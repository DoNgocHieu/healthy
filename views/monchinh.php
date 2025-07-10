<?php
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

if (!isset($_SESSION['cart']) && isset($_COOKIE['cart_sync'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['cart_sync'], true);
}

$sql = "SELECT id, name, price, description, image_url, quantity 
        FROM items WHERE TT='MC'";
$res = $mysqli->query($sql) or die("SQL ERROR (items): ".$mysqli->error);

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$res->free();
$mysqli->close();
?>

<div id="itemModalOverlay" class="item-modal-bg" style="display:none;">
  <div class="item-modal-box">
    <button class="modal-close" onclick="closeItemModal()"></button>
    <div id="itemModalContent"></div>
  </div>
</div>

<script defer src="../js/qty.js"></script>
<?php include 'menu_options.php'; ?>
<div class="monchinh-container">
  <div class="monchinh-grid">
    <?php if (empty($items)): ?>
      <p style="grid-column:1/-1;text-align:center;">Chưa có món mới nào.</p>
    <?php else: ?>
      <?php foreach ($items as $it):
        $id        = (int)$it['id'];
        $stockQty  = (int)$it['quantity'];
        $inCartQty = $_SESSION['cart'][$id]['qty'] ?? 0;
      ?>
        <div class="monchinh-card" onclick="showItemModalById(<?= $id ?>)">
          <img src="../img/<?=htmlspecialchars($it['image_url'],ENT_QUOTES)?>"
               alt="<?=htmlspecialchars($it['name'],ENT_QUOTES)?>">
          <div class="name"><?=htmlspecialchars($it['name'],ENT_QUOTES)?></div>
          <div class="price"><?=number_format($it['price'],0,',','.')?> đ</div>
          <div class="qty-control" id="cart-controls-<?= $id ?>" onclick="event.stopPropagation()">
            <?php if ($stockQty <= 0): ?>
              <button class="add-to-cart-btn disabled" onclick="event.stopPropagation()">
                <i class="fa-solid fa-lock"></i>
              </button>
            <?php elseif ($inCartQty > 0): ?>
              <button onclick="handleIncrease(<?= $id ?>, -1, <?= $stockQty ?>)">
                <i class="fa-solid fa-minus" style="font-size: 1.2rem"></i>
              </button>
              <input
                id="qty-input-<?= $id ?>"
                class="qty-display"
                type="number"
                min="1"
                max="<?= $stockQty ?>"
                value="<?= $inCartQty ?>"
                oninput="handleQtyChange(<?= $id ?>, <?= $stockQty ?>)"
                style="font-size: 1.2rem"
              />
              <button onclick="handleIncrease(<?= $id ?>, 1, <?= $stockQty ?>)">
                <i class="fa-solid fa-plus" style="font-size: 1.2rem"></i>
              </button>
              <div id="qty-error-<?= $id ?>" class="qty-error-tab"></div>
            <?php else: ?>
              <button class="add-to-cart-btn" onclick="addToCart(<?= $id ?>, <?= $stockQty ?>)">
                <i class="fa-solid fa-bag-shopping"></i>
              </button>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<style>
.monchinh-container {
  max-width: 1100px; 
  margin: 0 auto; 
  padding: 2rem 1rem; 
}
.monchinh-grid {
  display: grid !important; 
  grid-template-columns: repeat(4, 240px); 
  justify-content: center; 
  gap: 2rem;
}
.monchinh-card {
  background: #fff;
  width: 240px !important;
  padding: 1rem;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: transform .2s, box-shadow .2s;
  cursor: pointer;
}
.monchinh-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  background: rgba(205,255,206,0.66);
}
.monchinh-card img {
  width: 100%;
  max-width: 180px;
  height: 140px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: .75rem;
  background: #eee;
}
.monchinh-card .name {
  font-size: 1.15rem;
  font-weight: 700;
  color: #063b2b;
  margin-bottom: .5rem;
}
.monchinh-card .price {
  font-weight: bold;
  color: rgb(221,168,21);
  margin: .5rem 0;
}
.item-detail-container.qty-control input[type=number]::-webkit-inner-spin-button,
.item-detail-container.qty-control input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.item-detail-container.qty-control input[type=number] {
  -moz-appearance: textfield;
}

.item-detail-container.qty-control,
[id^="cart-controls-"] {
  display: flex !important;
  align-items: center;
  justify-content: center;
}

.item-detail-container.qty-control button,
[id^="cart-controls-"] > button {
  background: transparent !important;
  border: none !important;
  color: #1D2E28;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: color .2s;
}
.item-detail-container.qty-control button:hover,
[id^="cart-controls-"] > button:hover {
  color: #6BBF59;
}

.qty-control input[type="number"],
[id^="cart-controls-"] input[type="number"] {
  width: 3.2rem;               
  height: 2.4rem;
  font-size: 1.2rem;           
  font-weight: 700;
  text-align: center;
  background: transparent;
  border: none;
  color: #1D2E28;
  padding: 0;
  box-sizing: border-box;
}
.add-to-cart-icon,
.add-to-cart-btn {
  width: 2.4rem;      
  height: 2.4rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent !important;
  border: none !important;
  font-size: 1.6rem;  
  color: #1D2E28 !important; 
  cursor: pointer;
  transition: color .2s, background .2s;
}

.add-to-cart-icon:hover,
.add-to-cart-btn:hover {
  color: #6BBF59 !important;
}

.add-to-cart-icon {
  font-size: 1.4rem;
  color: #005a30;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
}
.add-to-cart-icon:hover {
  color: #00cf8a;
  
}
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
.qty-control {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.qty-error-tab {
  position: absolute;
  top: -40px;
  left: 50%;
  transform: translateX(-50%);
  background: #ffe6e6;
  color: #d33;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.9rem;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  display: none;
  z-index: 2;
}
.qty-error-tab.show {
  display: block;
}
body {
  background: url('../img/menu.png') no-repeat center center fixed;
  background-size: cover;
  font-family: 'Segoe UI', sans-serif;
}
</style>
<script>
  window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
  console.log('isLoggedIn =', window.isLoggedIn);
</script>