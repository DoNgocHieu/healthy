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

<script>
function showItemModalById(itemId) {
  var overlay = document.getElementById('itemModalOverlay');
  var contentDiv = document.getElementById('itemModalContent');
  contentDiv.innerHTML = '<div style="text-align:center;padding:2rem 1rem;">Đang tải...</div>';
  overlay.style.display = 'flex';
  fetch('item.php?id=' + itemId + '&ajax=1')
    .then(res => res.text())
    .then(html => {
      contentDiv.innerHTML = html;

      // Khởi tạo favorites cho modal content
      if (window.favoritesManager) {
        window.favoritesManager.initializeFavorites();
      }

      // Force run script tags in loaded content
      var scripts = contentDiv.querySelectorAll('script');
      scripts.forEach(script => {
        var newScript = document.createElement('script');
        newScript.textContent = script.textContent;
        document.head.appendChild(newScript);
        document.head.removeChild(newScript);
      });
    })
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

