<?php
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<link rel="stylesheet" href="../css/contact.css">

<div class="contact-container">
  <h1>Liên hệ <?= htmlspecialchars($settings['site_name'] ?? 'BROCCOLI') ?></h1>
  <div class="contact-info">
    <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($settings['address'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($settings['email'] ?? '') ?></p>
    <p><strong>Điện thoại:</strong> <?= htmlspecialchars($settings['phone_number'] ?? '') ?></p>
    <p><strong>Giờ hoạt động:</strong> <?= htmlspecialchars($settings['working_hours'] ?? '') ?></p>
    <p><strong>Facebook:</strong> <a href="<?= htmlspecialchars($settings['facebook_url'] ?? '#') ?>" target="_blank"><?= htmlspecialchars($settings['facebook_url'] ?? '') ?></a></p>
    <p><strong>Instagram:</strong> <a href="<?= htmlspecialchars($settings['instagram_url'] ?? '#') ?>" target="_blank"><?= htmlspecialchars($settings['instagram_url'] ?? '') ?></a></p>
    <p><strong>Thông tin giao hàng:</strong> <?= htmlspecialchars($settings['delivery_info'] ?? '') ?></p>
  </div>
  <form class="contact-form" method="post">
    <h2>Gửi tin nhắn cho chúng tôi</h2>
    <input type="text" name="name" placeholder="Họ tên" required>
    <input type="email" name="email" placeholder="Email" required>
    <textarea name="message" placeholder="Nội dung" rows="5" required></textarea>
    <button type="submit">Gửi liên hệ</button>
  </form>
</div>