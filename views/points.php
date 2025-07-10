<?php
// views/point.php
// 1. Khởi session và bảo vệ route
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
if (empty($_SESSION['user_id'])) {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}
$userId = $_SESSION['user_id'];

// Lấy thông tin user + avatar + điểm
$stmt = $pdo->prepare(
    "SELECT p.points, p.avatar, p.fullname
     FROM profiles p
     WHERE p.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['points'=>0,'avatar'=>null,'fullname'=>''];
$points    = (int)$user['points'];
$avatarUrl = $user['avatar']
    ? '/healthy/' . ltrim($user['avatar'], '/')
    : '/healthy/img/default-avatar.png';

// Lấy danh sách voucher còn hiệu lực
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

  <link rel="stylesheet" href="../css/points.css"/>
  <div class="container">
  <nav class="menu">
    <div class="menu-profile">
      <img
        src="<?= htmlspecialchars($avatarUrl) ?>"
        class="avatar-preview"
        alt="Avatar">
      <p><?= htmlspecialchars($user['fullname'] ?: $_SESSION['username']) ?></p>
    </div>
    <a href="layout.php?page=info" class="active">Thông tin tài khoản</a>
    <a href="layout.php?page=points">Điểm & voucher</a>
    <a href="layout.php?page=address">Địa chỉ giao hàng</a>
    <a href="layout.php?page=orders">Đơn hàng hiện tại</a>
  </nav>

    <!-- Nội dung chính -->
    <section class="main">
      <h2>Điểm tích lũy & Voucher</h2>
      <div class="points-balance">
        Bạn đang có: <span class="points"><?= $points ?> điểm</span>
      </div>

      <h3>Danh sách Voucher</h3>
      <div class="voucher-list">
        <?php if (empty($vouchers)): ?>
          <p>Hiện tại chưa có voucher nào.</p>
        <?php endif; ?>

        <?php foreach ($vouchers as $v): ?>
          <div class="voucher-item">
            <h4><?= htmlspecialchars($v['description']) ?></h4>
            <p>
              Mã: <strong><?= htmlspecialchars($v['code']) ?></strong><br/>
              Tiêu hao: <?= (int)$v['points_required'] ?> điểm
              <?php if ($v['expires_at']): ?>
                <br><small>Hạn dùng đến: <?= htmlspecialchars($v['expires_at']) ?></small>
              <?php endif; ?>
            </p>
            <form method="post" action="layout.php?page=redeem" class="redeem-form">
              <input type="hidden" name="voucher_id" value="<?= (int)$v['id'] ?>">
              <button type="submit" class="btn-redeem" <?= $points < (int)$v['points_required'] ? 'disabled' : '' ?>>Đổi ngay</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>