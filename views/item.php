<?php
$isAjax = isset($_POST['ajax']) && $_POST['ajax']=='1';
require_once __DIR__ . '/../config/config.php';
$mysqli     = getDbConnection();
session_start();

$id         = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo "Món ăn không hợp lệ!"; exit; }

$res        = $mysqli->query("SELECT * FROM items WHERE id=$id");
$item       = $res->fetch_assoc();
$res->free();
$mysqli->close();

if (!$item) { echo "Không tìm thấy món ăn!"; exit; }

$maxQty     = intval($item['quantity']);
$inCartQty  = $_SESSION['cart'][$id]['qty'] ?? 0;
?>

<script defer src="../js/qty.js"></script>

<?php if ($isAjax): ?>
  <button class="item-detail-modal-close"
          onclick="window.parent?.closeItemModal?.()"
          title="Đóng (Esc)">
    <svg viewBox="0 0 24 24" fill="none">
      <path d="M6 6l12 12M6 18L18 6"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"/>
    </svg>
  </button>
<?php endif; ?>
<script>
  initializeFromStorage();
  updateCartIcon();
</script>
<div class="item-detail-container">
  <div class="item-detail-row">

<div class="item-detail-info">
  <h2><?=htmlspecialchars($item['name'], ENT_QUOTES)?></h2>
  <div class="price"><?=number_format($item['price'],0,',','.')?> đ</div>

  <?php if ($maxQty > 0): ?>
    <div class="add-cart-row qty-control" id="cart-controls-<?= $id ?>">
      <?php if ($inCartQty > 0): ?>
        <button
          onclick="event.stopPropagation(); handleDecrease(<?= $id ?>, <?= $maxQty ?>)"
        >
          <i class="fa-solid fa-minus" style="font-size: 1.2rem"></i>
        </button>
        <input
          id="qty-input-<?= $id ?>"
          class="qty-display"
          type="number"
          min="1"
          max="<?= $maxQty ?>"
          value="<?= $inCartQty ?>"
          oninput="handleQtyInput(<?= $id ?>, <?= $maxQty ?>)"
          onblur="handleQtyBlur(<?= $id ?>, <?= $maxQty ?>)"
          style="font-size: 1.2rem"
        />
      <script>
        if (typeof initializeControls === 'function') {
          initializeControls();
        }
      </script>
        <div id="qty-error-<?= $id ?>" class="qty-error-tab"></div>

        <button
          onclick="event.stopPropagation(); handleIncrease(<?= $id ?>, <?= $maxQty ?>)"
        >
          <i class="fa-solid fa-plus" style="font-size: 1.2rem"></i>
        </button>
      <?php else: ?>
        <!-- Nếu chưa có trong giỏ, show icon thêm -->
        <i class="fas fa-shopping-bag add-to-cart-icon"
           onclick="event.stopPropagation(); addToCart(<?= $id ?>)">
        </i>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="color:#e74c3c;font-weight:600;margin:12px 0;">
      Món này đã hết hàng
    </div>
  <?php endif; ?>
</div>
    <div class="item-detail-img">
      <img src="../img/<?=htmlspecialchars($item['image_url'], ENT_QUOTES)?>"
           alt="<?=htmlspecialchars($item['name'], ENT_QUOTES)?>">
    </div>
  </div>

  <div class="desc">
    <h1>MÔ TẢ</h1>
    <?php
      $desc = htmlspecialchars($item['description'], ENT_QUOTES);
      $paragraphs = preg_split('/\R+/', $desc, -1, PREG_SPLIT_NO_EMPTY);
      foreach ($paragraphs as $p) {
        echo '<p>' . $p . '</p>';
      }
    ?>
  </div>

  <!-- ĐÁNH GIÁ SẢN PHẨM -->
  <style>
  .review-section {
    margin-top: 2.2rem;
    padding: 1.2rem 0 0.5rem 0;
    border-top: 1.5px solid #e0e0e0;
  }
  #review-list .review-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0 6px 0;
    margin-bottom: 2px;
    background: #f9f9f9;
    border-radius: 6px;
    box-shadow: 0 1px 2px #0001;
  }
  #review-list .review-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 2px;
  }
  #review-list .review-user {
    font-weight: 600;
    color: #0a4d1a;
    font-size: 1.08em;
  }
  #review-list .review-star {
    color: #f5b301;
    font-size: 1.15em;
    letter-spacing: 1px;
  }
  #review-list .review-date {
    color: #888;
    font-size: 0.97em;
  }
  #review-list .review-detail {
    margin-left: 4px;
    color: #222;
    font-size: 1.04em;
    padding-left: 2px;
  }
  #add-review-form {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-top: 18px;
    flex-wrap: wrap;
    background: #f6fff6;
    border-radius: 8px;
    padding: 12px 10px 10px 10px;
    box-shadow: 0 1px 4px #0001;
  }
  #add-review-form input[name=username] {
    min-width: 140px;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1.2px solid #b5b5b5;
    font-size: 1em;
    background: #fff;
  }
  #add-review-form .star-group {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 1.5em;
    cursor: pointer;
    margin-top: 2px;
    user-select: none;
  }
  #add-review-form .star-group .star {
    color: #ccc;
    transition: color .15s;
    cursor: pointer;
    padding: 0 1px;
  }
  #add-review-form .star-group .star.selected,
  #add-review-form .star-group .star:hover,
  #add-review-form .star-group .star.selected ~ .star {
    color: #f5b301;
  }
  #add-review-form textarea {
    min-width: 220px;
    min-height: 32px;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1.2px solid #b5b5b5;
    resize: vertical;
    font-size: 1em;
    background: #fff;
  }
  #add-review-form button[type=submit] {
    background: #6BBF59;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 24px;
    font-weight: 700;
    font-size: 1.08em;
    cursor: pointer;
    transition: background .18s;
    box-shadow: 0 1px 4px #0001;
    margin-left: 8px;
  }
  #add-review-form button[type=submit]:hover {
    background: #4e9c3e;
  }
  @media (max-width: 600px) {
    #add-review-form {
      flex-direction: column;
      align-items: stretch;
      gap: 8px;
    }
    #add-review-form textarea {
      min-width: 100px;
    }
  }
  </style>
  <div class="review-section">
    <div id="review-list"></div>
    <form id="add-review-form" autocomplete="off">
      <input name="username" placeholder="Tên của bạn" required>
      <div class="star-group" id="star-group">
        <span class="star" data-star="1">&#9733;</span>
        <span class="star" data-star="2">&#9733;</span>
        <span class="star" data-star="3">&#9733;</span>
        <span class="star" data-star="4">&#9733;</span>
        <span class="star" data-star="5">&#9733;</span>
        <input type="hidden" name="star" id="star-input" required>
      </div>
      <textarea name="detail" placeholder="Nội dung đánh giá" required></textarea>
      <button type="submit">Gửi đánh giá</button>
    </form>
  </div>
  <script>
    // Star rating UI
    (function() {
      const stars = document.querySelectorAll('#star-group .star');
      const starInput = document.getElementById('star-input');
      let selected = 0;
      stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
          const val = +this.dataset.star;
          stars.forEach(s => s.classList.toggle('selected', +s.dataset.star <= val));
        });
        star.addEventListener('mouseleave', function() {
          stars.forEach(s => s.classList.toggle('selected', +s.dataset.star <= selected));
        });
        star.addEventListener('click', function() {
          selected = +this.dataset.star;
          starInput.value = selected;
          stars.forEach(s => s.classList.toggle('selected', +s.dataset.star <= selected));
        });
      });
    })();
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof loadReviews === 'function') loadReviews(<?= $id ?>);
      if (typeof addReviewHandler === 'function') addReviewHandler(<?= $id ?>);
    });
  </script>
</div>

<style>
.item-modal-bg {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(34,139,34,0.09);
  display:  grid;
  justify-content: center;
  backdrop-filter: blur(2.2px);
}

.item-detail-container {
  position: relative;
  background: #fff;
  border-radius: 28px;
  box-shadow: 0 10px 48px rgba(34,139,34,0.17), 0 2px 16px #0001;
  width: 96vw;
  max-width: 780px;
  padding: 2.2rem;
  margin: 0 auto;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalPop .18s cubic-bezier(.51,-0.16,.54,1.26);
}
.item-detail-row {
  display: flex;
  gap: 2.5rem;
  align-items: center;
}
.item-detail-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.item-detail-info h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: rgb(0,39,16);
  margin-bottom: .65rem;
}
.item-detail-info .price {
  font-size: 1.17rem;
  font-weight: 680;
  color: rgb(221,168,21);
  margin-bottom: .65rem;
}
.item-detail-img {
  flex: 0 0 230px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.item-detail-img img {
  width: 100%;
  max-width: 230px;
  height: 230px;
  object-fit: cover;
  border-radius: 16px;
  box-shadow: 0 3px 18px #0001;
  background: #eee;
}
.desc {
  margin-top: 1.4rem;
  font-size: 1rem;
  line-height: 1.65;
  color: #282828;
  text-align: justify;
}
.desc h1 {
  text-align: center;
  font-size: 1.8rem;
  font-weight: 700;
  color: rgb(13,117,58);
}
.desc p {
  text-indent: 2rem;
  margin: 0 0 1rem 0;
}

.item-detail-modal-close {
  position: absolute;
  top: 1rem;
  right: 1.5rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 2rem;
  color: #222;
  opacity: .7;
  transition: opacity .15s, color .15s;
}
.item-detail-modal-close:hover {
  opacity: 1;
  color: #e74c3c;
}

@media (max-width: 800px) {
  .item-detail-row {
    flex-direction: column;
    gap: 1.4rem;
  }
  .item-detail-container {
    padding: 1.2rem;
    width: 99vw;
  }
  .item-detail-info h2,
  .item-detail-info .price {
    text-align: center;
  }
  .qty-error-tab
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
.item-detail-container .add-to-cart-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1.2rem;
  background:rgba(181, 255, 156, 0.75) !important;
  border: 1.5px solid rgb(30, 128, 0) !important; 
  border-radius: 8px;
  color:rgb(0, 64, 19) !important;
  font-size: 1rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  margin: 1rem auto 0;
  transition: color .2s, border-color .2s, background .2s;
  padding: .75rem 2.5rem !important;
  min-width: 200px !important;
}

.item-detail-container .add-to-cart-btn:hover {
  color:rgb(217, 0, 0) !important;
  border-color:rgb(58, 55, 2) !important;
  background: rgba(221, 168, 21, 0.22) !important;
}
.qty-control {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-error-tab {
  position: absolute;
  margin-top:6rem;
  margin-left:70px;
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
</style>
