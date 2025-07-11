<?php
$vnpayUrl = $vnp_Url;
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div style="color:red;padding:24px;">Thiếu hoặc sai mã đơn hàng!</div>';
    exit;
}
$orderId = intval($_GET['id']);
$bankCode = $_GET['bankCode'] ?? '';


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

// Khởi tạo dữ liệu cho VNPay
$vnp_TxnRef = 'ORDER' . $orderId; // Mã đơn hàng
$vnp_OrderInfo = 'Thanh toan don hang ' . $vnp_TxnRef;
$vnp_OrderType = 'billpayment';
$vnp_Amount = $order['total_amount'] * 100; // Số tiền * 100 (tiền tệ VNĐ)
$vnp_Locale = 'vn';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
$vnp_CreateDate = date('YmdHis');

// Tạo mảng dữ liệu gửi đi
$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => VNP_TMNCODE,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => $vnp_CreateDate,
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => VNP_RETURN_URL,
    "vnp_TxnRef" => $vnp_TxnRef
);

if (!empty($bankCode)) {
    $inputData['vnp_BankCode'] = $bankCode;
}

// Sắp xếp dữ liệu theo thứ tự a-z
ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}
$vnp_Url = VNP_URL . "?" . $query;
$vnpSecureHash = hash_hmac('sha512', $hashdata, VNP_HASHSECRET);
$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

// Nếu muốn chuyển hướng tự động, không cần render HTML bên dưới
// Nếu muốn hiển thị trang xác nhận, hãy comment hoặc xóa dòng chuyển hướng sau:

header("Location: $vnp_Url");
exit;

// The HTML code below is unreachable due to the exit above.
// If you want to show a confirmation page instead of redirecting immediately,
// comment out the header() and exit; lines above and uncomment the HTML below.

/*
<link rel="stylesheet" href="../css/checkout.css">
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
                    <div class="order-summary mb-4">
                        <p>Mã đơn hàng: <b><?= htmlspecialchars($vnp_TxnRef) ?></b></p>
                        <p>Số tiền thanh toán: <b><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</b></p>
                    </div>
                    
                    <form method="GET" action="<?= $vnpayUrl ?>" id="vnpayForm">
                        <button type="submit" class="btn btn-success btn-lg w-100" id="payButton">
                            <i class="fas fa-credit-card me-2"></i>
                            Thanh Toán Ngay
                            <span class="fw-bold"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</span>
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
            
            <div class="text-center mt-3">
                <a href="layout.php?page=order_success&id=<?= $orderId ?>" 
                   class="text-decoration-none" style="color:#1a7f37;">
                    <i class="fas fa-arrow-left me-2"></i>
                    Quay lại trang xác nhận đơn hàng
                </a>
            </div>
        </div>
    </div>
</div>
*/