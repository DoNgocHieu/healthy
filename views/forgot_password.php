<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$mysqli = getDbConnection();

/**
 * Gửi email đặt lại mật khẩu sử dụng PHPMailer
 * @param string $to Địa chỉ email người nhận
 * @param string $subject Tiêu đề email
 * @param string $body Nội dung email (HTML)
 * @return bool Thành công hay thất bại
 */
function sendResetMail($to, $subject, $body) {
    // Đường dẫn tới autoload PHPMailer (điều chỉnh nếu cần)
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP 
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dangthixuan2272004@gmail.com';  
        $mail->Password   = 'otyu zebp tbhq irpx';     
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('dangthixuan2272004@gmail.com', 'Broccoli Team'); 
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

// Lấy & xóa thông báo
$error   = $_SESSION['reset_error']   ?? '';
$success = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Validate
    if (empty($email)) {
        $error = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        // Kiểm tra tồn tại user
        $stmt = $mysqli->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Không tiết lộ
            $success = 'Nếu email này đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu cho bạn.';
        } else {
            $stmt->bind_result($userId);
            $stmt->fetch();
            $stmt->close();

            // Tạo token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            // Lưu vào password_resets
            $ins = $mysqli->prepare(
                "INSERT INTO password_resets (user_id, token, expires_at)
                 VALUES (?, ?, ?)"
            );
            $ins->bind_param('iss', $userId, $token, $expiresAt);
            $ins->execute();
            $ins->close();

            // Tạo link
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
            $resetLink = sprintf(
                '%s://%s%s/reset-password.php?token=%s',
                $protocol,
                $_SERVER['HTTP_HOST'],
                dirname($_SERVER['PHP_SELF']),
                $token
            );

            // Chuẩn bị mail
            $subject = 'Đặt lại mật khẩu Broccoli';
            $body = "Chào bạn,<br><br>"
                  ."Vui lòng bấm vào link sau để đặt lại mật khẩu (hết hạn sau 1 giờ):<br><br>"
                  ."<a href='$resetLink'>$resetLink</a><br><br>"
                  ."Nếu bạn không yêu cầu, hãy bỏ qua email này.<br><br>"
                  ."Broccoli Team";

            // Gửi mail bằng PHPMailer
            if (sendResetMail($email, $subject, $body)) {
                $success = 'Nếu email này đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu cho bạn.';
            } else {
                error_log("Failed to send reset email to $email");
                $error = 'Hệ thống đang có vấn đề, vui lòng thử lại sau.';
            }
        }
    }
    $_SESSION['reset_error']   = $error;
    $_SESSION['reset_success'] = $success;
    header('Location: forgot_password.php');
    exit;
}
?>
  <div class="auth-container">
    <div class="forgot-box">
      <div class="forgot-logo">
        <img src="../img/logo.png" alt="Broccoli"/>
        <h1>QUÊN MẬT KHẨU</h1>
      </div>

      <?php if ($error): ?>
        <div class="error-msg"><?= nl2br(htmlspecialchars($error)) ?></div>
      <?php elseif ($success): ?>
        <div class="success-msg"><?= nl2br(htmlspecialchars($success)) ?></div>
      <?php endif; ?>

      <?php if (! $success): ?>
      <form action="" method="POST" autocomplete="off">
        <div class="form-group">
          <label for="email">Email đăng ký</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Nhập email của bạn"
            required
          />
        </div>
        <button type="submit" class="btn-reset">Gửi link đặt lại</button>
      </form>
      <?php endif; ?>

      <div class="back-link">
        <a href="layout.php?page=login">&larr; Quay lại đăng nhập</a>
      </div>
    </div>
  </div>

  <style>

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: url('../img/fgpw.png') no-repeat center center fixed;
      background-size: cover;
      color: #333;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .auth-container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 4rem 1rem;
    }
    .forgot-box {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.06);
      width: 100%;
      max-width: 400px;
      padding: 2rem 1.5rem;
    }
    .forgot-logo {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .forgot-logo img {
      width: 60px;
      margin-bottom: .5rem;
    }
    .forgot-logo h1 {
      font-size: 1.8rem;
      color: #248a5a;
      font-weight: 600;
    }
    .error-msg, .success-msg {
      margin-bottom: 1rem;
      padding: .8rem;
      border-radius: 6px;
      font-size: .95rem;
      white-space: pre-wrap;
    }
    .error-msg {
      background: #ffe6e6;
      color: #cc0000;
      border: 1px solid #ffcccc;
    }
    .success-msg {
      background: #e6ffef;
      color: #2d7a46;
      border: 1px solid #b2eacb;
    }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label {
      display: block;
      margin-bottom: .4rem;
      color: #444;
      font-weight: 500;
    }
    .form-group input {
      width: 100%;
      padding: .75rem 1rem;
      border: 2px solid #c8e8dd;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color .2s, box-shadow .2s;
    }
    .form-group input:focus {
      outline: none;
      border-color: #248a5a;
      box-shadow: 0 0 0 3px rgba(36,138,90,0.15);
    }
    .btn-reset {
      display: block;
      width: 100%;
      padding: .75rem;
      background: #248a5a;
      color: #fff;
      font-size: 1rem;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background .2s, transform .1s;
    }
    .btn-reset:hover  { background: #1f7e50; }
    .btn-reset:active { transform: scale(0.98); }
    .back-link {
      text-align: center;
      margin-top: 1.5rem;
    }
    .back-link a {
      color: #248a5a;
      text-decoration: none;
      font-weight: 500;
    }
    .back-link a:hover { text-decoration: underline; }
  </style>