<?php
require_once __DIR__ . '/../config/config.php';
$pdo = getDb();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<link rel="stylesheet" href="../css/footer.css" />
<footer class="footer">
  <div class="footer-container">
    <div class="footer-col col-left">
      <h4><b><?= htmlspecialchars($settings['site_name'] ?? 'BROCCOLI') ?></b></h4>
      <p>
        <?= htmlspecialchars($settings['site_slogan'] ?? 'Giao hàng mọi nơi trong thời gian nhanh nhất, hình ảnh thực 100%, đảm bảo đúng như mô tả. Liên hệ qua facebook khi cần tư vấn.') ?>
      </p>
    </div>
    <div class="footer-col col-center">
      <h4><b>THÔNG TIN LIÊN HỆ</b></h4>
      <ul>
        <li><i class="fa fa-utensils"></i><?= htmlspecialchars($settings['site_name'] ?? 'BROCCOLI') ?></li>
        <li><i class="fa fa-map-marker-alt"></i><?= htmlspecialchars($settings['address'] ?? '') ?></li>
        <li><i class="fa fa-envelope"></i><?= htmlspecialchars($settings['email'] ?? '') ?></li>
        <li><i class="fa fa-phone"></i><?= htmlspecialchars($settings['phone_number'] ?? '') ?></li>
        <li><i class="fa fa-phone-square-alt"></i>Hotline: <?= htmlspecialchars($settings['phone_number'] ?? '') ?></li>
      </ul>
    </div>
     <div class="footer-col col-right">
      <h4><b>ADMIN BROCCOLI</b></h4>
      <div class="fb-page-wrapper">
        <div class="fb-page"
             data-href="https://www.facebook.com/profile.php?id=100076958486914"
             data-tabs="timeline"
             data-width="300"
             data-height="200"
             data-small-header="false"
             data-adapt-container-width="true"
             data-hide-cover="false"
             data-show-facepile="true">
          <blockquote cite="https://www.facebook.com/profile.php?id=100076958486914" class="fb-xfbml-parse-ignore">
            <a href="https://www.facebook.com/profile.php?id=100076958486914">ADMIN BROCCOLI</a>
            
          </blockquote>
          
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    ©2025 <?= htmlspecialchars($settings['site_name'] ?? 'BROCCOLI') ?>. All Rights Reserved.
  </div>
</footer>
