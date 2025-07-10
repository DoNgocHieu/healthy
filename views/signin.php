<?php
require_once __DIR__ . '/../config/config.php';

$pdo = getDbConnection();

$error = $_SESSION['signin_error'] ?? '';
unset($_SESSION['signin_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu
    $u = trim($_POST['username'] ?? '');
    $e = trim($_POST['email']    ?? '');
    $p = $_POST['password']      ?? '';
    $p2= $_POST['confirm_password'] ?? '';

    // Validate
    if (!$u || !$e || !$p || !$p2) {
        $err = 'Vui lòng điền đầy đủ.';
    } elseif ($p !== $p2) {
        $err = 'Mật khẩu không khớp.';
    } else {
        // Check tồn tại
        $stmt = $pdo->prepare('
          SELECT COUNT(*) FROM users WHERE username=? OR email=?
        ');
        $stmt->execute([$u, $e]);
        if ($stmt->fetchColumn()) {
            $err = 'Username hoặc email đã tồn tại.';
        } else {
            // Insert
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
              INSERT INTO users (username,email,password)
              VALUES (?,?,?)
            ');
            $stmt->execute([$u,$e,$hash]);
            // Tự động login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username']= $u;
            header('Location: home.php');
            exit;
        }
    }
}
?>
<link rel="stylesheet" href="../css/signin.css"/>

  <main class="signin-page">
    <div class="signin-box">
      <div class="signin-logo">
        <img src="../img/logo.png" alt="Broccoli Logo" />
        <h1>ĐĂNG KÝ</h1>
      </div>
      <form action="../config/do_signin.php"method="POST">
        <div class="form-group">
          <label for="username">Tên đăng nhập</label>
          <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required />
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Nhập email" required />
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu</label>
          <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required />
        </div>

        <div class="form-group">
          <label for="confirm_password">Xác nhận mật khẩu</label>
          <input type="password" id="confirm_password"
                 name="confirm_password" placeholder="Gõ lại mật khẩu" required />
        </div>
        
          <?php if ($error): ?>
          <div class="error">
            <?= nl2br(htmlspecialchars($error)) ?>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn-signin">Đăng ký</button>
      </form>
      <p class="login-link">
        Đã có tài khoản? <a href="layout.php?page=login">Đăng nhập ngay</a>
      </p>
    </div>
  </main>
</body>
</html>