<?php
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: layout.php?page=order_confirm&error=invalid_order');
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
    header('Location: layout.php?page=order_confirm&error=order_not_found');
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

// Show confirmation page instead of immediate redirect
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán VNPay - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
        }
        .btn-pay {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .order-info {
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card payment-card">
                <div class="card-header text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="fas fa-credit-card me-3"></i>
                        Thanh Toán VNPay
                    </h3>
                </div>
                <div class="card-body p-5">
                    <!-- Thông tin đơn hàng -->
                    <div class="order-info p-4 mb-4">
                        <h5 class="text-dark mb-3">
                            <i class="fas fa-receipt me-2"></i>
                            Thông tin đơn hàng
                        </h5>
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-2"><strong>Mã đơn hàng:</strong></p>
                                <p class="mb-2"><strong>Số tiền:</strong></p>
                                <p class="mb-0"><strong>Phương thức:</strong></p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="mb-2 text-primary fw-bold"><?= htmlspecialchars($vnp_TxnRef) ?></p>
                                <p class="mb-2 text-danger fw-bold"><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</p>
                                <p class="mb-0 text-success fw-bold">VNPay</p>
                            </div>
                        </div>
                    </div>

                    <!-- Nút thanh toán -->
                    <div class="text-center mb-4">
                        <a href="<?= htmlspecialchars($vnp_Url) ?>" class="btn btn-success btn-pay w-100" id="payButton">
                            <i class="fas fa-credit-card me-2"></i>
                            Thanh Toán Ngay
                            <div class="fw-bold"><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</div>
                        </a>
                    </div>

                    <!-- Thông báo -->
                    <div class="alert alert-info text-center border-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Bạn sẽ được chuyển đến cổng thanh toán VNPay an toàn để hoàn tất giao dịch
                    </div>

                    <!-- Countdown -->
                    <div class="alert alert-warning text-center border-0" id="countdownBox">
                        <i class="fas fa-clock me-2"></i>
                        Tự động chuyển hướng sau <strong id="countdown">15</strong> giây...
                    </div>

                    <!-- Security notice -->
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Giao dịch được bảo mật bởi VNPay
                        </small>
                    </div>
                </div>
            </div>

            <!-- Back link -->
            <div class="text-center mt-4">
                <a href="layout.php?page=order_confirm"
                   class="text-white text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>
                    Quay lại trang xác nhận đơn hàng
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let countdown = 15;
    const countdownEl = document.getElementById('countdown');
    const payButton = document.getElementById('payButton');

    const timer = setInterval(function() {
        countdown--;
        countdownEl.textContent = countdown;
        if (countdown <= 0) {
            clearInterval(timer);
            window.location.href = '<?= htmlspecialchars($vnp_Url) ?>';
        }
    }, 1000);

    payButton.addEventListener('click', function(e) {
        clearInterval(timer);
        document.getElementById('countdownBox').remove();
    });
});
</script>
