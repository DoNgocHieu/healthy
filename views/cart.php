<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
$pdo    = getDb();
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: layout.php?page=login');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    $itemId = intval($_POST['id']  ?? 0);
    $qty = max(0, intval($_POST['qty'] ?? 0));

    // Kiểm tra bản ghi có tồn tại và chưa bị xóa mềm
    $chk = $pdo->prepare("SELECT quantity, is_deleted FROM cart_items WHERE user_id = ? AND item_id = ? AND is_deleted = 0");
    $chk->execute([$userId, $itemId]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if ($qty > 0) {
            // Cập nhật lại số lượng
            $updateStmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND item_id = ?");
            $updateStmt->execute([$qty, $userId, $itemId]);
        } else {
            // Nếu qty = 0, xóa mềm
            $updateStmt = $pdo->prepare("UPDATE cart_items SET is_deleted = 1 WHERE user_id = ? AND item_id = ?");
            $updateStmt->execute([$userId, $itemId]);
        }
    }

    // Tính lại line total và grand total
    $stmtLine = $pdo->prepare("SELECT i.price, ci.quantity FROM cart_items ci LEFT JOIN items i ON ci.item_id = i.id WHERE ci.user_id = ? AND ci.item_id = ? AND ci.is_deleted = 0");
    $stmtLine->execute([$userId, $itemId]);
    $r = $stmtLine->fetch() ?: ['price' => 0, 'quantity' => 0];
    $lineTotal = $r['price'] * $r['quantity'];

    $stmtAll = $pdo->prepare("SELECT SUM(i.price * ci.quantity) FROM cart_items ci LEFT JOIN items i ON ci.item_id = i.id WHERE ci.user_id = ? AND ci.is_deleted = 0");
    $stmtAll->execute([$userId]);
    $grand = $stmtAll->fetchColumn() ?: 0;

    echo json_encode([
        'status' => 'ok',
        'line_total' => number_format($lineTotal, 0, ',', '.') . ' đ',
        'grand_total' => number_format($grand, 0, ',', '.') . ' đ'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addcart'])) {
    header('Content-Type: application/json; charset=utf-8');

    $itemId = intval($_POST['id']);
    $addQty = max(1, intval($_POST['qty'] ?? 1));

    // Kiểm item tồn tại
    $it = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
    $it->execute([$itemId]);
    if (!$it->fetch()) {
        echo json_encode(['status'=>'error']);
        exit;
    }

    // Kiểm cart_items
    $chk = $pdo->prepare("
      SELECT quantity, is_deleted
        FROM cart_items
       WHERE user_id = ? AND item_id = ?
    ");
    $chk->execute([$userId, $itemId]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Restore + cộng dồn
        $newQty = $row['quantity'] + $addQty;
        $pdo->prepare("
          UPDATE cart_items
             SET quantity   = ?,
                 is_deleted = 0
           WHERE user_id = ? AND item_id = ?
        ")->execute([$newQty, $userId, $itemId]);
    } else {
        // Insert mới
        $pdo->prepare("
          INSERT INTO cart_items(user_id, item_id, quantity)
          VALUES(?, ?, ?)
        ")->execute([$userId, $itemId, $addQty]);
    }

    echo json_encode(['status'=>'ok','qty'=>$addQty]);
    exit;
}
if (isset($_GET['del'])) {
    $itemId = intval($_GET['del']);
    $stmt = $pdo->prepare("
      UPDATE cart_items
         SET is_deleted = 1
       WHERE user_id = ? AND item_id = ?
    ");
    $stmt->execute([$userId, $itemId]);
    header('Location: layout.php?page=cart');
    exit;
}

// Load cart để hiển thị
$stmt = $pdo->prepare("
   SELECT
     ci.item_id   AS item_id,
     ci.quantity  AS qty,
     i.name, i.price, i.image_url, i.quantity AS stock_qty
   FROM cart_items ci
   JOIN items i
     ON ci.item_id = i.id
   WHERE ci.user_id = ? AND ci.is_deleted = 0
   ORDER BY ci.added_at DESC
");
$stmt->execute([$userId]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng
$total = array_reduce(
  $cart,
  fn($s, $r) => $s + $r['price'] * $r['qty'],
  0
);

// Load địa chỉ
$addrStmt = $pdo->prepare("
  SELECT id, fullname, phone, address, is_default
    FROM user_addresses
   WHERE user_id = ?
   ORDER BY is_default DESC, id ASC
");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);

// Xác định địa chỉ chọn
$selected = null;
if (!empty($_SESSION['selected_address_id'])) {
  foreach ($addresses as $a) {
    if ($a['id'] === $_SESSION['selected_address_id']) {
      $selected = $a;
      break;
    }
  }
}
?>
<?php include __DIR__ . '/checkout.php'; ?>
<video id="bg-video" autoplay muted loop>
  <source src="../video/cart.mp4" type="video/mp4">
</video>
<form id="selectAddressForm" method="POST" style="display:none">
  <input type="hidden" name="select_address" value="1">
  <input type="hidden" name="address_id" id="select_address_id" value="">
</form>
    <h2 class="cart-title">Giỏ hàng</h2>
    <link rel="stylesheet" href="../css/cart.css">
    <script defer src="../js/cart.js"></script>
    <div class="cart-container">
    <div class="cart-left">
    <?php if (!empty($cart)): ?>
        <div class="cart-header-row">
        <div>
            <label><input type="checkbox" id="check-all">
            Chọn tất cả <span id="selected-count">(0 món ăn)</span>
            </label>
        </div>
        <div class="cart-header-label">Đơn giá</div>
        <div class="cart-header-label">Số lượng</div>
        <div class="cart-header-label">Thành Tiền</div>
        </div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="cart-empty-box">
        <p>Giỏ hàng đang trống, hãy đến trang chủ để lựa chọn các món ăn với giá cực hấp dẫn nhé</p>
        <p><a href="layout.php?page=monmoi" class="cart-empty-home-link">MENU</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($cart as $item):
            // Lấy ID: ưu tiên item_id, nếu không có thì dùng id
            $id = $item['item_id'] ?? $item['id'] ?? null;
            if (!$id) continue;  // nếu vẫn không có thì bỏ qua item này

            // Các biến khác
            $name      = htmlspecialchars($item['name']       ?? '', ENT_QUOTES);
            $price     = intval($item['price']                ?? 0);
            $qty       = intval($item['qty']                  ?? 0);
            $img       = htmlspecialchars($item['image_url']   ?? '', ENT_QUOTES);
            $stock_qty = intval($item['stock_qty']            ?? 9999);

            $thanhtien = $price * $qty;
        ?>
        <div class="cart-item" data-id="<?= $id ?>">
        <input type="checkbox" class="cart-checkbox">

        <img class="cart-img" src="../img/<?= $img ?>" alt="<?= $name ?>">

        <div class="cart-info">
            <h4><?= $name ?></h4>
        </div>

        <div class="cart-price">
            <?= number_format($price, 0, ',', '.') ?> đ
        </div>

        <div class="cart-quantity">
            <button class="qty-decrease">-</button>
            <input
            type="number"
            class="cart-qty-input"
            data-id="<?= $id ?>"
            value="<?= $qty ?>"
            min="1"
            max="<?= $stock_qty ?>"
            >
            <button class="qty-increase">+</button>
        </div>

        <div class="cart-total-price cart-line-total">
            <?= number_format($thanhtien, 0, ',', '.') ?> đ
        </div>
        <a href="layout.php?page=cart&del=<?= $id ?>" class="cart-remove">
          <i class="fas fa-trash-alt"></i>
        </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
        <div class="cart-right">
        <div class="cart-box">
  <h4>Giao tới</h4>

  <?php if ($selected): ?>
    <?php
      // Tìm object của địa chỉ mặc định
      $default = array_filter($addresses, fn($a)=>$a['is_default']);
      $current = $selected;
      $isDefault = (int)$current['id'] === ((int)($default[array_key_first($default)]['id'] ?? 0));
    ?>
    <div class="address-details <?= $isDefault ? 'default-address' : '' ?>">
      <p>
        <p><b>HỌ TÊN: </b><?= htmlspecialchars($current['fullname']) ?></p>
        <?php if ($isDefault): ?>
          <span class="badge"       style="
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        margin-left: 0;
        color: red;
        background-color: #ffe5e5;
        padding: 0.5rem 1rem;
        margin-top:1px;
        border-radius: 4px;
        margin-left:2rem;
        font-size: 0.75rem;
        font-weight: bold;
      ">Mặc định</span>
        <?php endif; ?>
      </p>
      <b>ĐỊA CHỈ: </b><?= nl2br(htmlspecialchars($current['address'])) ?>
      <br><b>SĐT: </b><?= htmlspecialchars($current['phone']) ?>
    </div>
  <?php else: ?>
    <p class="address-details">Vui lòng thêm địa chỉ</p>
  <?php endif; ?>

<a href="#" id="btnSelectAddress" class="change-address">
  <?= $selected ? 'Thay đổi địa chỉ' : 'Đặt Địa Chỉ' ?>
</a>
        </div>
            <div class="cart-box">
                <h4>Khuyến mãi <button style="float:right; background:#ccc; border:none; padding:0.3rem 0.8rem; border-radius:1rem; cursor:not-allowed;">Áp Dụng</button></h4>
                <input type="text" placeholder="Nhập mã khuyến mãi" style="width:100%; padding:0.5rem; margin-top:0.5rem;">
            </div>
              <div class="cart-box">
                <p>Tạm tính: <span id="subtotal">0 đ</span></p>
                <p>Giảm giá: <span id="discount">0 đ</span></p>
                <p>Phí vận chuyển: <span id="shipping">0 đ</span></p>
                <p style="font-weight:bold;">
                  Tổng tiền: <span id="total">0 đ</span>
                </p>
              </div>
              <button class="cart-checkout-btn">Mua Hàng (<span id="checkout-count">0</span>)</button>
        </div>
    </div>
<script>
   window.isLoggedIn = true;
</script>
