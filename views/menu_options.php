<?php
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

$sql = "SELECT TT, name, img FROM categories";
$res = $mysqli->query($sql) or die("SQL ERROR (categories): " . $mysqli->error);
$cats = [];
while ($row = $res->fetch_assoc()) {
    $cats[$row['TT']] = $row;
}
$res->free();
$mysqli->close();

$order = ['MM','KV','TG','DH','N','RCQ','MC','C','L','TB'];

$pageMap = [
    'MM' => 'monmoi.php',
    'KV' => 'khaivi.php',
    'TG' => 'trongoi.php',
    'DH' => 'dauhu.php',
    'N'  => 'nam.php',
    'RCQ'=> 'raucuqua.php',
    'MC' => 'monchinh.php',
    'C'  => 'canh.php',
    'L'  => 'lau.php',
    'TB' => 'trabanh.php'
];
?>
<nav class="category-menu">
  <?php foreach ($order as $tt):
      if (!isset($cats[$tt])) continue;
      $cat = $cats[$tt];
      $file = isset($pageMap[$tt]) ? $pageMap[$tt] : 'category.php?tt='.$tt;
      $link = 'layout.php?page=' . pathinfo($file, PATHINFO_FILENAME);
  ?>
    <a href="<?= $link ?>" class="category-item">
      <img src="../img/<?=htmlspecialchars($cat['img'], ENT_QUOTES)?>"
           alt="<?=htmlspecialchars($cat['name'], ENT_QUOTES)?>">
      <span class="label"><?=htmlspecialchars($cat['name'], ENT_QUOTES)?></span>
    </a>
  <?php endforeach; ?>
</nav>
<script>
function showItemModalById(itemId) {
  var overlay = document.getElementById('itemModalOverlay');
  var contentDiv = document.getElementById('itemModalContent');
  contentDiv.innerHTML = '<div style="text-align:center;padding:2rem 1rem;">Đang tải...</div>';
  overlay.style.display = 'flex';
  fetch('item.php?id=' + itemId + '&ajax=1')
    .then(res => res.text())
    .then(html => contentDiv.innerHTML = html)
    .catch(() => contentDiv.innerHTML = 'Lỗi tải dữ liệu!');
  document.onkeydown = function(e) { if (e.key === 'Escape') closeItemModal(); }
  overlay.onclick = function(e) { if(e.target === overlay) closeItemModal(); }
}
function closeItemModal() {
  var overlay = document.getElementById('itemModalOverlay');
  overlay.style.display = 'none';
  document.onkeydown = null;
  overlay.onclick = null;
}
</script>
