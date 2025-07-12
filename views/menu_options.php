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
<style>
.category-menu {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 1rem 0;
  scrollbar-width: thin;
  scrollbar-color: #6BBF59 #e0f7e9;
  width: auto;
  flex-wrap: nowrap; 
}
.category-menu::-webkit-scrollbar {
  height: 8px;
}
.category-menu::-webkit-scrollbar-thumb {
  background: #6BBF59;
  border-radius: 4px;
}
.category-menu::-webkit-scrollbar-track {
  background: #e0f7e9;
}
.category-item {
  flex: 0 0 110px;
  min-width: 110px;
  text-align: center;
}
.category-scroll-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: #fff;
  border: 1px solid #6BBF59;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  cursor: pointer;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}
.category-scroll-btn.left { left: 0; }
.category-scroll-btn.right { right: 0; }
.category-menu-wrap { position: relative; }
</style>
<?php $showSlider = count($cats) > 10; ?>
<div class="category-menu-wrap">
 
  <nav class="category-menu" id="categoryMenu">
    <?php foreach ($cats as $cat): ?>
      <?php
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
 
</div>

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

function scrollCategoryMenu(dir) {
  const menu = document.getElementById('categoryMenu');
  menu.scrollBy({ left: dir * 220, behavior: 'smooth' });
}
</script>

