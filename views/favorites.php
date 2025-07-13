<?php
require_once __DIR__ . '/../config/config.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
}

$userId = $_SESSION['user_id'];
?>

<link rel="stylesheet" href="../css/catalog.css" />
<script defer src="../js/favorites.js"></script>
<script defer src="../js/qty.js"></script>

<div class="favorites-container">
    <div class="favorites-header">
        <h1><i class="fa-solid fa-heart"></i> Món Ăn Yêu Thích</h1>
        <p>Danh sách những món ăn bạn đã thêm vào yêu thích</p>
    </div>

    <div id="favorites-content">
        <div class="loading">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <p>Đang tải danh sách yêu thích...</p>
        </div>
    </div>
</div>

<div id="itemModalOverlay" class="item-modal-bg" style="display:none;">
  <div class="item-modal-box">
    <button class="modal-close" onclick="closeItemModal()"></button>
    <div id="itemModalContent"></div>
  </div>
</div>
    </div>
</div>

<style>
/* Favorites header giữ nguyên */
.favorites-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}
.favorites-header {
    text-align: center;
    margin-bottom: 3rem;
}
.favorites-header h1 {
    font-size: 2.5rem;
    color: #2c5530;
    margin-bottom: 0.5rem;
}
.favorites-header h1 i {
    color: #e74c3c;
    margin-right: 1rem;
}
.favorites-header p {
    font-size: 1.1rem;
    color: #6c757d;
    margin: 0;
}
.loading {
    text-align: center;
    padding: 4rem 0;
    color: #6c757d;
}
.loading i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}
/* Monmoi grid/card style cho favorites */
.monmoi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}
/* Card đẹp hơn, spacing, shadow, hover */
.monmoi-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(44,85,48,0.10);
    overflow: hidden;
    transition: box-shadow 0.3s, transform 0.3s;
    position: relative;
    padding-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 0.5rem;
}
.monmoi-card:hover {
    box-shadow: 0 12px 40px rgba(44,85,48,0.18);
    transform: translateY(-6px) scale(1.01);
}
.monmoi-card img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    border-radius: 18px 18px 0 0;
    box-shadow: 0 2px 8px rgba(44,85,48,0.08);
}
.monmoi-card .name {
    font-size: 1.35rem;
    font-weight: 700;
    color: #2c5530;
    margin: 1rem 0 0.5rem 0;
    text-align: center;
    letter-spacing: 0.5px;
}
.monmoi-card .price {
    font-size: 1.15rem;
    font-weight: 600;
    color: #e67e22;
    margin-bottom: 0.5rem;
    text-align: center;
    letter-spacing: 0.5px;
}
.monmoi-card .qty-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    justify-content: center;
}
.monmoi-card .qty-control button {
    background: #f5f5f5;
    color: #222;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(44,85,48,0.08);
    transition: background 0.2s, transform 0.2s;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.monmoi-card .qty-control button i.fa-shopping-cart {
    color: #222 !important;
    font-size: 1.2rem;
    margin-right: 2px;
}
.monmoi-card .qty-control button:hover:not(.disabled) {
    background: #eaeaea;
    transform: translateY(-1px) scale(1.05);
}
.monmoi-card .qty-control .disabled {
    background: #e0e0e0;
    color: #aaa;
    cursor: not-allowed;
    transform: none;
}
.monmoi-card .view-detail-btn {
    background: #222;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.2rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    transition: background 0.2s, transform 0.2s;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(44,85,48,0.08);
}
.monmoi-card .view-detail-btn:hover {
    background: #444;
    transform: translateY(-1px) scale(1.05);
}
.monmoi-card .card-header {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 2;
}
.monmoi-card .favorite-btn {
    background: rgba(255,255,255,0.95);
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(44,85,48,0.08);
    transition: box-shadow 0.2s, background 0.2s;
}
.monmoi-card .favorite-btn i {
    color: #e74c3c;
    font-size: 18px;
}
.monmoi-card .favorite-btn:hover {
    background: #fff;
    box-shadow: 0 4px 16px rgba(44,85,48,0.15);
}
/* Modal overlay */
.item-modal-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(44,85,48,0.15);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.item-modal-box {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    max-width: 480px;
    width: 100%;
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    position: relative;
}
.modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 32px;
    height: 32px;
    background: #fff;
    border: none;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    z-index: 2;
}
.modal-close:after {
    content: '\00d7';
    font-size: 22px;
    color: #e74c3c;
    display: block;
    text-align: center;
    line-height: 32px;
}
.empty-favorites {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}
.empty-favorites i {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
    display: block;
}
.empty-favorites h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #495057;
}
.empty-favorites p {
    font-size: 1.1rem;
    margin-bottom: 2rem;
}
.browse-menu-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}
.browse-menu-btn:hover {
    background: #218838;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}
.qty-display{
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
@media (max-width: 900px) {
    .monmoi-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.2rem;
    }
    .monmoi-card img {
        height: 200px;
    }
}
@media (max-width: 600px) {
    .monmoi-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .monmoi-card img {
        height: 160px;
    }
    .item-modal-box {
        max-width: 98vw;
        padding: 1rem 0.5rem 1rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('../api/get_favorites.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('favorites-content');
            if (data.status === 'success') {
                if (data.favorites.length === 0) {
                    container.innerHTML = `
                        <div class="empty-favorites">
                            <i class="fa-regular fa-heart"></i>
                            <h3>Chưa có món ăn yêu thích</h3>
                            <p>Hãy thêm những món ăn bạn thích vào danh sách yêu thích để dễ dàng tìm lại sau này!</p>
                            <a href="layout.php?page=monmoi" class="browse-menu-btn">
                                <i class="fa-solid fa-utensils"></i> Khám phá thực đơn
                            </a>
                        </div>
                    `;
                } else {
                    let html = '<div class="monmoi-grid">';
                    data.favorites.forEach(item => {
                        const isOutOfStock = item.quantity <= 0;
                        html += `
                            <div class="monmoi-card" data-item-id="${item.id}">
                              <div class="card-header">
                                <button class="favorite-btn" data-item-id="${item.id}" title="Xóa khỏi yêu thích" onclick="event.stopPropagation(); window.favoritesManager && window.favoritesManager.removeFavorite(${item.id});">
                                  <i class="fa-solid fa-heart"></i>
                                </button>
                              </div>
                              <img src="../img/${item.image_url}" alt="${item.name}">
                              <div class="name">${item.name}</div>
                              <div class="price">${new Intl.NumberFormat('vi-VN').format(item.price)} đ</div>
                              <div class="qty-control" id="cart-controls-${item.id}" onclick="event.stopPropagation()">
                                ${isOutOfStock ? `
                                  <button class="add-to-cart-btn disabled" onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-lock"></i>
                                  </button>
                                ` : `
                                  <button onclick="window.addToCart && window.addToCart(${item.id})">
                                    <i class="fa-solid fa-shopping-cart"></i> Thêm vào giỏ
                                  </button>
                                `}
                              </div>
                           
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                }
            } else if (data.status === 'not_logged_in') {
                container.innerHTML = `
                    <div class="empty-favorites">
                        <i class="fa-solid fa-user-lock"></i>
                        <h3>Vui lòng đăng nhập</h3>
                        <p>Bạn cần đăng nhập để xem danh sách món ăn yêu thích</p>
                        <a href="layout.php?page=login" class="browse-menu-btn">
                            <i class="fa-solid fa-sign-in-alt"></i> Đăng nhập
                        </a>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="empty-favorites">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <h3>Có lỗi xảy ra</h3>
                        <p>${data.message || 'Không thể tải danh sách yêu thích'}</p>
                        <button onclick="location.reload()" class="browse-menu-btn">
                            <i class="fa-solid fa-refresh"></i> Thử lại
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Lỗi load favorites:', error);
            document.getElementById('favorites-content').innerHTML = `
                <div class="empty-favorites">
                    <i class="fa-solid fa-wifi"></i>
                    <h3>Lỗi kết nối</h3>
                    <p>Không thể kết nối đến máy chủ</p>
                    <button onclick="location.reload()" class="browse-menu-btn">
                        <i class="fa-solid fa-refresh"></i> Thử lại
                    </button>
                </div>
            `;
        });
});

function showItemModalById(id) {
  fetch('../views/item.php?id=' + id, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax=1'
  })
    .then(res => res.text())
    .then(html => {
      document.getElementById('itemModalContent').innerHTML = html;
      document.getElementById('itemModalOverlay').style.display = 'block';
    });
}
function closeItemModal() {
  document.getElementById('itemModalOverlay').style.display = 'none';
}
</script>
