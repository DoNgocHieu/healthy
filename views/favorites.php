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

<style>
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

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.favorite-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.favorite-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.favorite-card-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.favorite-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.favorite-card:hover .favorite-card-image img {
    transform: scale(1.05);
}

.remove-favorite {
    position: absolute;
    top: 10px;
    right: 10px;
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
    opacity: 0;
    transform: scale(0.8);
}

.favorite-card:hover .remove-favorite {
    opacity: 1;
    transform: scale(1);
}

.remove-favorite:hover {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.remove-favorite i {
    color: #e74c3c;
    font-size: 16px;
}

.favorite-card-content {
    padding: 1.5rem;
}

.favorite-card-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c5530;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.favorite-card-price {
    font-size: 1.1rem;
    font-weight: 600;
    color: #e67e22;
    margin-bottom: 1rem;
}

.favorite-card-description {
    font-size: 0.9rem;
    color: #6c757d;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.favorite-card-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.add-to-cart-btn {
    flex: 1;
    background: #28a745;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-to-cart-btn:hover {
    background: #218838;
    transform: translateY(-1px);
}

.add-to-cart-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
}

.view-detail-btn {
    background: #17a2b8;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.view-detail-btn:hover {
    background: #138496;
    transform: translateY(-1px);
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

@media (max-width: 768px) {
    .favorites-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .favorites-header h1 {
        font-size: 2rem;
    }

    .favorite-card-content {
        padding: 1rem;
    }
}
</style>

<script>
// Load favorites khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    loadUserFavorites();
});

function loadUserFavorites() {
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
                    let html = '<div class="favorites-grid">';

                    data.favorites.forEach(item => {
                        const isOutOfStock = item.quantity <= 0;
                        html += `
                            <div class="favorite-card" data-item-id="${item.id}">
                                <div class="favorite-card-image">
                                    <img src="../img/${item.image_url}" alt="${item.name}">
                                    <button class="remove-favorite favorite-btn" data-item-id="${item.id}" title="Xóa khỏi yêu thích">
                                        <i class="fa-solid fa-heart"></i>
                                    </button>
                                </div>
                                <div class="favorite-card-content">
                                    <h3 class="favorite-card-title">${item.name}</h3>
                                    <div class="favorite-card-price">${new Intl.NumberFormat('vi-VN').format(item.price)} đ</div>
                                    <p class="favorite-card-description">${item.description}</p>
                                    <div class="favorite-card-actions">
                                        <button class="add-to-cart-btn ${isOutOfStock ? 'disabled' : ''}"
                                                ${isOutOfStock ? 'disabled' : ''}
                                                onclick="addToCartFromFavorites(${item.id})"
                                                data-item-id="${item.id}">
                                            ${isOutOfStock ? '<i class="fa-solid fa-lock"></i> Hết hàng' : '<i class="fa-solid fa-shopping-cart"></i> Thêm vào giỏ'}
                                        </button>
                                        <button class="view-detail-btn" onclick="showItemModalById(${item.id})">
                                            <i class="fa-solid fa-eye"></i> Xem
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                    container.innerHTML = html;

                    // Khởi tạo favorites manager cho các nút heart
                    if (window.favoritesManager) {
                        window.favoritesManager.initializeFavorites();
                    }
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
                        <button onclick="loadUserFavorites()" class="browse-menu-btn">
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
                    <button onclick="loadUserFavorites()" class="browse-menu-btn">
                        <i class="fa-solid fa-refresh"></i> Thử lại
                    </button>
                </div>
            `;
        });
}

function addToCartFromFavorites(itemId) {
    if (typeof addToCart === 'function') {
        addToCart(itemId);
    } else if (typeof window.addToCart === 'function') {
        window.addToCart(itemId);
    } else {
        console.error('Hàm addToCart không tồn tại');
        alert('Có lỗi xảy ra khi thêm vào giỏ hàng');
    }
}

// Listen cho sự kiện remove favorite để reload trang
document.addEventListener('favoriteRemoved', function(e) {
    // Xóa card khỏi giao diện
    const card = document.querySelector(`[data-item-id="${e.detail.itemId}"]`);
    if (card) {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.8)';
        setTimeout(() => {
            card.remove();

            // Kiểm tra nếu không còn item nào
            const remainingCards = document.querySelectorAll('.favorite-card');
            if (remainingCards.length === 0) {
                loadUserFavorites();
            }
        }, 300);
    }
});
</script>
