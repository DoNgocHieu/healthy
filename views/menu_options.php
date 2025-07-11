<?php
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

// Lấy tất cả danh mục có ảnh
$sql = "SELECT TT, name, img FROM categories WHERE img IS NOT NULL AND img != ''";
$res = $mysqli->query($sql) or die("SQL ERROR (categories): " . $mysqli->error);
$cats = [];
while ($row = $res->fetch_assoc()) {
    $cats[] = $row;
}
$res->free();
$mysqli->close();
?>
<nav class="category-menu">
  <?php foreach ($cats as $cat):
      // Tạo link động theo TT, ví dụ: layout.php?page=kv
      $link = 'layout.php?page=monmoi&tt=' . strtoupper($cat['TT']);
  ?>
    <a href="<?= $link ?>" class="category-item">
      <img src="/healthy/<?=htmlspecialchars($cat['img'], ENT_QUOTES)?>"
           alt="<?=htmlspecialchars($cat['name'], ENT_QUOTES)?>">
      <span class="label"><?=htmlspecialchars($cat['name'], ENT_QUOTES)?></span>
    </a>
  <?php endforeach; ?>
</nav>
