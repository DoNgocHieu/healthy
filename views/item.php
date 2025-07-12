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
<div class="item-detail-container">
  <div class="item-detail-row">

<div class="item-detail-info">
  <div class="title-with-heart">
    <h2><?=htmlspecialchars($item['name'], ENT_QUOTES)?></h2>
    <button class="favorite-btn favorite-btn-large" data-item-id="<?= $id ?>" title="Thêm vào yêu thích">
      <i class="fa-regular fa-heart"></i>
    </button>
  </div>
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
      <img src="/healthy/img/<?=htmlspecialchars($item['image_url'], ENT_QUOTES)?>"
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
    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-rows: auto auto auto auto;
    gap: 16px;
    margin-top: 18px;
    background: linear-gradient(135deg, #f8fff8 0%, #f0fcf0 100%);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(107, 191, 89, 0.2);
  }
  #add-review-form input[name=username] {
    grid-column: 1;
    grid-row: 1;
    padding: 12px 16px;
    border-radius: 8px;
    border: 2px solid #e8f5e8;
    font-size: 1.05em;
    background: #fff;
    transition: all 0.3s ease;
    font-weight: 500;
  }
  #add-review-form input[name=username]:focus {
    outline: none;
    border-color: #6BBF59;
    box-shadow: 0 0 0 3px rgba(107, 191, 89, 0.1);
  }
  #add-review-form .star-group {
    grid-column: 2;
    grid-row: 1;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 1.8em;
    cursor: pointer;
    user-select: none;
    padding: 8px 12px;
    border-radius: 10px;
    background: rgba(245, 179, 1, 0.08);
    border: 2px solid rgba(245, 179, 1, 0.25);
    transition: all .3s ease;
    justify-self: end;
  }
  #add-review-form .star-group:hover {
    background: rgba(245, 179, 1, 0.1);
    border-color: rgba(245, 179, 1, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(245, 179, 1, 0.15);
  }
  #add-review-form .star-group .star {
    color: #ddd;
    transition: all .2s ease;
    cursor: pointer;
    padding: 2px 3px;
    border-radius: 4px;
    position: relative;
  }
  #add-review-form .star-group .star:hover {
    color: #f5b301;
    transform: scale(1.15);
    text-shadow: 0 0 8px rgba(245, 179, 1, 0.3);
  }
  #add-review-form .star-group .star.selected {
    color: #f5b301;
    text-shadow: 0 0 8px rgba(245, 179, 1, 0.4);
    animation: starPulse .3s ease;
  }
  @keyframes starPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
  }
  #add-review-form textarea {
    grid-column: 1 / -1;
    grid-row: 2;
    min-height: 80px;
    padding: 12px 16px;
    border-radius: 8px;
    border: 2px solid #e8f5e8;
    resize: vertical;
    font-size: 1.05em;
    background: #fff;
    font-family: inherit;
    line-height: 1.5;
    transition: all 0.3s ease;
  }
  #add-review-form textarea:focus {
    outline: none;
    border-color: #6BBF59;
    box-shadow: 0 0 0 3px rgba(107, 191, 89, 0.1);
  }
  #add-review-form textarea::placeholder {
    color: #999;
    font-style: italic;
  }
  #add-review-form .image-upload-section {
    grid-column: 1;
    grid-row: 3;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  #add-review-form .image-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border: 2px solid #42a5f5;
    border-radius: 8px;
    color: #1565c0;
    font-size: 0.95em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
  }
  #add-review-form .image-upload-btn:hover {
    background: linear-gradient(135deg, #bbdefb, #90caf9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(66, 165, 245, 0.3);
  }
  #add-review-form .image-upload-btn i {
    font-size: 1.1em;
  }
  #add-review-form input[type="file"] {
    display: none;
  }
  #add-review-form .image-preview {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  #add-review-form .image-preview-item {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e0e0e0;
  }
  #add-review-form .image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  #add-review-form .image-preview-item .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    background: #e74c3c;
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }
  #add-review-form button[type=submit] {
    grid-column: 2;
    grid-row: 3;
    background: linear-gradient(135deg, #6BBF59, #4e9c3e);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 12px 28px;
    font-weight: 700;
    font-size: 1.1em;
    cursor: pointer;
    transition: all .3s ease;
    box-shadow: 0 4px 12px rgba(107, 191, 89, 0.3);
    justify-self: end;
    align-self: start;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  #add-review-form button[type=submit]:hover {
    background: linear-gradient(135deg, #4e9c3e, #3d7b2f);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(107, 191, 89, 0.4);
  }
  #add-review-form button[type=submit]:active {
    transform: translateY(0);
  }
  @media (max-width: 600px) {
    #add-review-form {
      grid-template-columns: 1fr;
      grid-template-rows: auto auto auto auto auto;
      gap: 12px;
    }
    #add-review-form input[name=username] {
      grid-column: 1;
      grid-row: 1;
    }
    #add-review-form .star-group {
      grid-column: 1;
      grid-row: 2;
      justify-self: center;
      font-size: 1.6em;
    }
    #add-review-form textarea {
      grid-column: 1;
      grid-row: 3;
      min-height: 70px;
    }
    #add-review-form .image-upload-section {
      grid-column: 1;
      grid-row: 4;
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
    }
    #add-review-form button[type=submit] {
      grid-column: 1;
      grid-row: 5;
      justify-self: stretch;
      padding: 14px 20px;
    }
  }
  </style>
  <div class="review-section">
    <div id="review-list"></div>
    <form id="add-review-form" autocomplete="off" enctype="multipart/form-data" method="POST">
      <input name="username" placeholder="Tên của bạn" required>
      <div class="star-group" id="star-group">
        <span class="star" data-star="1">&#9733;</span>
        <span class="star" data-star="2">&#9733;</span>
        <span class="star" data-star="3">&#9733;</span>
        <span class="star" data-star="4">&#9733;</span>
        <span class="star" data-star="5">&#9733;</span>
        <input type="hidden" name="star" id="star-input" required>
      </div>
      <textarea name="detail" placeholder="Chia sẻ trải nghiệm của bạn về món ăn này..." required></textarea>
      <div class="image-upload-section">
        <label for="review-images" class="image-upload-btn">
          <i class="fas fa-camera"></i>
          Thêm ảnh
        </label>
        <input type="file" id="review-images" name="images[]" multiple accept="image/*" onchange="debugFileInput(this)">
        <div class="image-preview" id="image-preview"></div>
      </div>
      <button type="submit">
        <i class="fas fa-paper-plane"></i> Gửi đánh giá
      </button>
    </form>
  </div>
  <script>
    // Star rating UI - Improved logic
    (function() {
      const stars = document.querySelectorAll('#star-group .star');
      const starInput = document.getElementById('star-input');
      let selected = 0;

      function highlightStars(count) {
        stars.forEach((star, index) => {
          if (index < count) {
            star.classList.add('selected');
          } else {
            star.classList.remove('selected');
          }
        });
      }

      stars.forEach((star, index) => {
        star.addEventListener('mouseenter', function() {
          highlightStars(index + 1);
        });

        star.addEventListener('mouseleave', function() {
          highlightStars(selected);
        });

        star.addEventListener('click', function() {
          selected = index + 1;
          starInput.value = selected;
          highlightStars(selected);
        });
      });
    })();

    // Image upload functionality
    (function() {
      const fileInput = document.getElementById('review-images');
      const imagePreview = document.getElementById('image-preview');

      // Initialize global selectedFiles array
      if (!window.selectedFiles) {
        window.selectedFiles = [];
      }
      let selectedFiles = window.selectedFiles;

      fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);

        files.forEach(file => {
          if (file.type.startsWith('image/') && selectedFiles.length < 3) {
            selectedFiles.push(file);

            const reader = new FileReader();
            reader.onload = function(e) {
              const previewItem = document.createElement('div');
              previewItem.className = 'image-preview-item';
              previewItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-btn" onclick="removeImage(${selectedFiles.length - 1})">×</button>
              `;
              imagePreview.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
          }
        });

        // Reset input to allow selecting same file again
        fileInput.value = '';
      });

      // Make removeImage function global
      window.removeImage = function(index) {
        window.selectedFiles.splice(index, 1);
        imagePreview.innerHTML = '';

        // Rebuild preview
        window.selectedFiles.forEach((file, i) => {
          const reader = new FileReader();
          reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.innerHTML = `
              <img src="${e.target.result}" alt="Preview">
              <button type="button" class="remove-btn" onclick="removeImage(${i})">×</button>
            `;
            imagePreview.appendChild(previewItem);
          };
          reader.readAsDataURL(file);
        });
      };
    })();
  </script>

  <script>
    // Define review functions inline
    function loadReviews(id_food) {
      console.log('GỌI loadReviews với id:', id_food);
      const reviewList = document.getElementById('review-list');
      if (!reviewList) {
        console.log('Không tìm thấy #review-list');
        return;
      }
      reviewList.innerHTML = '<div style="text-align:center;padding:20px;color:#666;"><i class="fa fa-spinner fa-spin"></i> Đang tải đánh giá...</div>';

      fetch(`/healthy/api/get_reviews.php?id_food=${id_food}`)
        .then((r) => {
          console.log('Response status:', r.status);
          return r.json();
        })
        .then((data) => {
          console.log('Parsed data:', data);

          if (!data.reviews || !data.reviews.length) {
            reviewList.innerHTML = '<div style="text-align:center;padding:30px;color:#999;background:#f8f8f8;border-radius:10px;border:2px dashed #ddd;"><i class="fa fa-comment-o" style="font-size:2em;margin-bottom:10px;display:block;"></i>Chưa có đánh giá nào cho món này.<br><small>Hãy là người đầu tiên đánh giá!</small></div>';
            return;
          }

          const reviewsHtml = data.reviews.map((rv) => {
            let photosHtml = '';
            if (rv.images && Array.isArray(rv.images) && rv.images.length > 0) {
              photosHtml = '<div class="review-photos" style="margin:10px 0 0 4px;display:flex;gap:8px;flex-wrap:wrap;">' +
                rv.images.map(filename => `<img src="/healthy/uploads/reviews/${filename}" style="width:80px;height:80px;border-radius:8px;border:2px solid #e8f0e8;object-fit:cover;cursor:pointer;" onclick="window.open(this.src, '_blank')">`).join('') +
                '</div>';
            }

            return `
              <div class="review-item">
                <div class="review-head">
                  <span class="review-user">${rv.username}</span>
                  <span class="review-star">${'★'.repeat(rv.star)}${'☆'.repeat(5 - rv.star)}</span>
                  <span class="review-date">${rv.date}</span>
                </div>
                <div class="review-detail">${rv.detail}</div>
                ${photosHtml}
              </div>
            `;
          });

          reviewList.innerHTML = reviewsHtml.join('');
          console.log('Reviews loaded successfully');
        })
        .catch((err) => {
          console.error('Fetch error:', err);
          reviewList.innerHTML = '<div style="text-align:center;padding:20px;color:#e74c3c;background:#fff5f5;border-radius:10px;border:2px solid #fecaca;"><i class="fa fa-exclamation-triangle"></i> Lỗi tải đánh giá.</div>';
        });
    }

    function addReviewHandler(id_food) {
      const form = document.getElementById('add-review-form');
      if (!form) {
        console.error('Form not found!');
        return;
      }

      console.log('Setting up form handler for id_food:', id_food);

      form.onsubmit = function (e) {
        e.preventDefault();
        console.log('Form submitted');

        const username = form.username.value.trim();
        const star = parseInt(form.star.value, 10);
        const detail = form.detail.value.trim();
        if (!username || !star || !detail) {
          alert('Vui lòng nhập đầy đủ thông tin đánh giá!');
          return;
        }

        const formData = new FormData();
        formData.append('id_food', id_food);
        formData.append('username', username);
        formData.append('star', star);
        formData.append('detail', detail);

        // Add images from selectedFiles array
        if (window.selectedFiles && window.selectedFiles.length > 0) {
          console.log('Adding files from selectedFiles array:', window.selectedFiles.length);
          for (let i = 0; i < window.selectedFiles.length; i++) {
            const file = window.selectedFiles[i];
            console.log(`File ${i}:`, {name: file.name, type: file.type, size: file.size});
            formData.append('images[]', file);
          }
        } else {
          console.log('No files in selectedFiles array');
        }

        // Debug FormData
        console.log('FormData entries:');
        for (let pair of formData.entries()) {
          console.log(pair[0], ':', pair[1]);
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
        submitBtn.disabled = true;

        fetch('/healthy/api/add_review.php', {
          method: 'POST',
          body: formData,
          // Don't set Content-Type header - let browser set it for multipart/form-data
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              form.reset();
              if (window.selectedFiles) window.selectedFiles = [];
              const imagePreview = document.getElementById('image-preview');
              if (imagePreview) imagePreview.innerHTML = '';
              document.querySelectorAll('#star-group .star').forEach((star) => {
                star.classList.remove('selected');
              });
              loadReviews(id_food);
              alert('Đánh giá đã được gửi thành công!');
            } else {
              alert(data.message || 'Lỗi gửi đánh giá!');
            }
          })
          .catch(() => alert('Lỗi kết nối server!'))
          .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
          });
      };
    }

    // Initialize reviews
    console.log('Initializing reviews for item:', <?= $id ?>);

    loadReviews(<?= $id ?>);
    addReviewHandler(<?= $id ?>);

    // Add missing functions to prevent errors
    if (typeof initializeFromStorage === 'undefined') {
      window.initializeFromStorage = function() {
        console.log('initializeFromStorage called (stub)');
      };
    }

    if (typeof updateCartIcon === 'undefined') {
      window.updateCartIcon = function() {
        console.log('updateCartIcon called (stub)');
      };
    }

    // Call the functions if they exist
    if (typeof initializeFromStorage === 'function') {
      initializeFromStorage();
    }
    if (typeof updateCartIcon === 'function') {
      updateCartIcon();
    }
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

.title-with-heart {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
  margin-bottom: .65rem;
}

.title-with-heart h2 {
  flex: 1;
  margin: 0;
}

.favorite-btn-large {
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #e9ecef;
  border-radius: 50%;
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.favorite-btn-large:hover {
  background: rgba(255, 255, 255, 1);
  border-color: #e74c3c;
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.favorite-btn-large i {
  font-size: 20px;
  color: #6c757d;
  transition: color 0.3s ease;
}

.favorite-btn-large.favorited {
  border-color: #e74c3c;
  background: rgba(231, 76, 60, 0.1);
}

.favorite-btn-large.favorited i {
  color: #e74c3c;
}

.favorite-btn-large:hover i {
  color: #e74c3c;
}
.item-detail-info h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: rgb(0,39,16);
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
  .qty-error-tab {
    display: none;
  }

.item-detail-container.qty-control input[type=number]::-webkit-inner-spin-button,
.item-detail-container.qty-control input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.item-detail-container.qty-control input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
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
