<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $pdo;

    public function __construct() {
        $this->pdo = getDb();
    }

    public function register($username, $email, $password, $phone = null) {
        try {
            // Kiểm tra email đã tồn tại
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email đã được sử dụng'];
            }

            // Kiểm tra username đã tồn tại
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
            }

            // Mã hóa mật khẩu
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Thêm user mới
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password, phone, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$username, $email, $hashedPassword, $phone]);

            return ['success' => true, 'message' => 'Đăng ký thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, email, password, role, status
                 FROM users WHERE email = ?"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'Email không tồn tại'];
            }

            if ($user['status'] === 'blocked') {
                return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Mật khẩu không đúng'];
            }

            // Tạo session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function forgotPassword($email) {
        try {
            // Kiểm tra email tồn tại
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Email không tồn tại'];
            }

            // Tạo token reset password
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Lưu token
            $stmt = $this->pdo->prepare(
                "INSERT INTO password_resets (email, token, expires_at)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$email, $token, $expires]);

            // Gửi email
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your-email@gmail.com'; // Cập nhật email
                $mail->Password = 'your-password'; // Cập nhật password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('your-email@gmail.com', 'Healthy Food');
                $mail->addAddress($email);

                $resetLink = "http://localhost/healthy/reset-password.php?token=" . $token;

                $mail->isHTML(true);
                $mail->Subject = 'Đặt lại mật khẩu';
                $mail->Body = "Để đặt lại mật khẩu, vui lòng click vào link sau:<br>
                             <a href='{$resetLink}'>{$resetLink}</a><br><br>
                             Link sẽ hết hạn sau 1 giờ.";

                $mail->send();
                return ['success' => true, 'message' => 'Đã gửi email hướng dẫn đặt lại mật khẩu'];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Không thể gửi email: ' . $mail->ErrorInfo];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function resetPassword($token, $newPassword) {
        try {
            // Kiểm tra token hợp lệ và chưa hết hạn
            $stmt = $this->pdo->prepare(
                "SELECT email, is_used FROM password_resets
                 WHERE token = ? AND expires_at > NOW()
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                return ['success' => false, 'message' => 'Token không hợp lệ hoặc đã hết hạn'];
            }

            if ($reset['is_used']) {
                return ['success' => false, 'message' => 'Token đã được sử dụng'];
            }

            // Cập nhật mật khẩu
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare(
                "UPDATE users SET password = ? WHERE email = ?"
            );
            $stmt->execute([$hashedPassword, $reset['email']]);

            // Đánh dấu token đã sử dụng
            $stmt = $this->pdo->prepare(
                "UPDATE password_resets SET is_used = 1 WHERE token = ?"
            );
            $stmt->execute([$token]);

            return ['success' => true, 'message' => 'Đặt lại mật khẩu thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Đăng xuất thành công'];
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, email, role, points, avatar_url
                 FROM users WHERE id = ?"
            );
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
