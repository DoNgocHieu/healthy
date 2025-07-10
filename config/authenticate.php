<?php
session_start();
require_once __DIR__ . '/config.php';
$mysqli = getDbConnection();

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

// Lấy dữ liệu
$login    = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate cơ bản
if (!$login || !$password) {
    $_SESSION['login_error'] = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

// Tìm user theo username hoặc email
$stmt = $mysqli->prepare("
    SELECT id, password 
    FROM users 
    WHERE username = ? OR email = ? 
    LIMIT 1
");
$stmt->bind_param('ss', $login, $login);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['login_error'] = 'Tên đăng nhập hoặc email không tồn tại.';
    $stmt->close();
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

$stmt->bind_result($userId, $hashedPassword);
$stmt->fetch();
$stmt->close();

// Kiểm tra mật khẩu
if (!password_verify($password, $hashedPassword)) {
    $_SESSION['login_error'] = 'Mật khẩu không đúng.';
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

// Đăng nhập thành công → tạo session
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $login;

// Redirect về home
header('Location: /healthy/views/layout.php?page=home');
exit;
