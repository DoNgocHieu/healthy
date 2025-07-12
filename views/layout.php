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
// Xử lý logout trước khi xuất ra bất kỳ HTML nào
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
  include __DIR__ . '/logout.php';
  exit;
}

// Handle cart/checkout POST requests before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page']) && $_GET['page'] === 'cart') {
  require_once __DIR__ . '/../config/config.php';
  $pdo = getDb();

  if (empty($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
  }

  $action = $_POST['action'] ?? '';

  if ($action === 'select_address') {
    $_SESSION['selected_address_id'] = (int)$_POST['address_id'];
    header('Location: layout.php?page=cart');
    exit;
  }
  elseif ($action === 'edit_checkout') {
    $stmt = $pdo->prepare("
      UPDATE user_addresses
         SET fullname=:fn, address=:ad, phone=:ph
       WHERE id=:aid AND user_id=:uid
    ");
    $stmt->execute([
      ':fn'   => $_POST['fullname'],
      ':ad'   => $_POST['address'],
      ':ph'   => $_POST['phone'],
      ':aid'  => (int)$_POST['address_id'],
      ':uid'  => $_SESSION['user_id']
    ]);
    $_SESSION['selected_address_id'] = (int)$_POST['address_id'];

    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'ok',
        'id' => (int)$_POST['address_id'],
        'fullname' => $_POST['fullname'],
        'address' => $_POST['address'],
        'phone' => $_POST['phone']
      ]);
      exit;
    }
    header('Location: layout.php?page=cart');
    exit;
  }
  elseif ($action === 'add_checkout') {
    $ins = $pdo->prepare("
      INSERT INTO user_addresses (user_id,fullname,address,phone)
      VALUES (:uid,:fn,:ad,:ph)
    ");
    $ins->execute([
      ':uid'=>$_SESSION['user_id'],
      ':fn' =>$_POST['fullname'],
      ':ad' =>$_POST['address'],
      ':ph' =>$_POST['phone']
    ]);

    $newId = $pdo->lastInsertId();
    $_SESSION['selected_address_id'] = $newId;

    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'ok',
        'id' => $newId,
        'fullname' => $_POST['fullname'],
        'address' => $_POST['address'],
        'phone' => $_POST['phone']
      ]);
      exit;
    }
    header('Location: layout.php?page=order_confirm');
    exit;
  }
}

// Handle order_confirm POST requests before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page']) && $_GET['page'] === 'order_confirm') {
  require_once __DIR__ . '/../config/config.php';
  $pdo = getDb();
  $userId = $_SESSION['user_id'] ?? null;

  // Debug logging
  error_log("Order confirm POST - User ID: " . ($userId ?: 'NULL'));
  error_log("Payment method: " . ($_POST['payment_method'] ?? 'NULL'));
  error_log("Selected address ID: " . ($_SESSION['selected_address_id'] ?? 'NULL'));

  if (!$userId) {
    error_log("Order confirm POST - No user ID, redirecting to login");
    header('Location: layout.php?page=login');
    exit;
  }

  if (empty($_POST['payment_method'])) {
    error_log("Order confirm POST - No payment method selected");
    // Let the page handle the error display
  } else {
    // Get cart items
    $stmt = $pdo->prepare("
      SELECT ci.item_id, ci.quantity, i.name, i.price, i.quantity as stock_qty
      FROM cart_items ci
      JOIN items i ON ci.item_id = i.id
      WHERE ci.user_id = ? AND ci.is_deleted = 0
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get address
    $addrStmt = $pdo->prepare("
      SELECT id, fullname, phone, address
      FROM user_addresses
      WHERE user_id = ? AND id = ?
    ");
    $selectedAddressId = $_SESSION['selected_address_id'] ?? null;
    error_log("Selected address ID: " . ($selectedAddressId ?: 'NULL'));

    $addrStmt->execute([$userId, $selectedAddressId]);
    $selectedAddress = $addrStmt->fetch(PDO::FETCH_ASSOC);

    error_log("Found address: " . ($selectedAddress ? 'YES' : 'NO'));
    if (!$selectedAddress) {
      error_log("Address fetch failed - trying to get default address");
      // Try to get default address if selected address not found
      $defaultAddrStmt = $pdo->prepare("
        SELECT id, fullname, phone, address
        FROM user_addresses
        WHERE user_id = ? AND is_default = 1
        LIMIT 1
      ");
      $defaultAddrStmt->execute([$userId]);
      $selectedAddress = $defaultAddrStmt->fetch(PDO::FETCH_ASSOC);
      error_log("Default address found: " . ($selectedAddress ? 'YES' : 'NO'));
    }

    if ($selectedAddress && !empty($cartItems)) {
      // Calculate totals
      $subtotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
      $shipping = 0;
      $discount = 0;
      $total = $subtotal + $shipping - $discount;

      // Check stock
      $outOfStock = [];
      foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock_qty']) {
          $outOfStock[] = $item['name'];
        }
      }

      if (empty($outOfStock)) {
        try {
          $pdo->beginTransaction();

          // Create order
          $orderStmt = $pdo->prepare("
            INSERT INTO orders (
              user_id, shipping_address, payment_method, subtotal,
              shipping_fee, discount, total_amount, order_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
          ");
          $fullShipping = $selectedAddress['fullname'] . ', ' . $selectedAddress['phone'] . ', ' . $selectedAddress['address'];
          $orderStmt->execute([$userId, $fullShipping, $_POST['payment_method'], $subtotal, $shipping, $discount, $total]);
          $orderId = $pdo->lastInsertId();

          // Add order items
          $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)
          ");
          foreach ($cartItems as $item) {
            $itemStmt->execute([$orderId, $item['item_id'], $item['quantity'], $item['price']]);
          }

          // Clear cart
          $clearCartStmt = $pdo->prepare("UPDATE cart_items SET is_deleted = 1 WHERE user_id = ?");
          $clearCartStmt->execute([$userId]);

          $pdo->commit();

          // Redirect based on payment method
          if ($_POST['payment_method'] === 'vnpay') {
            header("Location: vnpay_pay.php?id=$orderId");
            exit;
          } else {
            header("Location: layout.php?page=order_success&id=$orderId");
            exit;
          }
        } catch (Exception $e) {
          $pdo->rollBack();
          // Let the page handle the error display
        }
      }
    }
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
      <input type="text" name="q" placeholder="Search" />
      <button type="submit"><i class="fa fa-search"></i></button>
    </form>
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
<?php endif; ?>
  <?php
  $allowPages = [
    'login','signin','monmoi','khaivi','trongoi','dauhu','nam',
    'raucuqua','monchinh','canh','lau','trabanh',
    'item','vct','lh','home','hd1','cart','dl','info','logout','forgot_password','points','address','checkout','footer',
    'order_confirm','order_success','orders','admin','favorites','post_detail'
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
?>
<script>
  if (window.favoritesManager && window.favoritesManager.init) {
    window.favoritesManager.init();
  }
</script>
<style>

.favorite-btn {
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.favorite-btn:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.favorite-btn i {
  font-size: 16px;
  color: #6c757d;
  transition: color 0.3s ease;
}

.favorite-btn.favorited i {
  color: #e74c3c;
}

.favorite-btn:hover i {
  color: #e74c3c;
}

</style>
