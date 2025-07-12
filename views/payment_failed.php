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
                        <i class="fas fa-exclamation-triangle payment-failed-icon"></i>
                    </div>

                    <h5 class="text-danger mb-3">Rất tiếc, thanh toán của bạn không thành công!</h5>
                    <!-- ...existing code... -->

                    <div class="alert alert-danger" role="alert">
                        <strong>Lý do:</strong> <?= htmlspecialchars($errorMessage) ?>
                        <?php if ($errorCode): ?>
                            <br><small class="payment-failed-error-code">Mã lỗi: <?= htmlspecialchars($errorCode) ?></small>
                        <?php endif; ?>
                    </div>

                    <?php if ($orderId): ?>
                        <p class="text-muted mb-4 payment-failed-order">
                            Đơn hàng #<?= htmlspecialchars($orderId) ?> vẫn được lưu trong hệ thống.<br>
                            Bạn có thể thử thanh toán lại hoặc chọn phương thức thanh toán khác.
                        </p>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <?php if ($orderId): ?>
                            <a href="layout.php?page=order_confirm&retry=<?= $orderId ?>"
                               class="btn btn-primary btn-lg payment-failed-btn">
                                <i class="fas fa-redo me-2"></i>
                                Thử Thanh Toán Lại
                            </a>
                        <?php endif; ?>

                        <a href="layout.php?page=cart" class="btn btn-secondary payment-failed-btn">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Quay Lại Giỏ Hàng
                        </a>

                        <a href="layout.php" class="btn btn-outline-secondary payment-failed-btn">
                            <i class="fas fa-home me-2"></i>
                            Về Trang Chủ
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted payment-failed-support">
                    <i class="fas fa-phone me-2"></i>
                    Cần hỗ trợ? Liên hệ: <strong>1900-1234</strong>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 6px 32px rgba(220,53,69,0.12);
}
.card-header {
    border: none;
    background: linear-gradient(135deg, #dc3545 60%, #c82333 100%) !important;
    box-shadow: 0 2px 8px rgba(220,53,69,0.08);
}
.payment-failed-icon {
    font-size: 72px;
    margin-bottom: 8px;
    color: #dc3545;
    filter: drop-shadow(0 2px 8px rgba(220,53,69,0.18));
}
.payment-failed-title {
    font-size: 1.35rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.payment-failed-alert {
    border-radius: 12px;
    border: none;
    font-size: 1.1rem;
    background: linear-gradient(90deg, #ffe5e5 60%, #fff6f6 100%);
    color: #b71c1c;
    box-shadow: 0 2px 8px rgba(220,53,69,0.08);
}
.payment-failed-order {
    font-size: 1.05rem;
    color: #6c757d;
}
.payment-failed-actions .btn {
    border-radius: 10px;
    padding: 14px 0;
    font-size: 1.08rem;
    font-weight: 600;
    margin-bottom: 8px;
    box-shadow: 0 2px 8px rgba(220,53,69,0.04);
    transition: background 0.2s, color 0.2s;
}
.payment-failed-btn.btn-primary {
    background: linear-gradient(90deg, #dc3545 60%, #c82333 100%) !important;
    border: none;
}
.payment-failed-btn.btn-primary:hover {
    background: linear-gradient(90deg, #c82333 60%, #dc3545 100%) !important;
    color: #fff;
}
.payment-failed-btn.btn-secondary {
    background: #f8f9fa !important;
    color: #343a40 !important;
    border: none;
}
.payment-failed-btn.btn-secondary:hover {
    background: #e2e6ea !important;
    color: #212529 !important;
}
.payment-failed-btn.btn-outline-secondary {
    border: 2px solid #dc3545 !important;
    color: #dc3545 !important;
    background: #fff !important;
}
.payment-failed-btn.btn-outline-secondary:hover {
    background: #dc3545 !important;
    color: #fff !important;
}
.payment-failed-support {
    font-size: 1.08rem;
    color: #6c757d;
    margin-top: 12px;
}
@media (max-width: 768px) {
    .card-body {
        padding: 2rem 0.5rem !important;
    }
    .payment-failed-icon {
        font-size: 48px;
    }
    .payment-failed-title {
        font-size: 1.1rem;
    }
}
</style>
