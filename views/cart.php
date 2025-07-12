<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
$pdo    = getDb();
$userId = $_SESSION['user_id'] ?? null;

// Handle AJAX cart operations only (redirect logic moved to layout.php)
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
    echo json_encode(['status' => 'error']);
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

  echo json_encode(['status' => 'ok', 'qty' => $addQty]);
  exit;
}

// Check login for displaying cart content
if (!$userId) {
    echo '<div style="text-align:center;padding:50px;">
            <h3>Vui lòng đăng nhập để xem giỏ hàng</h3>
            <a href="layout.php?page=login" class="btn">Đăng nhập</a>
          </div>';
    return;
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

// Lấy danh sách voucher user đã đổi nhưng chưa dùng
$stmt = $pdo->prepare("
    SELECT vu.id, vu.voucher_id, v.code, v.description, v.discount_type, v.discount_value, v.expires_at
      FROM voucher_usage vu
      JOIN vouchers v ON vu.voucher_id = v.id
     WHERE vu.user_id = ? AND vu.order_id IS NULL
     ORDER BY vu.used_at DESC
");
$stmt->execute([$userId]);
$unusedVouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
              max="<?= $stock_qty ?>">
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
        $default = array_filter($addresses, fn($a) => $a['is_default']);
        $current = $selected;
        $isDefault = (int)$current['id'] === ((int)($default[array_key_first($default)]['id'] ?? 0));
        ?>
        <div class="address-details <?= $isDefault ? 'default-address' : '' ?>">
          <p>
          <p><b>HỌ TÊN: </b><?= htmlspecialchars($current['fullname']) ?></p>
          <?php if ($isDefault): ?>
            <span class="badge" style="
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
      <?php if (!empty($unusedVouchers)): ?>
        <ul class="voucher-list">
          <?php foreach ($unusedVouchers as $v): ?>
            <li
              data-code="<?= htmlspecialchars($v['code']) ?>"
              data-type="<?= htmlspecialchars($v['discount_type']) ?>"
              data-value="<?= htmlspecialchars($v['discount_value']) ?>"
            >
              <b><?= htmlspecialchars($v['code']) ?></b> - <?= htmlspecialchars($v['description']) ?>
              <?php if ($v['discount_type'] === 'percent'): ?>
                (<?= number_format($v['discount_value'], 1) ?>%)
              <?php else: ?>
                (<?= number_format($v['discount_value'], 0, ',', '.') ?>₫)
              <?php endif; ?>
              <?php if ($v['expires_at']): ?>
                - HSD: <?= date('d/m/Y', strtotime($v['expires_at'])) ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>Bạn chưa có voucher nào chưa dùng.</p>
      <?php endif; ?>
      <input type="text" placeholder="Nhập mã khuyến mãi" style="width:100%; padding:0.5rem; margin-top:0.5rem;">
    </div>
    <div class="cart-box">
      <p>Tạm tính: <span id="subtotal">0 đ</span></p>
      <p>Giảm giá: <span id="discount">0 đ</span></p>
      <p>Phí vận chuyển: <span id="shipping">15.000 đ</span></p>
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
<script>
const voucherInput = document.querySelector('input[placeholder="Nhập mã khuyến mãi"]');
const applyBtn = document.querySelector('.cart-box h4 button');
const voucherList = document.querySelectorAll('.voucher-list li');
const subtotalEl = document.getElementById('subtotal');
const discountEl = document.getElementById('discount');
const totalEl = document.getElementById('total');
const shippingEl = document.getElementById('shipping');

let selectedVoucher = null;

// Hiển thị tạm tính ban đầu
function updateSubtotal() {
  let subtotal = 0;
  document.querySelectorAll('.cart-line-total').forEach(e => {
    subtotal += parseInt(e.textContent.replace(/\D/g, '')) || 0;
  });
  subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + ' đ';
  return subtotal;
}

// Phí ship cố định
const SHIPPING_FEE = 15000;
shippingEl.textContent = SHIPPING_FEE.toLocaleString('vi-VN') + ' đ';

voucherInput.addEventListener('input', function() {
  selectedVoucher = null;
  applyBtn.style.background = '#ccc';
  applyBtn.style.cursor = 'not-allowed';
  applyBtn.disabled = true;

  voucherList.forEach(li => {
    if (li.dataset.code && li.dataset.code.toUpperCase() === this.value.trim().toUpperCase()) {
      selectedVoucher = li;
    }
  });

  if (selectedVoucher) {
    applyBtn.style.background = '#4caf50';
    applyBtn.style.cursor = 'pointer';
    applyBtn.disabled = false;
  }
});

applyBtn.addEventListener('click', function() {
  if (!selectedVoucher) return;

  let subtotal = updateSubtotal();
  let discount = 0;
  if (selectedVoucher.dataset.type === 'percent') {
    discount = Math.round(subtotal * parseFloat(selectedVoucher.dataset.value) / 100);
  } else {
    discount = parseInt(selectedVoucher.dataset.value);
  }
  discountEl.textContent = discount.toLocaleString('vi-VN') + ' đ';

  // Tổng tiền = tạm tính - giảm giá + ship
  let total = subtotal - discount + SHIPPING_FEE;
  totalEl.textContent = total.toLocaleString('vi-VN') + ' đ';
});

function updateTotal() {
  let subtotal = 0;
  document.querySelectorAll('.cart-line-total').forEach(e => {
    subtotal += parseInt(e.textContent.replace(/\D/g, '')) || 0;
  });
  subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + ' đ';

  let discount = 0;
  if (selectedVoucher) {
    if (selectedVoucher.dataset.type === 'percent') {
      discount = Math.round(subtotal * parseFloat(selectedVoucher.dataset.value) / 100);
    } else {
      discount = parseInt(selectedVoucher.dataset.value);
    }
  }
  discountEl.textContent = discount.toLocaleString('vi-VN') + ' đ';

  // Phí ship cố định
  shippingEl.textContent = SHIPPING_FEE.toLocaleString('vi-VN') + ' đ';

  // Tổng tiền = tạm tính - giảm giá + ship
  let total = subtotal - discount + SHIPPING_FEE;
  totalEl.textContent = total.toLocaleString('vi-VN') + ' đ';

  // Cập nhật số lượng món
  const count = document.querySelectorAll('.cart-item').length;
  document.getElementById('checkout-count').textContent = count;
}

// Gọi khi trang load
updateTotal();

// Gọi lại khi nhấn áp dụng voucher
applyBtn.addEventListener('click', function() {
  if (!selectedVoucher) return;
  updateTotal();
});
document.querySelectorAll('.qty-increase, .qty-decrease, .cart-qty-input').forEach(el => {
  el.addEventListener('change', updateTotal);
  el.addEventListener('click', updateTotal);
});

document.addEventListener('DOMContentLoaded', function() {
  updateTotal();
});
</script>
<script>
// Xử lý đặt hàng khi nhấn nút Mua Hàng
document.querySelector('.cart-checkout-btn').addEventListener('click', function() {
  const count = document.querySelectorAll('.cart-item').length;
  if (count < 1) {
    alert('Vui lòng chọn ít nhất một món để mua hàng');
    return;
  }
  // Chuyển sang trang xác nhận hoặc gửi dữ liệu lên server
  window.location.href = 'layout.php?page=order_confirm';
});
</script>
<script>
  // Thay đổi địa chỉ giao hàng
  document.getElementById('btnSelectAddress').addEventListener('click', function(e) {
    e.preventDefault();
    const form = document.getElementById('selectAddressForm');
    const addressId = <?= json_encode($selected ? $selected['id'] : null) ?>;
    if (addressId) {
      document.getElementById('select_address_id').value = addressId;
      form.submit();
    } else {
      alert('Vui lòng chọn địa chỉ giao hàng.');
    }
  });
</script>
