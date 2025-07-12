<?php
require_once __DIR__ . '/../config/config.php';

// Lấy các tham số trả về từ VNPay
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_Amount = $_GET['vnp_Amount'] ?? 0;
$vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
$vnp_PayDate = $_GET['vnp_PayDate'] ?? '';
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';

// Tạo mảng chứa dữ liệu trả về để verify
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);

// Tạo chuỗi hash để kiểm tra
$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, VNP_HASHSECRET);

// Kiểm tra chữ ký và xử lý kết quả
if ($secureHash == $vnp_SecureHash) {
    // Lấy order ID từ mã giao dịch
    $orderId = substr($vnp_TxnRef, 5); // Bỏ prefix 'ORDER'

    if ($vnp_ResponseCode == '00') {
        // Thanh toán thành công
        $pdo = getDb();
        $stmt = $pdo->prepare("
            UPDATE orders
            SET
                payment_status = 'paid',
                payment_method = 'vnpay',
                payment_transaction_no = ?,
                payment_bank_code = ?,
                payment_date = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        // Parse VNPay date format (YmdHis) to MySQL datetime
        $paymentDate = DateTime::createFromFormat('YmdHis', $vnp_PayDate);
        $paymentDateStr = $paymentDate ? $paymentDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

        $stmt->execute([
            $vnp_TransactionNo,
            $vnp_BankCode,
            $paymentDateStr,
            $orderId
        ]);

        header('Location: layout.php?page=order_success&id=' . $orderId);
    } else {
        // Thanh toán thất bại
        header('Location: layout.php?page=payment_failed&id=' . $orderId . '&error=' . $vnp_ResponseCode);
    }
} else {
    // Chữ ký không hợp lệ
    header('Location: layout.php?page=payment_failed&error=invalid_signature');
}
?>
