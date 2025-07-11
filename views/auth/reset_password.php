<?php
require_once __DIR__ . '/../../config/Auth.php';

$auth = new Auth();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: /healthy/views/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Mật khẩu xác nhận không khớp';
    }

    if (empty($errors)) {
        $result = $auth->resetPassword($token, $password);
        if ($result['success']) {
            header('Location: /healthy/views/auth/login.php?message=' . urlencode('Đặt lại mật khẩu thành công'));
            exit;
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
    <title>Đặt lại mật khẩu - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/healthy/css/auth.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Đặt lại mật khẩu</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password"
                                       name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Đặt lại mật khẩu</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
