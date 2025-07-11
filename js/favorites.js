// Favorites functionality
class FavoritesManager {
	constructor() {
		this.init();
	}

	init() {
		// Khởi tạo khi DOM loaded
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', () =>
				this.initializeFavorites()
			);
		} else {
			this.initializeFavorites();
		}
	}

	initializeFavorites() {
		// Tìm tất cả heart icons và khởi tạo
		const heartIcons = document.querySelectorAll('.favorite-btn');
		heartIcons.forEach((icon) => this.attachFavoriteHandler(icon));

		// Load trạng thái yêu thích cho tất cả items hiện có
		this.loadFavoriteStates();
	}

	loadFavoriteStates() {
		// Lấy tất cả item IDs trên trang hiện tại
		const itemElements = document.querySelectorAll('[data-item-id]');
		itemElements.forEach((element) => {
			const itemId = element.getAttribute('data-item-id');
			if (itemId) {
				this.checkFavoriteStatus(itemId);
			}
		});
	}

	checkFavoriteStatus(itemId) {
		if (!window.isLoggedIn) return;

		fetch(`../api/favorites.php?item_id=${itemId}`)
			.then((response) => response.json())
			.then((data) => {
				if (data.status === 'success') {
					this.updateHeartIcon(itemId, data.is_favorite);
				}
			})
			.catch((error) => {
				console.error('Lỗi kiểm tra trạng thái yêu thích:', error);
			});
	}

	updateHeartIcon(itemId, isFavorite) {
		const heartBtn = document.querySelector(
			`.favorite-btn[data-item-id="${itemId}"]`
		);
		if (heartBtn) {
			const icon = heartBtn.querySelector('i');
			if (isFavorite) {
				icon.classList.remove('fa-regular');
				icon.classList.add('fa-solid');
				heartBtn.classList.add('favorited');
				heartBtn.title = 'Xóa khỏi yêu thích';
			} else {
				icon.classList.remove('fa-solid');
				icon.classList.add('fa-regular');
				heartBtn.classList.remove('favorited');
				heartBtn.title = 'Thêm vào yêu thích';
			}
		}
	}

	attachFavoriteHandler(heartBtn) {
		if (heartBtn.hasAttribute('data-favorite-initialized')) return;

		heartBtn.addEventListener('click', (e) => {
			e.preventDefault();
			e.stopPropagation();
			this.toggleFavorite(heartBtn);
		});

		heartBtn.setAttribute('data-favorite-initialized', 'true');
	}

	toggleFavorite(heartBtn) {
		if (!window.isLoggedIn) {
			this.showMessage(
				'Vui lòng đăng nhập để sử dụng tính năng yêu thích',
				'warning'
			);
			return;
		}

		const itemId = heartBtn.getAttribute('data-item-id');
		if (!itemId) {
			this.showMessage('Không tìm thấy ID món ăn', 'error');
			return;
		}

		const isFavorited = heartBtn.classList.contains('favorited');
		const action = isFavorited ? 'remove' : 'add';

		// Tạm thời thay đổi giao diện để UX mượt mà
		this.updateHeartIcon(itemId, !isFavorited);

		const formData = new FormData();
		formData.append('item_id', itemId);
		formData.append('action', action);

		fetch('../api/favorites.php', {
			method: 'POST',
			body: formData,
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.status === 'success') {
					this.showMessage(data.message, 'success');

					// Emit custom event để các trang khác có thể lắng nghe
					const event = new CustomEvent(
						data.action === 'added'
							? 'favoriteAdded'
							: 'favoriteRemoved',
						{
							detail: { itemId: itemId, action: data.action },
						}
					);
					document.dispatchEvent(event);
				} else {
					// Khôi phục trạng thái cũ nếu có lỗi
					this.updateHeartIcon(itemId, isFavorited);
					this.showMessage(data.message || 'Có lỗi xảy ra', 'error');
				}
			})
			.catch((error) => {
				// Khôi phục trạng thái cũ nếu có lỗi
				this.updateHeartIcon(itemId, isFavorited);
				this.showMessage('Lỗi kết nối', 'error');
				console.error('Lỗi toggle favorite:', error);
			});
	}

	showMessage(message, type = 'info') {
		// Tạo thông báo toast
		const toast = document.createElement('div');
		toast.className = `toast toast-${type}`;
		toast.textContent = message;

		// CSS styling
		toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 300px;
            word-wrap: break-word;
        `;

		// Màu sắc theo loại thông báo
		switch (type) {
			case 'success':
				toast.style.backgroundColor = '#28a745';
				break;
			case 'error':
				toast.style.backgroundColor = '#dc3545';
				break;
			case 'warning':
				toast.style.backgroundColor = '#ffc107';
				toast.style.color = '#212529';
				break;
			default:
				toast.style.backgroundColor = '#17a2b8';
		}

		document.body.appendChild(toast);

		// Hiện thông báo
		setTimeout(() => {
			toast.style.opacity = '1';
			toast.style.transform = 'translateX(0)';
		}, 100);

		// Ẩn và xóa thông báo
		setTimeout(() => {
			toast.style.opacity = '0';
			toast.style.transform = 'translateX(100%)';
			setTimeout(() => {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 300);
		}, 3000);
	}

	// Utility method để thêm heart icon vào một item
	addHeartIcon(itemId, container) {
		const heartBtn = document.createElement('button');
		heartBtn.className = 'favorite-btn';
		heartBtn.setAttribute('data-item-id', itemId);
		heartBtn.title = 'Thêm vào yêu thích';
		heartBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';

		container.appendChild(heartBtn);
		this.attachFavoriteHandler(heartBtn);

		// Kiểm tra trạng thái yêu thích
		this.checkFavoriteStatus(itemId);

		return heartBtn;
	}
}

// Khởi tạo favorites manager
window.favoritesManager = new FavoritesManager();

// Export cho sử dụng global
window.toggleFavorite = (itemId) => {
	const heartBtn = document.querySelector(
		`.favorite-btn[data-item-id="${itemId}"]`
	);
	if (heartBtn) {
		window.favoritesManager.toggleFavorite(heartBtn);
	}
};

window.addFavoriteIcon = (itemId, container) => {
	return window.favoritesManager.addHeartIcon(itemId, container);
};
