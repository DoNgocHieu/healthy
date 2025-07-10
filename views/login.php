<?php
if (isset($_SESSION['user_id'])) {
    $dest = $_SESSION['after_login_redirect'] ?? 'layout.php?page=home';
    unset($_SESSION['after_login_redirect']);
    header("Location: $dest");
    exit;
}

// Tiếp tục hiển thị form...
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<link rel="stylesheet" href="../css/login.css"/>

  <div class="login-page">
    <div class="login-box">
      <div class="login-logo">
        <img src="../img/logo.png" alt="Broccoli"/>
        <h1>ĐĂNG NHẬP</h1>
      </div>

      <form action="../config/authenticate.php" method="POST" autocomplete="on">
        <div class="form-group">
          <label for="username">Tên đăng nhập / Email</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Nhập username hoặc email"
            required
            autocomplete="username"
          />
        </div>

        <div class="form-group" style="position:relative;">
          <label for="password">Mật khẩu</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Nhập mật khẩu"
            required
            autocomplete="current-password"
          />
          <button
            type="button"
            id="togglePassword"
            aria-label="Hiện mật khẩu"
            class="btn-toggle-pw"
          >
            <span class="icon-open"style="position: absolute;
            right: -0.8rem;
            top: 50%;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0;">🙈</span>

            <span class="icon-closed" style="position: absolute;
            right: -0.8rem;
            top: 50%;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0;
            display:none;">🙉</span>
          </button>
        </div>

        <div class="form-options">
          <label>
            <input type="checkbox" name="remember_me" value="1"/> Ghi nhớ đăng nhập
          </label>
          <a href="layout.php?page=forgot_password">Quên mật khẩu?</a>
        </div>

        <button type="submit" class="btn-login">Đăng nhập</button>
      </form>
      <?php if ($error): ?>
        <div class="error-msg">
          <?= nl2br(htmlspecialchars($error)) ?>
        </div>
      <?php endif; ?>
      <p class="signup">
        Bạn chưa có tài khoản?
        <a href="layout.php?page=signin">Đăng ký ngay</a>
      </p>
    </div>
  </div>
<script>
(() => {
  const pwInput = document.getElementById('password');
  const btn     = document.getElementById('togglePassword');
  const iconOpen   = btn.querySelector('.icon-open');
  const iconClosed = btn.querySelector('.icon-closed');

  btn.addEventListener('click', () => {
    const isHidden = pwInput.type === 'password';
    pwInput.type = isHidden ? 'text' : 'password';
    iconOpen.style.display   = isHidden ? 'none' : '';
    iconClosed.style.display = isHidden ? '' : 'none';
    btn.setAttribute(
      'aria-label',
      isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
    );
  });
})();
</script>