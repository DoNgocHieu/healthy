<?php
// filepath: c:\wamp64\www\healthy\views\info.php
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
$pdo = getDb();

// Bảo vệ route: chỉ cho user đã login
if (empty($_SESSION['user_id'])) {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

$userId  = $_SESSION['user_id'];
$errors  = [];
$success = false;

// Get user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy profile
$stmt = $pdo->prepare(
    "SELECT p.avatar, p.fullname, p.gender, p.dob
     FROM profiles p
     WHERE p.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avatar' => null, 'fullname' => null, 'gender' => null, 'dob' => null];

// Merge user info
$user = array_merge($userInfo ?: [], $profile);

// User display name
$displayName = !empty($user['fullname']) ? $user['fullname'] : (!empty($user['username']) ? $user['username'] : 'User');

// Get avatar URL
$avatarUrl = getAvatarUrl($user['avatar']);

// Tách dob thành năm-tháng-ngày
list($dobYear, $dobMonth, $dobDay) = array_pad(explode('-', $user['dob'] ?? ''), 3, '');

// Xử lý POST khi bấm Lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $fullname = trim($_POST['fullname'] ?? '');
    $gender   = $_POST['gender'] ?? '';
    $day      = $_POST['day'] ?? '';
    $month    = $_POST['month'] ?? '';
    $year     = $_POST['year'] ?? '';

    // Validate
    if ($fullname === '') {
        $errors[] = 'Họ & Tên không được để trống.';
    }
    if (!in_array($gender, ['male','female'], true)) {
        $errors[] = 'Vui lòng chọn giới tính.';
    }
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        $errors[] = 'Ngày sinh không hợp lệ.';
    }

    // Xử lý avatar upload
    $avatarPath = null;
    if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'], true)) {
            $errors[] = 'Chỉ chấp nhận định dạng .jpg/.jpeg/.png';
        } elseif ($file['size'] > 1000000) {
            $errors[] = 'Avatar tối đa 1MB.';
        } else {
            $dir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = 'avt_' . $userId . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dir . $filename);
            $avatarPath = 'uploads/avatars/' . $filename;
        }
    }

    // Nếu không có lỗi, lưu/upsert vào DB
    if (empty($errors)) {
        $dob = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $sql = "
            INSERT INTO profiles (user_id, fullname, gender, dob, avatar)
            VALUES (:uid, :fullname, :gender, :dob, :avatar)
            ON DUPLICATE KEY UPDATE
              fullname = VALUES(fullname),
              gender   = VALUES(gender),
              dob      = VALUES(dob),
              avatar   = COALESCE(VALUES(avatar), avatar)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid'      => $userId,
            ':fullname' => $fullname,
            ':gender'   => $gender,
            ':dob'      => $dob,
            ':avatar'   => $avatarPath
        ]);
        $success = true;
    }
}
?>

<link rel="stylesheet" href="../css/info.css">

<div class="container">
  <nav class="menu">
    <div class="menu-profile">
      <img
        src="<?= htmlspecialchars($avatarUrl) ?>"
        class="avatar-preview"
        alt="Avatar">
      <p><?= htmlspecialchars($displayName) ?></p>
    </div>
    <a href="layout.php?page=info" class="active">Thông tin tài khoản</a>
    <a href="layout.php?page=points">Điểm & Voucher</a>
    <a href="layout.php?page=address">Địa chỉ giao hàng</a>
    <a href="layout.php?page=orders">Đơn hàng của tôi</a>
  </nav>

  <section class="main">
    <h2>Thông tin tài khoản</h2>
    <p>Quản lý thông tin tài khoản cá nhân</p>

    <?php if ($success): ?>
      <p class="success">Cập nhật thông tin thành công!</p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <ul class="errors">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form action="?page=info" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Email</label>
        <span><?= htmlspecialchars($user['email']) ?></span>
      </div>

      <div class="form-group">
        <label for="fullname">Họ &amp; Tên</label>
        <input
          type="text"
          id="fullname"
          name="fullname"
          value="<?= htmlspecialchars($user['fullname'] ? $user['fullname'] : $user['username']) ?>"
          required />
      </div>

      <div class="form-group">
        <label>Giới tính</label>
        <label class="radio-inline">
          <input
            type="radio"
            name="gender"
            value="male"
            <?= $user['gender'] === 'male' ? 'checked' : '' ?> />
          Nam
        </label>
        <label class="radio-inline">
          <input
            type="radio"
            name="gender"
            value="female"
            <?= $user['gender'] === 'female' ? 'checked' : '' ?> />
          Nữ
        </label>
      </div>

      <div class="form-group">
        <label>Ngày sinh</label>
        <div class="date-group">
          <select name="day" required>
            <option value="">Ngày</option>
            <?php for ($i = 1; $i <= 31; $i++): ?>
              <option value="<?= $i ?>" <?= $dobDay == $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>

          <select name="month" required>
            <option value="">Tháng</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $dobMonth == $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endfor; ?>
          </select>

          <select name="year" required>
            <option value="">Năm</option>
            <?php for ($y = date('Y'); $y >= 1950; $y--): ?>
              <option value="<?= $y ?>" <?= $dobYear == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="avatar">Avatar</label><br />
        <img
          src="<?= htmlspecialchars($avatarUrl) ?>"
          class="avatar-preview"
          alt="Avatar cuối cùng" /><br />
        <input
          type="file"
          name="avatar"
          id="avatar"
          accept=".jpg,.jpeg,.png" />
        <small>Kích thước tối đa 1MB. Format: .JPEG, .PNG</small>
      </div>

      <button type="submit" class="btn-save">Lưu</button>
    </form>
  </section>
</div>
