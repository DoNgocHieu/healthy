<?php
session_start();
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

if (isset($_SESSION['user_id'])) {
    $stmt = $mysqli->prepare("SELECT banned FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($banned);
    $stmt->fetch();
    $stmt->close();

    if (!empty($banned)) {
        session_destroy();
        header('Location: /healthy/views/layout.php?page=login');
        exit;
    }
}

require_once __DIR__ . '/../config/SiteSettingsManager.php';

$page = $_GET['page'] ?? 'home';

// Load site settings
$settingsManager = new SiteSettingsManager();
$siteSettings = [];
try {
    $allSettings = $settingsManager->getSettingsByGroup();
    foreach ($allSettings as $setting) {
        $siteSettings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    // Fallback values nếu không load được settings
    $siteSettings = [
        'site_name' => 'BROCCOLI',
        'site_logo' => '../img/logo.png',
        'site_slogan' => 'Healthy Food For Life'
    ];
}

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'load') {
  header('Content-Type: application/json; charset=utf-8');

  $userId = $_SESSION['user_id'] ?? null;
  if (!$userId) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT item_id, quantity
      FROM cart_items
     WHERE user_id = ?
       AND is_deleted = 0
  ");
  $stmt->execute([$userId]);

  $cart = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cart[$row['item_id']] = [
      'qty'       => (int)$row['quantity'],
      'stock_qty' => (int)$row['quantity']  // hoặc nếu muốn lấy stock hiện tại thì query items.quantity
    ];
  }

  echo json_encode(['status'=>'ok','cart'=>$cart]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    // Kiểm tra đăng nhập
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['status' => 'not_logged_in']);
        exit;
    }

    // Nhận dữ liệu từ client
    $itemId = intval($_POST['id'] ?? 0);
    $qty    = max(0, intval($_POST['qty'] ?? 0));

    if ($qty > 0) {
        // Cố gắng UPDATE trước
        // Kiểm tra xem item đã có trong giỏ hàng chưa
        $check = $pdo->prepare("SELECT id FROM cart_items WHERE user_id = ? AND item_id = ?");
        $check->execute([$userId, $itemId]);

        $upd = null;
        if ($check->fetch()) {
            // Nếu đã có thì UPDATE
            $upd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND item_id = ?");
            $upd->execute([$qty, $userId, $itemId]);
        } else {
            // Nếu chưa có thì INSERT
            $ins = $pdo->prepare("INSERT INTO cart_items (user_id, item_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $ins->execute([$userId, $itemId, $qty]);
        }

        // Nếu có đối tượng $upd và không có bản ghi nào được cập nhật, INSERT mới
        if ($upd && $upd->rowCount() === 0) {
            $ins = $pdo->prepare(
                "INSERT INTO cart_items (user_id, item_id, quantity)
                 VALUES (:uid, :iid, :qty)"
            );
            $ins->execute([':uid'=>$userId, ':iid'=>$itemId, ':qty'=>$qty]);
        }
    } else {
        // qty = 0: đánh dấu xoá/giảm về 0
        $del = $pdo->prepare(
            "UPDATE cart_items
                SET quantity = 0, is_deleted = 1
              WHERE user_id = :uid AND item_id = :iid"
        );
        $del->execute([':uid'=>$userId, ':iid'=>$itemId]);
    }

    // Tính giá trị line và tổng giỏ
    $priceStmt = $pdo->prepare("SELECT price FROM items WHERE id = ?");
    $priceStmt->execute([$itemId]);
    $unitPrice = $priceStmt->fetchColumn() ?: 0;
    $lineTotal = $unitPrice * $qty;

    $totalStmt = $pdo->prepare(
        "SELECT SUM(ci.quantity * i.price) FROM cart_items ci
         JOIN items i ON ci.item_id = i.id
         WHERE ci.user_id = ?"
    );
    $totalStmt->execute([$userId]);
    $grandTotal = $totalStmt->fetchColumn() ?: 0;

    // Trả về JSON
    echo json_encode([
        'status'      => 'ok',
        'line_total'  => number_format($lineTotal, 0, ',', '.') . ' đ',
        'grand_total' => number_format($grandTotal, 0, ',', '.') . ' đ',
        'stock_qty'   => $qty
    ]);
    exit;
}
?>
<?php if ($page !== 'admin'): ?>
  <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($siteSettings['site_logo'] ?? '../img/logo.png'); ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/site.css" />
  <link rel="stylesheet" href="../css/menu_options.css" />

  <script src="<?= BASE_URL ?>/js/qty.js" defer></script>
  <script src="../js/favorites.js" defer></script>
  <script defer src="../js/site.js"></script>
    <div id="fb-root"></div>
  <script async defer crossorigin="anonymous"
    src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v16.0">
  </script>
<header class="navbar">

  <div class="logo">
    <img src="<?php echo htmlspecialchars($siteSettings['site_logo'] ?? '../img/logo.png'); ?>"
         alt="<?php echo htmlspecialchars($siteSettings['site_name'] ?? 'Broccoli'); ?> Logo"
         class="logo-img" />
    <span><?php echo htmlspecialchars($siteSettings['site_name'] ?? 'BROCCOLI'); ?></span>
  </div>
  <nav class="main-nav">
    <ul>
      <li><a href="layout.php?page=home">Trang chủ</a></li>
      <li><a href="layout.php?page=monmoi">Menu</a></li>
      <li><a href="layout.php?page=vct">Về chúng tôi</a></li>
      <li><a href="layout.php?page=dl">Dưỡng lành</a></li>
      <li><a href="layout.php?page=lh">Liên hệ</a></li>
    </ul>
  </nav>
  <div class="top-search-icons">
    <form action="#" method="GET" class="search-form">
      <input type="text" name="q" id="searchInput" placeholder="Search" autocomplete="off" />
    </form>

    <!-- Modal kết quả tìm kiếm -->
    <div id="searchModal" class="search-modal-bg" style="display:none;">
      <div class="search-modal-box">
        <button class="modal-close" onclick="closeSearchModal()"></button>
        <div id="searchResults"></div>
      </div>
    </div>
    <a href="layout.php?page=favorites" class="icon-link" title="Favorites"><i class="fa fa-heart"></i></a>
    <a href="layout.php?page=cart" class="icon-link" title="Cart"><i class="fa fa-shopping-cart"></i></a>
    <div class="user-menu">
      <i class="fa fa-user user-icon"></i>
      <ul class="user-dropdown">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="/healthy/views/layout.php?page=admin&section=dashboard">Quản lý</a></li>
          <?php endif; ?>
          <li><a href="/healthy/views/layout.php?page=info">Thông tin</a></li>
          <li><a href="/healthy/views/layout.php?page=logout">Đăng xuất</a></li>
        <?php else: ?>
          <li><a href="/healthy/views/layout.php?page=login">Đăng nhập</a></li>
          <li><a href="/healthy/views/layout.php?page=signin">Đăng ký</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</header>
<?php endif; ?>

<?php if ($page !== 'admin'): ?>
<main>
<script>
function showSearchModal(resultsHtml) {
  document.getElementById('searchResults').innerHTML = resultsHtml;
  document.getElementById('searchModal').style.display = 'block';
  positionSearchModal();
}
function closeSearchModal() {
  document.getElementById('searchModal').style.display = 'none';
}
function positionSearchModal() {
  const input = document.getElementById('searchInput');
  const modal = document.getElementById('searchModal');
  const rect = input.getBoundingClientRect();
  modal.style.left = rect.left + 'px';
  modal.style.top = (rect.bottom + window.scrollY) + 'px';
  modal.style.width = input.offsetWidth + 'px';
}
let searchTimeout = null;
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function(e) {
  const q = searchInput.value.trim();
  clearTimeout(searchTimeout);
  if (!q) {
    closeSearchModal();
    return;
  }
  searchTimeout = setTimeout(() => {
    fetch('/healthy/api/search.php?q=' + encodeURIComponent(q))
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success' && data.items.length > 0) {
          let html = '<ul class="search-list">';
          data.items.forEach(item => {
            html += `<li>
              <img src="../img/${item.image_url}" alt="${item.name}" />
              <div class="search-info">
                <div class="search-title">${item.name}</div>
                <div class="search-price">${new Intl.NumberFormat('vi-VN').format(item.price)} đ</div>
                <div class="search-actions">
                  <button class="add-to-cart-btn" onclick="event.stopPropagation(); if(window.isLoggedIn){addToCart(${item.id})}else{alert('Vui lòng đăng nhập để thêm vào giỏ!');}">
                    <i class="fa-solid fa-bag-shopping"></i>
                  </button>
                  <button class="favorite-btn" data-item-id="${item.id}" title="Thêm vào yêu thích" onclick="event.stopPropagation(); if(window.isLoggedIn){window.favoritesManager && window.favoritesManager.toggleFavorite(${item.id})}else{alert('Vui lòng đăng nhập để thêm vào yêu thích!');}">
                    <i class="fa-regular fa-heart"></i>
                  </button>
                </div>
              </div>
            </li>`;
          });
          html += '</ul>';
          showSearchModal(html);
        } else {
          showSearchModal('<p>Không tìm thấy kết quả phù hợp.</p>');
        }
      })
      .catch(() => showSearchModal('<p>Lỗi kết nối hoặc không tìm thấy kết quả.</p>'));
  }, 350);
});
searchInput.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSearchModal();
  }
});
document.addEventListener('mousedown', function(e) {
  const modal = document.getElementById('searchModal');
  const input = document.getElementById('searchInput');
  if (modal.style.display === 'block' && !modal.contains(e.target) && e.target !== input) {
    closeSearchModal();
  }
});
</script>

<?php endif; ?>
  <?php
  $allowPages = [
    'login','signin','monmoi','khaivi','trongoi','dauhu','nam',
    'raucuqua','monchinh','canh','lau','trabanh',
    'item','vct','lh','home','hd1','cart','dl','BBCX','info','logout','forgot_password','points','address','checkout','footer',
    'order_confirm','order_success','orders','admin','favorites'
  ];

  if (in_array($page, $allowPages) && file_exists($page . '.php')) {
    // Nếu là admin page, chỉ include và không thêm footer
    if ($page === 'admin') {
      include $page . '.php';
      exit; // Dừng ngay để không load footer
    } else {
      include $page . '.php';
      if ($page === 'home' && file_exists('hd1.php')) include 'hd1.php';
    }
  }

  ?>
<?php if ($page !== 'admin'): ?>
   <?php include __DIR__ . '/footer.php'; ?>
</main>
<script>
  window.BASE_URL = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']),"/\\") ?>';
  window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<?php endif; ?>
<?php
if ($_GET['page'] === 'category' && !empty($_GET['tt'])) {
    $tt = $_GET['tt'];
    $sql = "SELECT id, name, price, description, image_url, quantity 
            FROM items WHERE TT = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $tt);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $res->free();
   
}
