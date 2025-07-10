<?php
require_once __DIR__ . '/../config/config.php';
$page = $_GET['page'] ?? 'home';

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
        $upd = $pdo->prepare(
            "UPDATE cart_items
                SET quantity = :qty, is_deleted = 0
              WHERE user_id = :uid AND item_id = :iid"
        );
        $upd->execute([':qty'=>$qty, ':uid'=>$userId, ':iid'=>$itemId]);

        // Nếu không có bản ghi nào được cập nhật, INSERT mới
        if ($upd->rowCount() === 0) {
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
        WHERE ci.user_id = ? AND ci.is_deleted = 0"
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
  <link rel="icon" type="image/png" href="../img/logo.png" />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/site.css" />
  <link rel="stylesheet" href="../css/menu_options.css" />
  
  <script src="<?= BASE_URL ?>/js/qty.js" defer></script>
  <script defer src="../js/site.js"></script>
    <div id="fb-root"></div>
  <script async defer crossorigin="anonymous"
    src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v16.0">
  </script>
<header class="navbar">
  <div class="logo">
    <img src="../img/logo.png" alt="Broccoli Logo" class="logo-img" />
    <span>BROCCOLI</span>
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
    <a href="#" class="icon-link" title="Favorites"><i class="fa fa-heart"></i></a>
    <a href="layout.php?page=cart" class="icon-link" title="Cart"><i class="fa fa-shopping-cart"></i></a>
    <div class="user-menu">
      <i class="fa fa-user user-icon"></i>
      <ul class="user-dropdown">
        <?php if (!empty($_SESSION['user_id'])): ?>
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

<main>
  <?php
  $allowPages = [
    'login','signin','monmoi','khaivi','trongoi','dauhu','nam',
    'raucuqua','monchinh','canh','lau','trabanh',
    'item','vct','lh','home','hd1','cart','dl','BBCX','info','logout','forgot_password','points','address','checkout','footer'
  ];

  if (in_array($page, $allowPages) && file_exists($page . '.php')) {
    include $page . '.php';
    if ($page === 'home' && file_exists('hd1.php')) include 'hd1.php';
  }
  
  ?>
   <?php include __DIR__ . '/footer.php'; ?>
</main>
<script>
  window.BASE_URL = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']),"/\\") ?>';
  window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
