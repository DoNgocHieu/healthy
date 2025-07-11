<?php
require_once __DIR__ . '/../../config/Database.php';

class UserAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUsers($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            // Đếm tổng số người dùng
            $stmt = $this->db->query('SELECT COUNT(*) as total FROM users');
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Lấy danh sách người dùng
            $query = "
                SELECT id, username, email, fullname, phone, address, role, created_at
                FROM users
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$perPage, $offset]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            error_log("UserAdmin error: " . $e->getMessage());
            return [
                'users' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
                'error' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }
}

// Khởi tạo UserAdmin
$userAdmin = new UserAdmin();

// Xử lý phân trang - sử dụng p làm tham số phân trang để tránh xung đột với page routing
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Lấy danh sách người dùng
$result = $userAdmin->getUsers($currentPage, $perPage);
$users = $result['users'];
$totalPages = $result['totalPages'];
?>

<!-- Tiêu đề -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Quản lý người dùng</h2>
</div>

<!-- Hiển thị thông báo lỗi nếu có -->
<?php if (!empty($result['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($result['error']); ?>
    </div>
<?php endif; ?>

<!-- Bảng danh sách người dùng -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Họ tên</th>
                <th>Số điện thoại</th>
                <th>Địa chỉ</th>
                <th>Vai trò</th>
                <th>Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars((string)$user['id']); ?></td>
                <td><?php echo htmlspecialchars((string)$user['username']); ?></td>
                <td><?php echo htmlspecialchars((string)$user['email']); ?></td>
                <td><?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($user['phone'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string)($user['address'] ?? '')); ?></td>
                <td><?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?></td>
                <td><?php echo $user['created_at'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))) : ''; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr>
                <td colspan="8" class="text-center">Chưa có người dùng nào</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Phân trang">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="?page=admin&section=users&p=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
