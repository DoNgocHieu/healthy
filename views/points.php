<?php
// views/point.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
$pdo = getDb();

// Bảo vệ route
if (empty($_SESSION['user_id'])) {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}
$userId = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thông tin điểm
$stmt = $pdo->prepare(
    "SELECT p.points, p.avatar, p.fullname
     FROM profiles p
     WHERE p.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['points' => 0, 'avatar' => null, 'fullname' => null];

// Merge user info
$user = array_merge($userInfo ?: [], $profile);

// User display name
$displayName = !empty($user['fullname']) ? $user['fullname'] : (!empty($user['username']) ? $user['username'] : 'User');

// Get avatar URL
$avatarUrl = getAvatarUrl($user['avatar']);

// Lấy danh sách voucher có sẵn
$stmt = $pdo->prepare(
    "SELECT id, code, description, points_required, expires_at
     FROM vouchers
     WHERE active = 1
       AND (expires_at IS NULL OR expires_at > NOW())
     ORDER BY points_required ASC"
);
$stmt->execute();
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="<?= getAssetUrl('css/points.css') ?>">

<div class="container">
  <nav class="menu">
    <div class="menu-profile">
      <img
        src="<?= htmlspecialchars($avatarUrl) ?>"
        class="avatar-preview"
        alt="Avatar">
      <p><?= htmlspecialchars($displayName) ?></p>
    </div>
    <a href="layout.php?page=info">Thông tin tài khoản</a>
    <a href="layout.php?page=points" class="active">Điểm & Voucher</a>
    <a href="layout.php?page=address">Địa chỉ giao hàng</a>
    <a href="layout.php?page=orders">Đơn hàng của tôi</a>
  </nav>

  <div class="content">
    <section class="points-section">
      <h2>Điểm tích lũy</h2>
      <div class="points-card">
        <div class="points-balance">
          <span class="points-amount"><?= number_format($user['points']) ?></span>
          <span class="points-label">điểm</span>
        </div>
        <p class="points-info">
          Bạn có thể sử dụng điểm để đổi các voucher giảm giá hấp dẫn!
        </p>
      </div>
    </section>

    <section class="vouchers-section">
      <h2>Voucher có sẵn</h2>
      <div class="vouchers-grid">
        <?php foreach ($vouchers as $voucher): ?>
          <div class="voucher-card">
            <div class="voucher-info">
              <h3><?= htmlspecialchars($voucher['code']) ?></h3>
              <p><?= htmlspecialchars($voucher['description']) ?></p>
              <?php if ($voucher['expires_at']): ?>
                <small>Hết hạn: <?= date('d/m/Y', strtotime($voucher['expires_at'])) ?></small>
              <?php endif; ?>
            </div>
            <div class="voucher-points">
              <span><?= number_format($voucher['points_required']) ?> điểm</span>
              <button class="btn btn-redeem" <?= $user['points'] >= $voucher['points_required'] ? '' : 'disabled' ?>>
                Đổi ngay
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<script>
document.querySelectorAll('.btn-redeem').forEach(button => {
    button.addEventListener('click', function() {
        if (confirm('Bạn có chắc muốn đổi voucher này?')) {
            // Thêm logic đổi voucher ở đây
            alert('Chức năng đang được phát triển!');
        }
    });
});
</script>
