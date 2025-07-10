<?php
session_start();
require_once __DIR__ . '/config.php';
$mysqli = getDbConnection();  

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}

// Lấy dữ liệu từ form
$username        = trim($_POST['username'] ?? '');
$email           = trim($_POST['email']    ?? '');
$password        = $_POST['password']      ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$username || !$email || !$password || !$confirmPassword) {
    $_SESSION['signin_error'] = 'Vui lòng điền đầy đủ thông tin.';
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}
// Kiểm tra username hoặc email đã tồn tại?
$stmt = $mysqli->prepare(
    'SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1'
);
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Lấy dữ liệu trả về để so sánh
    $stmt->bind_result($dbUsername, $dbEmail);
    $stmt->fetch();

    if ($dbUsername === $username) {
        $_SESSION['signin_error'] = 'Tên đăng nhập đã tồn tại.';
    } else {
        $_SESSION['signin_error'] = 'Email đã được sử dụng.';
    }

    $stmt->close();
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}
$stmt->close();
if (!preg_match('/[A-Za-z]/', $username)) {
    $_SESSION['signin_error'] = 'Tên đăng nhập phải có ít nhất một chữ cái.';
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}
// Validate mật khẩu: trên 8 ký tự, ít nhất 1 thường, 1 hoa, 1 số, 1 đặc biệt
$pattern = '/^(?=.{8,}$)(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/';
if (preg_match($pattern, $password) !== 1) {
    $_SESSION['signin_error'] = 
        "Vui lòng điền mật khẩu có chứa:\n" .
        "- ít nhất 1 chữ thường\n" .
        "- ít nhất 1 chữ hoa\n" .
        "- ít nhất 1 số\n" .
        "- ít nhất 1 ký tự đặc biệt\n" .
        "và tổng độ dài phải trên 8 ký tự";
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}

// Xác nhận mật khẩu
if ($password !== $confirmPassword) {
    $_SESSION['signin_error'] = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}

// Hash mật khẩu & INSERT tài khoản mới
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare(
    'INSERT INTO users (username, email, password) VALUES (?, ?, ?)'
);
$stmt->bind_param('sss', $username, $email, $hash);

if (! $stmt->execute()) {
    // Nếu lỗi khi insert
    $_SESSION['signin_error'] = 'Đăng ký thất bại. Vui lòng thử lại.';
    $stmt->close();
    header('Location: /healthy/views/layout.php?page=signin');
    exit;
}

$stmt->close();

// Tự động đăng nhập, lưu session
$userId = $mysqli->insert_id;
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;

// Chuyển về trang home
header('Location: /healthy/views/layout.php?page=home');
exit;