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

// Lấy tổng điểm từ bảng points_history
$stmt = $pdo->prepare('SELECT SUM(change_amount) AS total_points FROM points_history WHERE user_id = ?');
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalPoints = (int)($row['total_points'] ?? 0);

// Lấy danh sách voucher user đã đổi
$stmt = $pdo->prepare('SELECT voucher_id FROM voucher_usage WHERE user_id = ?');
$stmt->execute([$userId]);
$boughtVouchers = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'voucher_id');
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
          <span class="points-amount"><?= number_format($totalPoints) ?></span>
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
        <?php foreach ($vouchers as $v): ?>
          <div class="voucher-card">
            <div class="voucher-info">
              <h3><?= htmlspecialchars($v['code']) ?></h3>
              <p><?= htmlspecialchars($v['description']) ?></p>
              <small>
                <?= $v['expires_at'] ? 'Hết hạn: ' . date('d/m/Y', strtotime($v['expires_at'])) : 'Không giới hạn' ?>
              </small>
            </div>
            <div class="voucher-points">
              <span><?= number_format($v['points_required']) ?> điểm</span>
              <button
                class="btn-redeem"
                <?php
                    if (in_array($v['id'], $boughtVouchers)) {
                        echo 'disabled style="background:#ccc;cursor:not-allowed"';
                    } elseif ($totalPoints < $v['points_required']) {
                        echo 'disabled';
                    }
                ?>
              >
                <?= in_array($v['id'], $boughtVouchers) ? 'Đã nhận' : 'Đổi ngay' ?>
              </button>
            </div>
            <input type="hidden" name="voucher_id" value="<?= $v['id'] ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<script>
document.querySelectorAll('.btn-redeem').forEach(button => {
    button.addEventListener('click', function() {
        const voucherId = this.closest('.voucher-card').querySelector('input[name="voucher_id"]').value;
        if (confirm('Bạn có chắc muốn đổi voucher này?')) {
            fetch('/healthy/actions/redeem_voucher.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'voucher_id=' + voucherId
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    });
});
</script>
