<?php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div style="color:red;padding:24px;">Thiếu hoặc sai mã đơn hàng!</div>';
    exit;
}
$orderId = intval($_GET['id']);
$bankCode = $_GET['bankCode'] ?? ''; // Thêm dòng này

require_once __DIR__ . '/../config/config.php';
$pdo = getDb();

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div style="color:red;padding:24px;">Không tìm thấy đơn hàng!</div>';
    exit;
}

// Thông tin cần thiết cho VNPAY
$total = $order['total_amount'];
$orderCode = 'ORDER' . $orderId;

?>
<link rel="stylesheet" href="../css/checkout.css">
<div class="checkout-container">
    <h2>Thanh toán qua VNPAY</h2>
    <div class="order-summary">
        <p>Mã đơn hàng: <b><?= htmlspecialchars($orderCode) ?></b></p>
        <p>Số tiền cần thanh toán: <b><?= number_format($total, 0, ',', '.') ?> đ</b></p>
    </div>
    <div style="margin:32px 0;">
        <!-- Thay thế bằng nút hoặc form tích hợp VNPAY thật -->
        <a href="#" class="checkout-button" style="background:#1976d2;color:#fff;padding:12px 32px;border-radius:8px;font-size:18px;">
            Thanh toán qua VNPAY
        </a>
        <p style="margin-top:16px;color:#1976d2;">(Demo: Tích hợp API VNPAY tại đây)</p>
    </div>
    <a href="layout.php?page=order_success&id=<?= $orderId ?>" style="color:#1a7f37;">Quay lại trang xác nhận đơn hàng</a>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white text-center py-4">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Thanh Toán VNPay
                    </h4>
                </div>
                <div class="card-body p-5">
                    <form method="POST" action="https://sandbox.vnpayment.vn/paymentv2/vpcpay.html" id="vnpayForm">
                        <input type="hidden" name="order_id" value="<?= $orderId ?>">
                        <input type="hidden" name="amount" value="<?= $total ?>">
                        <input type="hidden" name="bank_code" value="<?= htmlspecialchars($bankCode ?? '') ?>">
                        <button type="submit" class="btn btn-success btn-lg" id="payButton">
                            <i class="fas fa-credit-card me-2"></i>
                            Thanh Toán Ngay
                            <span class="fw-bold"><?= number_format($total, 0, ',', '.') ?>đ</span>
                        </button>
                    </form>
                    <?php if (!empty($bankCode)): ?>
                    <div class="alert alert-warning mt-3 text-center" id="countdownBox">
                        <i class="fas fa-clock me-2"></i>
                        Tự động chuyển hướng sau <strong id="countdown">5</strong> giây...
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        let countdown = 5;
                        const countdownEl = document.getElementById('countdown');
                        const vnpayForm = document.getElementById('vnpayForm');
                        const payButton = document.getElementById('payButton');
                        const timer = setInterval(function() {
                            countdown--;
                            countdownEl.textContent = countdown;
                            if (countdown <= 0) {
                                clearInterval(timer);
                                vnpayForm.submit();
                            }
                        }, 1000);
                        payButton.addEventListener('click', function(e) {
                            clearInterval(timer);
                            document.getElementById('countdownBox').remove();
                        });
                    });
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>