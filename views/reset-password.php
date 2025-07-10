<?php
require_once dirname(__DIR__) . '/config/config.php';
$mysqli = getDbConnection();

if (empty($_GET['token'])) {
    die('Token không hợp lệ!');
}
$token = $_GET['token'];

// Kiểm tra token hợp lệ và chưa hết hạn
$stmt = $mysqli->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    die('Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!');
}
$stmt->bind_result($userId, $expiresAt);
$stmt->fetch();
if (strtotime($expiresAt) < time()) {
    die('Link đặt lại mật khẩu đã hết hạn!');
}
$stmt->close();

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    if (empty($password) || empty($confirm)) {
        $error = 'Vui lòng nhập đầy đủ mật khẩu mới.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải từ 6 ký tự.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param('si', $hash, $userId);
        $upd->execute();
        $upd->close();
        $del = $mysqli->prepare("DELETE FROM password_resets WHERE token = ?");
        $del->bind_param('s', $token);
        $del->execute();
        $del->close();
        $success = 'Đặt lại mật khẩu thành công! Bạn có thể <a href=\"layout.php?page=login\">đăng nhập</a>.';
    }
}
?>
<link rel="stylesheet" href="../css/login.css"/>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <img src="../img/logo.png" alt="Broccoli"/>
      <h1>ĐẶT LẠI MẬT KHẨU</h1>
    </div>
    <?php if ($error): ?>
      <div class="error-msg"><?= nl2br(htmlspecialchars($error)) ?></div>
    <?php elseif ($success): ?>
      <div class="success-msg"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!$success): ?>
    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="password">Mật khẩu mới</label>
        <input type="password" id="password" name="password" required minlength="6" placeholder="Nhập mật khẩu mới" />
      </div>
      <div class="form-group">
        <label for="confirm">Nhập lại mật khẩu</label>
        <input type="password" id="confirm" name="confirm" required minlength="6" placeholder="Nhập lại mật khẩu" />
      </div>
      <button type="submit" class="btn-reset" style="background: #248a5a; color: #fff; font-weight: 600; border: none; border-radius: 8px; padding: .75rem; font-size: 1rem; transition: background .2s, transform .1s; cursor: pointer; margin-top: 1rem;">Đặt lại mật khẩu</button>
    </form>
    <?php endif; ?>
    <div class="back-link">
      <a href="layout.php?page=login">&larr; Quay lại đăng nhập</a>
    </div>
  </div>
</div>