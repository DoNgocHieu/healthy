<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'broccoli');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', '/healthy');

function getDbConnection() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($mysqli->connect_errno) {
        die("Kết nối MySQL thất bại: ("
            . $mysqli->connect_errno . ") "
            . $mysqli->connect_error);
    }
    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}

function getDb() {
    static $pdo;
    if (!$pdo) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) {
            die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
        }
    }
    return $pdo;
}

$mysqli = getDbConnection();
$pdo     = getDb();

define('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNP_RETURN_URL', 'http://localhost/healthy/views/vnpay_return.php'); // Updated to remove port
define('VNP_TMNCODE', 'NJJ0R8FS'); // Terminal ID provided by VNPay
define('VNP_HASHSECRET', 'BYKJBHPPZKQMKBIBGGXIYKWYFAYSJXCW'); // Secret key provided by VNPay
