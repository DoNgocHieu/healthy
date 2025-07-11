<?php
require_once __DIR__ . '/Database.php';

class ItemAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getItems($filters = [], $page = 1, $perPage = 10) {
        try {
            // Kiểm tra xem bảng items có tồn tại không
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'items'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return [
                    'items' => [],
                    'total' => 0,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => 0,
                    'error' => 'Bảng items chưa được tạo'
                ];
            }

            // Kiểm tra cấu trúc bảng items
            $stmt = $this->db->prepare("DESCRIBE items");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $hasCategory = in_array('TT', $columns); // Sử dụng TT thay vì category_id

            $offset = ($page - 1) * $perPage;
            $where = [];
            $params = [];

            if (!empty($filters['name'])) {
                $where[] = 'i.name LIKE ?';
                $params[] = "%{$filters['name']}%";
            }

            if ($hasCategory && !empty($filters['category_id'])) {
                $where[] = 'i.TT = ?'; // Sử dụng TT thay vì category_id
                $params[] = $filters['category_id'];
            }

            if (isset($filters['stock'])) {
                switch ($filters['stock']) {
                    case 'out':
                        $where[] = 'i.quantity = 0';
                        break;
                    case 'in':
                        $where[] = 'i.quantity > 0';
                        break;
                    case 'low':
                        $where[] = 'i.quantity < 10 AND i.quantity > 0';
                        break;
                }
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // Get total records for pagination
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as total
                FROM items i
                ' . $whereClause
            );
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Check if categories table exists
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'categories'");
            $stmt->execute();
            $categoriesExist = $stmt->fetch();

            // Get items with category if available
            if ($categoriesExist && $hasCategory) {
                $stmt = $this->db->prepare('
                    SELECT i.*, c.name as category_name, i.TT as category_id, i.quantity as stock_quantity
                    FROM items i
                    LEFT JOIN categories c ON i.TT = c.TT
                    ' . $whereClause . '
                    ORDER BY i.name ASC
                    LIMIT ? OFFSET ?
                ');
            } else {
                $stmt = $this->db->prepare('
                    SELECT i.*, i.TT as category_id, i.quantity as stock_quantity
                    FROM items i
                    ' . $whereClause . '
                    ORDER BY i.name ASC
                    LIMIT ? OFFSET ?
                ');
            }

            $params = array_merge($params, [$perPage, $offset]);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
                'error' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    public function getItem($id) {
        try {
            // Check if categories table exists
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'categories'");
            $stmt->execute();
            $categoriesExist = $stmt->fetch();

            if ($categoriesExist) {
                $stmt = $this->db->prepare('
                    SELECT i.*, c.name as category_name, i.TT as category_id, i.quantity as stock_quantity
                    FROM items i
                    LEFT JOIN categories c ON i.TT = c.TT
                    WHERE i.id = ?
                ');
            } else {
                $stmt = $this->db->prepare('
                    SELECT i.*, i.TT as category_id, i.quantity as stock_quantity
                    FROM items i
                    WHERE i.id = ?
                ');
            }

            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    public function saveItem($data) {
        try {
            $this->db->beginTransaction();

            // Check if items table exists, if not create it
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'items'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                $this->db->exec('
                    CREATE TABLE items (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        description TEXT,
                        price DECIMAL(10,2) NOT NULL,
                        stock_quantity INT DEFAULT 0,
                        image_url VARCHAR(255),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ');
            }

            // Check if categories table exists
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'categories'");
            $stmt->execute();
            $categoriesExist = $stmt->fetch();

            // Check if TT column exists (for category relationship)
            $stmt = $this->db->prepare("DESCRIBE items");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $hasCategory = in_array('TT', $columns);

            // Items table should already have TT column as foreign key to categories.TT
            // No need to add category_id column

            if (empty($data['id'])) {
                // Insert new item
                $columns = ['name', 'description', 'price', 'quantity'];
                $values = ['?', '?', '?', '?'];
                $params = [
                    $data['name'],
                    $data['description'],
                    $data['price'],
                    $data['stock_quantity'] ?? 0 // Map stock_quantity to quantity
                ];

                if (!empty($data['image_url'])) {
                    $columns[] = 'image_url';
                    $values[] = '?';
                    $params[] = $data['image_url'];
                }

                if ($hasCategory && !empty($data['category_id'])) {
                    $columns[] = 'TT'; // Sử dụng TT thay vì category_id
                    $values[] = '?';
                    $params[] = $data['category_id'];
                }

                $sql = 'INSERT INTO items (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } else {
                // Update existing item
                $sets = ['name = ?', 'description = ?', 'price = ?', 'quantity = ?'];
                $params = [
                    $data['name'],
                    $data['description'],
                    $data['price'],
                    $data['stock_quantity'] ?? 0 // Map stock_quantity to quantity
                ];

                if (!empty($data['image_url'])) {
                    $sets[] = 'image_url = ?';
                    $params[] = $data['image_url'];
                }

                if ($hasCategory && isset($data['category_id'])) {
                    $sets[] = 'TT = ?'; // Sử dụng TT thay vì category_id
                    $params[] = $data['category_id'];
                }

                $params[] = $data['id'];

                $sql = 'UPDATE items SET ' . implode(', ', $sets) . ' WHERE id = ?';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    public function deleteItem($id) {
        try {
            $this->db->beginTransaction();

            // Check if item exists in any orders
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'order_items'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $this->db->prepare('
                    SELECT COUNT(*) as count
                    FROM order_items
                    WHERE item_id = ?
                ');
                $stmt->execute([$id]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                if ($count > 0) {
                    throw new Exception('Không thể xóa món ăn này vì đã có trong đơn hàng');
                }
            }

            // Delete item
            $stmt = $this->db->prepare('DELETE FROM items WHERE id = ?');
            $stmt->execute([$id]);

            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCategories() {
        try {
            // Check if categories table exists
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'categories'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return [];
            }

            // Use TT as id since that's the primary key in categories table
            $stmt = $this->db->prepare('SELECT TT as id, name FROM categories ORDER BY name');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting categories: " . $e->getMessage());
            return [];
        }
    }
}
