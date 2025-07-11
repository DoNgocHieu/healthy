<?php
require_once __DIR__ . '/../../config/Auth.php';

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    $errors = [];

    if (empty($email)) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }

    if (empty($errors)) {
        $result = $auth->forgotPassword($email);
        if ($result['success']) {
            $message = 'Đã gửi email hướng dẫn đặt lại mật khẩu';
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/healthy/css/auth.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Quên mật khẩu</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Gửi link đặt lại mật khẩu</button>
                        </form>

                        <div class="text-center mt-3">
                            <p><a href="/healthy/views/auth/login.php">Quay lại đăng nhập</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
