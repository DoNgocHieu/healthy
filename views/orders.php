<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$userId = $_SESSION['user_id'] ?? null;

// Xử lý AJAX cho hủy/hoàn thành đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['cancel_order', 'complete_order'])) {
    header('Content-Type: application/json');
    $success = false;
    if (!$userId) {
        echo json_encode(['success' => false, 'msg' => 'Chưa đăng nhập']);
        exit;
    }
    $orderId = (int)($_POST['order_id'] ?? 0);
    $checkStmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$orderId, $userId]);
    $status = $checkStmt->fetchColumn();

    if ($_POST['action'] === 'cancel_order' && $status === 'pending') {
        $upd = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND user_id = ?");
        $upd->execute([$orderId, $userId]);
        $success = true;
    }
    if ($_POST['action'] === 'complete_order' && in_array($status, ['shipping','confirmed'])) {
        $upd = $pdo->prepare("UPDATE orders SET order_status = 'completed', payment_status = 'paid' WHERE id = ? AND user_id = ?");
        $upd->execute([$orderId, $userId]);
        $success = true;

        // Tính điểm: ví dụ 1 điểm mỗi 10.000đ
        $orderStmt = $pdo->prepare("SELECT total_amount FROM orders WHERE id = ?");
        $orderStmt->execute([$orderId]);
        $total = $orderStmt->fetchColumn();
        $addPoint = floor($total / 10000);

        // Ghi lịch sử điểm vào bảng points_history
        $insHistory = $pdo->prepare("INSERT INTO points_history (user_id, change_amount, created_at) VALUES (?, ?, NOW())");
        $insHistory->execute([$userId, $addPoint]);
    }
    echo json_encode(['success' => $success]);
    exit;
}

if (!$userId) {
    header('Location: layout.php?page=login');
    exit;
}

// Lấy danh sách đơn hàng của người dùng
$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.created_at,
        o.order_status,
        o.payment_method,
        o.shipping_address,
        o.total_amount
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../css/orders.css">

<div class="orders-container">
    <h1>Đơn hàng của tôi</h1>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <!-- <img src="../img/empty-order.png" alt="Không có đơn hàng"> -->
            <p>Bạn chưa có đơn hàng nào</p>
            <a href="layout.php?page=home" class="btn-shop-now">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Đơn hàng #<?= $order['id'] ?></h3>
                            <p>Đặt ngày: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="order-status <?= strtolower($order['order_status']) ?>">
                            <?php
                            $statusText = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'shipping' => 'Đang giao hàng',
                                'processing' => 'Đang xử lý',
                                'completed' => 'Đã giao hàng',
                                'cancelled' => 'Đã hủy'
                            ];
                            echo $statusText[$order['order_status']] ?? $order['order_status'];
                            ?>
                        </div>
                    </div>

                    <div class="order-body">
                        <?php
                        // Lấy chi tiết đơn hàng
                        $detailStmt = $pdo->prepare("
                            SELECT
                                oi.quantity,
                                oi.price,
                                i.name,
                                i.image_url
                            FROM order_items oi
                            JOIN items i ON oi.item_id = i.id
                            WHERE oi.order_id = ?
                        ");
                        $detailStmt->execute([$order['id']]);
                        $orderItems = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="order-items">
                            <?php foreach ($orderItems as $item): ?>
                                <div class="order-item">
                                    <img src="../img/<?= htmlspecialchars($item['image_url']) ?>"
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div class="item-info">
                                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                                        <p>Số lượng: <?= $item['quantity'] ?></p>
                                        <p>Đơn giá: <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="delivery-info">
                                <h4>Thông tin giao hàng</h4>
                                <?php
                                $shippingParts = explode(',', $order['shipping_address'] ?? '');
                                $fullname = trim($shippingParts[0] ?? '');
                                $phone = trim($shippingParts[1] ?? '');
                                $address = implode(',', array_slice($shippingParts, 2));
                                ?>
                                <p><strong>Người nhận:</strong> <?= htmlspecialchars($fullname) ?></p>
                                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($phone) ?></p>
                                <p><strong>Địa chỉ giao hàng:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'] ?? '')) ?></p>
                                <p><strong>Phương thức thanh toán:</strong>
                                    <?= $order['payment_method'] === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng' ?>
                                </p>
                            </div>
                            <div class="order-total">
                                <p>Tổng tiền: <span><?= number_format($order['total_amount'] ?? 0, 0, ',', '.') ?> đ</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="order-status-actions" data-order-id="<?= $order['id'] ?>" data-status="<?= $order['order_status'] ?>">
                        <?php if ($order['order_status'] === 'pending'): ?>
                            <button type="button" class="btn-cancel-order" title="Hủy đơn" onclick="cancelOrder(<?= $order['id'] ?>, this)">
                                <i class="fa fa-times-circle"></i> Hủy đơn
                            </button>
                        <?php else: ?>
                            <span class="order-status-label <?= strtolower($order['order_status']) ?>">
                                <?php
                                $statusText = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'shipping' => 'Đang giao hàng',
                                    'processing' => 'Đang xử lý',
                                    'completed' => 'Đã giao hàng',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $statusText[$order['order_status']] ?? $order['order_status'];
                                ?>
                            </span>
                            <?php if (in_array($order['order_status'], ['shipping','confirmed'])): ?>
                                <button type="button" class="btn-complete-order" title="Đã nhận hàng" onclick="completeOrder(<?= $order['id'] ?>, this)">
                                    <i class="fa fa-check-circle"></i> Đã nhận hàng
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>



<style>
.order-status-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: flex-end;
  min-height: 40px;
  margin: 20px;
}
.order-status-label {
  font-weight: 600;
  font-size: 1rem;
  padding: 6px 18px;
  border-radius: 18px;
  background: #f5f5f5;
  color: #2d4c2a;
  margin-right: 0;
}
.order-status-label.completed { background: #eafbe7; color: #27ae60; }
.order-status-label.cancelled { background: #fbeaea; color: #e74c3c; }
.order-status-label.shipping { background: #eaf3fb; color: #2980b9; }
.order-status-label.confirmed { background: #f7fbe9; color: #f39c12; }
.order-status-label.processing { background: #f7fbe9; color: #8e44ad; }
.order-status-label.pending { background: #fffbe9; color: #f39c12; }

.btn-cancel-order, .btn-complete-order {
  border: none;
  outline: none;
  background: #e74c3c;
  color: #fff;
  font-weight: 500;
  font-size: 0.98rem;
  border-radius: 18px;
  padding: 7px 18px 7px 14px;
  cursor: pointer;
  transition: background 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 20px;
}
.btn-cancel-order i { color: #fff; font-size: 1.1em; }
.btn-cancel-order:hover {
  background: #c0392b;
}
.btn-complete-order {
  background: #27ae60;
}
.btn-complete-order i { color: #fff; font-size: 1.1em; }
.btn-complete-order:hover {
  background: #219150;
}
</style>

<script>
function cancelOrder(orderId, btn) {
  if (!confirm('Bạn chắc chắn muốn hủy đơn hàng này?')) return;
  btn.disabled = true;
  fetch('orders.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=cancel_order&order_id=' + orderId
  })
  .then(response => response.json())
  .then(data => {
    btn.disabled = false;
    if (data.success) {
      // Cập nhật giao diện người dùng
      const orderCard = btn.closest('.order-card');
      orderCard.querySelector('.order-status').textContent = 'Đã hủy';
      orderCard.querySelector('.order-status').className = 'order-status cancelled';
      btn.remove();
    } else {
      alert('Đã có lỗi xảy ra. Vui lòng thử lại sau.');
    }
  })
  .catch(error => {
    btn.disabled = false;
    console.error('Error:', error);
    alert('Đã có lỗi xảy ra. Vui lòng thử lại sau.');
  });
}

function completeOrder(orderId, btn) {
  if (!confirm('Xác nhận đã nhận hàng?')) return;
  btn.disabled = true;
  fetch('orders.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=complete_order&order_id=' + orderId
  })
  .then(response => response.json())
  .then(data => {
    btn.disabled = false;
    if (data.success) {
      const orderCard = btn.closest('.order-card');
      // Cập nhật trạng thái chính
      const statusDiv = orderCard.querySelector('.order-status');
      if (statusDiv) {
        statusDiv.textContent = 'Đã giao hàng';
        statusDiv.className = 'order-status completed';
      }
      // Cập nhật label trạng thái nếu có
      const statusLabel = orderCard.querySelector('.order-status-label');
      if (statusLabel) {
        statusLabel.textContent = 'Đã giao hàng';
        statusLabel.className = 'order-status-label completed';
      }
      // Ẩn nút
      btn.remove();
    } else {
      alert('Đã có lỗi xảy ra. Vui lòng thử lại sau.');
    }
  })
  .catch(error => {
    btn.disabled = false;
    console.error('Error:', error);
    alert('Đã có lỗi xảy ra. Vui lòng thử lại sau.');
  });
}
</script>
