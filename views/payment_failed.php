<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();

$orderId = $_GET['id'] ?? null;
$errorCode = $_GET['error'] ?? '';

// Error code mapping
$errorMessages = [
    '01' => 'Giao dịch chưa hoàn tất',
    '02' => 'Giao dịch bị lỗi',
    '04' => 'Giao dịch đảo (Khách hàng đã bị trừ tiền tại Ngân hàng nhưng GD chưa thành công ở VNPAY)',
    '05' => 'VNPAY đang xử lý giao dịch này (GD hoàn tiền)',
    '06' => 'VNPAY đã gửi yêu cầu hoàn tiền sang Ngân hàng (GD hoàn tiền)',
    '07' => 'Giao dịch bị nghi ngờ',
    '09' => 'GD Hoàn trả bị từ chối',
    '10' => 'Đã giao hàng',
    '11' => 'Giao dịch không hợp lệ',
    '12' => 'Giao dịch không thành công',
    'invalid_signature' => 'Chữ ký không hợp lệ',
];

$errorMessage = $errorMessages[$errorCode] ?? 'Thanh toán không thành công';

// Update order status if we have order ID
if ($orderId) {
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = 'failed', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
}
?>

<link rel="stylesheet" href="../css/checkout.css">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-danger text-white text-center py-4">
                    <h4 class="mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        Thanh Toán Thất Bại
                    </h4>
                </div>
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 64px;"></i>
                    </div>

                    <h5 class="text-danger mb-3">Rất tiếc, thanh toán của bạn không thành công!</h5>

                    <div class="alert alert-danger" role="alert">
                        <strong>Lý do:</strong> <?= htmlspecialchars($errorMessage) ?>
                        <?php if ($errorCode): ?>
                            <br><small>Mã lỗi: <?= htmlspecialchars($errorCode) ?></small>
                        <?php endif; ?>
                    </div>

                    <?php if ($orderId): ?>
                        <p class="text-muted mb-4">
                            Đơn hàng #<?= htmlspecialchars($orderId) ?> vẫn được lưu trong hệ thống.
                            Bạn có thể thử thanh toán lại hoặc chọn phương thức thanh toán khác.
                        </p>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <?php if ($orderId): ?>
                            <a href="layout.php?page=order_confirm&retry=<?= $orderId ?>"
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-redo me-2"></i>
                                Thử Thanh Toán Lại
                            </a>
                        <?php endif; ?>

                        <a href="layout.php?page=cart" class="btn btn-secondary">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Quay Lại Giỏ Hàng
                        </a>

                        <a href="layout.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>
                            Về Trang Chủ
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-phone me-2"></i>
                    Cần hỗ trợ? Liên hệ: <strong>1900-1234</strong>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border: none;
}

.btn {
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
}

.alert {
    border-radius: 10px;
    border: none;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}
</style>
