<?php
require_once __DIR__ . '/../config/config.php';
$mysqli = getDbConnection();

if (!isset($_SESSION['cart']) && isset($_COOKIE['cart_sync'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['cart_sync'], true);
}


// Lấy mã danh mục từ URL, ví dụ: monmoi.php?tt=MM
$tt = $_GET['tt'] ?? 'MM'; // Mặc định là 'MM' nếu không truyền

$sql = "SELECT id, name, price, description, image_url, quantity
        FROM items WHERE TT = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $tt);
$stmt->execute();
$res = $stmt->get_result();


// Lấy danh sách id món
$items = [];
$itemIds = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
    $itemIds[] = (int)$row['id'];
}
$res->free();

// Lấy điểm trung bình cho từng món
$avgStars = [];
if ($itemIds) {
    $mysqli2 = getDbConnection();
    $idsStr = implode(',', $itemIds);
    $sqlAvg = "SELECT id_food, ROUND(AVG(star),1) AS avg_star, COUNT(*) AS review_count FROM comments WHERE id_food IN ($idsStr) GROUP BY id_food";
    $resAvg = $mysqli2->query($sqlAvg);
    while ($row = $resAvg->fetch_assoc()) {
        $avgStars[(int)$row['id_food']] = [
            'avg_star' => (float)$row['avg_star'],
            'review_count' => (int)$row['review_count']
        ];
    }
    $resAvg->free();
    $mysqli2->close();
}
?>

<div id="itemModalOverlay" class="item-modal-bg" style="display:none;">
  <div class="item-modal-box">
    <button class="modal-close" onclick="closeItemModal()"></button>
    <div id="itemModalContent"></div>
  </div>
</div>

<script defer src="../js/qty.js"></script>
<script defer src="../js/favorites.js"></script>
<?php include 'menu_options.php'; ?>
<div class="monmoi-container">
  <div class="monmoi-grid">
    <?php if (empty($items)): ?>
      <p style="grid-column:1/-1;text-align:center;">Chưa có món mới nào.</p>
    <?php else: ?>
      <?php foreach ($items as $it):
        $id        = (int)$it['id'];
        $stockQty  = (int)$it['quantity'];
        $inCartQty = $_SESSION['cart'][$id]['qty'] ?? 0;
      ?>

        <div class="monmoi-card" onclick="showItemModalById(<?= $id ?>)" data-item-id="<?= $id ?>">
          <div class="card-header">
            <button class="favorite-btn" data-item-id="<?= $id ?>" title="Thêm vào yêu thích" onclick="event.stopPropagation()">
              <i class="fa-regular fa-heart"></i>
            </button>
          </div>
          <img src="../img/<?=htmlspecialchars($it['image_url'],ENT_QUOTES)?>"
               alt="<?=htmlspecialchars($it['name'],ENT_QUOTES)?>">
          <div class="name ellipsis-1line"><?=htmlspecialchars($it['name'],ENT_QUOTES)?></div>
          <?php
            $avg = $avgStars[$id]['avg_star'] ?? 0;
            $count = $avgStars[$id]['review_count'] ?? 0;
          ?>
          <div class="avg-star" style="margin-bottom:4px;">
            <?php if ($count > 0): ?>
              <span style="color:#f5b301;font-size:1.1em;">
                <?= str_repeat('★', floor($avg)) . str_repeat('☆', 5-floor($avg)) ?>
                <span style="font-weight:600;">(<?= $avg ?>)</span>
                <span style="color:#888;font-size:10px;">/ <?= $count ?> đánh giá</span>
              </span>
            <?php else: ?>
              <span style="color:#bbb;font-size:1em;">Chưa có đánh giá</span>
            <?php endif; ?>
          </div>
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
.monmoi-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 1rem;
}
.monmoi-grid {
  display: grid !important;
  grid-template-columns: repeat(4, 240px);
  justify-content: center;
  gap: 2rem;
}
.monmoi-card {
  background: #fff;
  width: 240px !important;
  padding: 1rem;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  position: relative;
  transition: transform .2s, box-shadow .2s;
  cursor: pointer;
}

.card-header {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
}

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
.monmoi-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  background: rgba(205,255,206,0.66);
}
.monmoi-card img {
  width: 100%;
  max-width: 180px;
  height: 140px;
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: .75rem;
  background: #eee;
}
  .monmoi-card .name {
    font-size: 1.15rem;
    font-weight: 700;
    color: #063b2b;
    margin-bottom: .5rem;
  }
  .ellipsis-1line {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    max-width: 100%;
  }
.monmoi-card .price {
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

/* Modal Styles */
.item-modal-bg {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.5);
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  padding: 20px;
}

.item-modal-bg[style*="display:none"] {
  display: none !important;
}

.item-modal-bg[style*="display:flex"] {
  display: flex !important;
}

.item-modal-box {
  position: relative;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
}


.modal-close {
  position: absolute;
  top: 15px;
  right: 15px;
  z-index: 10001;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #fff;
  transform: scale(1.1);
}

.modal-close::before {
  content: '×';
  font-weight: bold;
  color: #666;
}
</style>
<script>

  window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
  console.log('isLoggedIn =', window.isLoggedIn);

function showItemModalById(id) {
  fetch('/healthy/views/item.php?id=' + id, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax=1'
  })
    .then(res => res.text())
    .then(html => {
      document.getElementById('itemModalContent').innerHTML = html;
      const overlay = document.getElementById('itemModalOverlay');
      overlay.style.display = 'flex'; // Use flex instead of block

      // Hide the default modal close button since the item.php has its own
      const defaultCloseBtn = overlay.querySelector('.modal-close');
      if (defaultCloseBtn) {
        defaultCloseBtn.style.display = 'none';
      }

      // Execute scripts in the loaded content
      const scripts = document.getElementById('itemModalContent').querySelectorAll('script');
      scripts.forEach(script => {
        const newScript = document.createElement('script');
        if (script.src) {
          newScript.src = script.src;
        } else {
          newScript.textContent = script.textContent;
        }
        document.head.appendChild(newScript);
        if (!script.src) {
          document.head.removeChild(newScript);
        }
      });
    });
}
function closeItemModal() {
  document.getElementById('itemModalOverlay').style.display = 'none';

  // Show the default close button again for next time
  const defaultCloseBtn = document.querySelector('.modal-close');
  if (defaultCloseBtn) {
    defaultCloseBtn.style.display = 'flex';
  }
}

// Close modal when clicking outside
document.getElementById('itemModalOverlay').addEventListener('click', function(e) {
  // Only close if clicking the overlay background, not the modal content
  if (e.target === this) {
    closeItemModal();
  }
});
</script>
