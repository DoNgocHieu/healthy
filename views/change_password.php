<style>
    .success-msg {
      background: #e6ffef;
      color: #2d7a46;
      border: 1px solid #b2eacb;
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 6px;
      font-size: .95rem;
      white-space: pre-wrap;
    }
</style>
<?php
require_once dirname(__DIR__) . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$mysqli = getDbConnection();

if (empty($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
}
$userId = $_SESSION['user_id'];

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($old) || empty($new) || empty($confirm)) {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif ($new !== $confirm) {
        $error = 'Mật khẩu mới nhập lại không khớp.';
    } elseif (strlen($new) < 6) {
        $error = 'Mật khẩu mới phải từ 6 ký tự.';
    } else {
        $stmt = $mysqli->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($hash);
        $fetched = $stmt->fetch();
        $needClose = true;
        if ($fetched) {
            if (!password_verify($old, $hash)) {
                $error = 'Mật khẩu cũ không đúng.';
            } elseif (password_verify($new, $hash)) {
                $error = 'Mật khẩu mới không được trùng với mật khẩu cũ.';
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $stmt->close();
                $needClose = false;
                $upd = $mysqli->prepare('UPDATE users SET password = ? WHERE id = ?');
                $upd->bind_param('si', $newHash, $userId);
                $upd->execute();
                $upd->close();
                $success = 'Đổi mật khẩu thành công!';
                echo '<script>window.onload = function() { alert("Đổi mật khẩu thành công!"); }</script>';
            }
        } else {
            $error = 'Không tìm thấy tài khoản.';
        }
        if ($needClose && $stmt) {
            @$stmt->close();
        }
}
        // Chuyển về trang này
        // if ($success) {
        //     header('Location: /healthy/views/layout.php?page=change_password');
        //     exit;
        // }
    }
    // Đóng kết nối
    if ($mysqli) {
        $mysqli->close();
    }
?>
<link rel="stylesheet" href="../css/login.css"/>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <img src="../img/logo.png" alt="Broccoli"/>
      <h1>ĐỔI MẬT KHẨU</h1>
    </div>
    <?php if ($error): ?>
      <div class="error-msg"><?= nl2br(htmlspecialchars($error)) ?></div>
    <?php elseif ($success): ?>
      <div class="success-msg" id="success-msg-popup"><?= $success ?></div>
      <script>
        window.onload = function() {
          var msg = document.getElementById('success-msg-popup');
          if (msg && msg.textContent.trim()) {
            setTimeout(function() {
              alert(msg.textContent.replace(/(<([^>]+)>)/gi, ""));
            }, 200);
          }
        };
      </script>
    <?php endif; ?>
    <?php if (!$success || $success): ?>
    <form method="POST" autocomplete="off">
      <div class="form-group" style="position:relative;">
        <label for="old_password">Mật khẩu hiện tại</label>
        <input type="password" id="old_password" name="old_password" required minlength="6" placeholder="Nhập mật khẩu hiện tại" />
        <button type="button" id="togglePassword1" aria-label="Hiện mật khẩu" class="btn-toggle-pw">
          <span class="icon-open" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0;">🙈</span>
          <span class="icon-closed" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0; display:none;">🙉</span>
        </button>
      </div>
      <div class="form-group" style="position:relative;">
        <label for="new_password">Mật khẩu mới</label>
        <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Nhập mật khẩu mới" />
        <button type="button" id="togglePassword2" aria-label="Hiện mật khẩu" class="btn-toggle-pw">
          <span class="icon-open" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0;">🙈</span>
          <span class="icon-closed" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0; display:none;">🙉</span>
        </button>
      </div>
      <div class="form-group" style="position:relative;">
        <label for="confirm_password">Nhập lại mật khẩu mới</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Nhập lại mật khẩu mới" />
        <button type="button" id="togglePassword3" aria-label="Hiện mật khẩu" class="btn-toggle-pw">
          <span class="icon-open" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0;">🙈</span>
          <span class="icon-closed" style="position: absolute; right: -0.8rem; top: 50%; border: none; cursor: pointer; font-size: 1.5rem; line-height: 1; padding: 0; display:none;">🙉</span>
        </button>
      </div>
      <button type="submit" class="btn-reset" style="background: #248a5a; color: #fff; font-weight: 600; border: none; border-radius: 8px; padding: .75rem; font-size: 1rem; transition: background .2s, transform .1s; cursor: pointer; margin-top: 1rem;">Đổi mật khẩu</button>
      </form>
      </div>
      </div>
<script>
(() => {
  function setupToggle(pwId, btnId) {
    const pwInput = document.getElementById(pwId);
    const btn     = document.getElementById(btnId);
    if (!pwInput || !btn) return;
    const iconOpen   = btn.querySelector('.icon-open');
    const iconClosed = btn.querySelector('.icon-closed');
    btn.addEventListener('click', () => {
      const isHidden = pwInput.type === 'password';
      pwInput.type = isHidden ? 'text' : 'password';
      iconOpen.style.display   = isHidden ? 'none' : '';
      iconClosed.style.display = isHidden ? '' : 'none';
      btn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  }
  setupToggle('old_password', 'togglePassword1');
  setupToggle('new_password', 'togglePassword2');
  setupToggle('confirm_password', 'togglePassword3');
})();
</script>
    <?php endif; ?>
