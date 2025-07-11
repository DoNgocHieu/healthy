<?php
require_once __DIR__ . '/../config/config.php';

class Catalog {
    private $pdo;

    public function __construct() {
        $this->pdo = getDb();
    }

    public function getAllCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }

    public function getItems($options = []) {
        $where = [];
        $params = [];
        $orderBy = "items.id DESC";
        $limit = isset($options['limit']) ? (int)$options['limit'] : 12;
        $offset = isset($options['page']) ? ((int)$options['page'] - 1) * $limit : 0;

        // Filter by category
        if (!empty($options['category'])) {
            $where[] = "items.TT = ?";
            $params[] = $options['category'];
        }

        // Search by name
        if (!empty($options['search'])) {
            $where[] = "items.name LIKE ?";
            $params[] = "%{$options['search']}%";
        }

        // Price range
        if (!empty($options['min_price'])) {
            $where[] = "items.price >= ?";
            $params[] = $options['min_price'];
        }
        if (!empty($options['max_price'])) {
            $where[] = "items.price <= ?";
            $params[] = $options['max_price'];
        }

        // Sort
        if (!empty($options['sort'])) {
            switch ($options['sort']) {
                case 'price_asc':
                    $orderBy = "items.price ASC";
                    break;
                case 'price_desc':
                    $orderBy = "items.price DESC";
                    break;
                case 'name_asc':
                    $orderBy = "items.name ASC";
                    break;
                case 'name_desc':
                    $orderBy = "items.name DESC";
                    break;
            }
        }

        // Build query
        $sql = "SELECT items.*,
                       categories.name as category_name,
                       COALESCE(AVG(reviews.rating), 0) as average_rating,
                       COUNT(DISTINCT reviews.id) as review_count
                FROM items
                LEFT JOIN categories ON items.TT = categories.TT
                LEFT JOIN reviews ON items.id = reviews.item_id";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " GROUP BY items.id
                  ORDER BY {$orderBy}
                  LIMIT {$limit} OFFSET {$offset}";

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM items";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(" AND ", $where);
        }
        $totalCount = $this->pdo->prepare($countSql);
        $totalCount->execute($params);
        $total = $totalCount->fetchColumn();

        // Get items
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    public function getItemDetails($itemId, $userId = null) {
        $sql = "SELECT items.*,
                       categories.name as category_name,
                       COALESCE(AVG(reviews.rating), 0) as average_rating,
                       COUNT(DISTINCT reviews.id) as review_count,
                       " . ($userId ? "(SELECT 1 FROM favorites WHERE user_id = ? AND item_id = items.id) as is_favorite" : "0 as is_favorite") . "
                FROM items
                LEFT JOIN categories ON items.TT = categories.TT
                LEFT JOIN reviews ON items.id = reviews.item_id
                WHERE items.id = ?
                GROUP BY items.id";

        $params = $userId ? [$userId, $itemId] : [$itemId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $item = $stmt->fetch();

        if ($item) {
            // Get reviews
            $sql = "SELECT reviews.*,
                          users.username,
                          users.avatar_url,
                          GROUP_CONCAT(review_images.image_url) as images
                   FROM reviews
                   LEFT JOIN users ON reviews.user_id = users.id
                   LEFT JOIN review_images ON reviews.id = review_images.review_id
                   WHERE reviews.item_id = ?
                   GROUP BY reviews.id
                   ORDER BY reviews.created_at DESC
                   LIMIT 5";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$itemId]);
            $item['reviews'] = $stmt->fetchAll();

            // Get related items
            $sql = "SELECT items.*,
                          COALESCE(AVG(reviews.rating), 0) as average_rating
                   FROM items
                   LEFT JOIN reviews ON items.id = reviews.item_id
                   WHERE items.TT = ? AND items.id != ?
                   GROUP BY items.id
                   ORDER BY RAND()
                   LIMIT 4";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$item['TT'], $itemId]);
            $item['related_items'] = $stmt->fetchAll();
        }

        return $item;
    }

    public function toggleFavorite($userId, $itemId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM favorites WHERE user_id = ? AND item_id = ?"
            );
            $stmt->execute([$userId, $itemId]);
            $favorite = $stmt->fetch();

            if ($favorite) {
                $stmt = $this->pdo->prepare(
                    "DELETE FROM favorites WHERE user_id = ? AND item_id = ?"
                );
                $stmt->execute([$userId, $itemId]);
                return [
                    'success' => true,
                    'is_favorite' => false,
                    'message' => 'Đã xóa khỏi danh sách yêu thích'
                ];
            } else {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO favorites (user_id, item_id) VALUES (?, ?)"
                );
                $stmt->execute([$userId, $itemId]);
                return [
                    'success' => true,
                    'is_favorite' => true,
                    'message' => 'Đã thêm vào danh sách yêu thích'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    public function getUserFavorites($userId, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT items.*,
                       categories.name as category_name,
                       COALESCE(AVG(reviews.rating), 0) as average_rating,
                       COUNT(DISTINCT reviews.id) as review_count,
                       favorites.created_at as favorited_at
                FROM favorites
                JOIN items ON favorites.item_id = items.id
                LEFT JOIN categories ON items.TT = categories.TT
                LEFT JOIN reviews ON items.id = reviews.item_id
                WHERE favorites.user_id = ?
                GROUP BY items.id
                ORDER BY favorites.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        $items = $stmt->fetchAll();

        // Get total count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = $stmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }
}
