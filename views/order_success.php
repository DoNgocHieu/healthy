<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: layout.php?page=login');
    exit;
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: layout.php?page=cart');
    exit;
}

// Lấy thông tin đơn hàng
$orderStmt = $pdo->prepare("
    SELECT o.*,
           ua.fullname, ua.phone, ua.address,
           u.email
    FROM orders o
    JOIN user_addresses ua ON o.address_id = ua.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: layout.php?page=cart');
    exit;
}

// Lấy chi tiết đơn hàng
$itemsStmt = $pdo->prepare("
    SELECT oi.*, i.name, i.image_url
    FROM order_items oi
    JOIN items i ON oi.item_id = i.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Thông tin ngân hàng nếu thanh toán chuyển khoản
$bankInfo = [
    'bank_name' => 'Techcombank',
    'account_name' => 'Công ty TNHH Healthy Food',
    'account_number' => '19033743209016',
    'branch' => 'Chi nhánh Hà Nội',
    'note' => "DH{$orderId}"
];
?>

<style>
.order-success {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.success-header {
    text-align: center;
    margin-bottom: 2rem;
}

.success-header h1 {
    color: #00b894;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.success-header p {
    color: #636e72;
    font-size: 1.1rem;
}

.order-info {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.order-info h2 {
    color: #2d3436;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.info-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.info-item {
    padding: 1rem;
    background: white;
    border-radius: 4px;
}

.info-item h3 {
    color: #636e72;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.info-item p {
    color: #2d3436;
    margin: 0;
}

.items-list {
    margin-bottom: 2rem;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dfe6e9;
}

.order-item:last-child {
    border-bottom: none;
}

.order-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 1rem;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 0.5rem;
    color: #2d3436;
}

.item-details p {
    margin: 0;
    color: #636e72;
}

.bank-info {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.bank-info h2 {
    color: #2d3436;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.bank-info p {
    margin: 0.5rem 0;
    color: #636e72;
}

.bank-info strong {
    color: #2d3436;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-primary {
    background: #00b894;
    color: white;
}

.btn-primary:hover {
    background: #00a187;
}

.btn-secondary {
    background: #dfe6e9;
    color: #2d3436;
}

.btn-secondary:hover {
    background: #b2bec3;
}
</style>

<div class="order-success">
    <div class="success-header">
        <h1>Đặt hàng thành công!</h1>
        <p>Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là: #<?= $orderId ?></p>
    </div>

    <div class="order-info">
        <h2>Thông tin đơn hàng</h2>
        <div class="info-grid">
            <div class="info-item">
                <h3>Người nhận</h3>
                <p><?= htmlspecialchars($order['fullname']) ?></p>
            </div>
            <div class="info-item">
                <h3>Số điện thoại</h3>
                <p><?= htmlspecialchars($order['phone']) ?></p>
            </div>
            <div class="info-item">
                <h3>Email</h3>
                <p><?= htmlspecialchars($order['email']) ?></p>
            </div>
            <div class="info-item">
                <h3>Địa chỉ</h3>
                <p><?= nl2br(htmlspecialchars($order['address'])) ?></p>
            </div>
            <div class="info-item">
                <h3>Phương thức thanh toán</h3>
                <p><?= $order['payment_method'] === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng' ?></p>
            </div>
            <div class="info-item">
                <h3>Tổng tiền</h3>
                <p><?= number_format($order['total'], 0, ',', '.') ?> đ</p>
            </div>
        </div>
    </div>

    <div class="items-list">
        <h2>Món ăn đã đặt</h2>
        <?php foreach ($items as $item): ?>
            <div class="order-item">
                <img src="../img/<?= htmlspecialchars($item['image_url']) ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="item-details">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p>Số lượng: <?= $item['quantity'] ?></p>
                    <p>Giá: <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                    <p>Thành tiền: <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> đ</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($order['payment_method'] === 'bank_transfer'): ?>
        <div class="bank-info">
            <h2>Thông tin chuyển khoản</h2>
            <p><strong>Ngân hàng:</strong> <?= $bankInfo['bank_name'] ?></p>
            <p><strong>Chủ tài khoản:</strong> <?= $bankInfo['account_name'] ?></p>
            <p><strong>Số tài khoản:</strong> <?= $bankInfo['account_number'] ?></p>
            <p><strong>Chi nhánh:</strong> <?= $bankInfo['branch'] ?></p>
            <p><strong>Nội dung chuyển khoản:</strong> <?= $bankInfo['note'] ?></p>
            <p style="color: #d63031;"><strong>Lưu ý:</strong> Vui lòng chuyển khoản trong vòng 24h kể từ khi đặt hàng.</p>
        </div>
    <?php endif; ?>

    <div class="action-buttons">
        <a href="layout.php?page=monmoi" class="btn btn-primary">Tiếp tục mua hàng</a>
        <a href="layout.php?page=orders" class="btn btn-secondary">Xem đơn hàng</a>
    </div>
</div>
