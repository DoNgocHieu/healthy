(function (window, document) {
	'use strict';
	window.handleDecrease = (id) => window.decrement(id);
	window.handleIncrease = (id) => window.increment(id);

	const BASE_PATH = `${window.location.origin}/healthy/views/layout.php`;
	const CART_ENDPOINT = `${BASE_PATH}?page=cart`;
	const LOGIN_ENDPOINT = `${BASE_PATH}?page=login`;

	function checkLoginStatus() {
		if (!window.isLoggedIn) {
			alert('Vui lòng đăng nhập');
			window.location.href = LOGIN_ENDPOINT;
			return false;
		}
		return true;
	}

	function debugLog(...args) {
		console.log('[CartSync]', ...args);
	}

	function getStock(container) {
		const s = parseInt(container.dataset.stock, 10);
		return isNaN(s) || s < 0 ? Number.MAX_SAFE_INTEGER : s;
	}

	function updateCartIcon() {
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		const total = Object.values(cart).reduce(
			(sum, it) => sum + (it.qty || 0),
			0
		);
		const icon = document.querySelector('#cart-icon');
		if (!icon) return;
		icon.innerHTML = `
	  <i class="fa fa-shopping-cart"></i>
	  ${total > 0 ? `<span class="cart-badge">${total}</span>` : ''}
	`;
	}

	function syncWithServer(id, qty) {
		debugLog('sync', id, qty);
		fetch(CART_ENDPOINT, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: `ajax=1&id=${id}&qty=${qty}`,
		})
			.then((r) => r.text())
			.then((text) => {
				debugLog('resp raw', text);
				let data;
				try {
					data = JSON.parse(text);
				} catch (e) {
					console.error('Invalid JSON', text);
					return alert('Server trả về không hợp lệ');
				}
				handleServerResponse(id, data);
			})
			.catch((err) => {
				console.error('AJAX error', err);
				alert('Không thể kết nối server');
			});
	}

	function handleServerResponse(id, data) {
		if (data.status === 'ok') {
			const line = document.querySelector(
				`.cart-line-total[data-id="${id}"]`
			);
			if (line) line.textContent = data.line_total;
		} else if (data.status === 'max_limit') {
			alert(`Chỉ còn ${data.stock_qty} sản phẩm`);
			forceQty(id, data.stock_qty);
		} else {
			alert('Cập nhật thất bại');
		}

		updateCartIcon();
		initializeControls();
	}

	function forceQty(id, max) {
		const inp = document.getElementById(`qty-input-${id}`);
		if (inp) inp.value = max;
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		cart[id] = { qty: max, stock_qty: max };
		localStorage.setItem('cart', JSON.stringify(cart));
	}
	function renderContainer(container, id, qty) {
		const stock = getStock(container);

		// chưa login → show lock
		if (!window.isLoggedIn) {
			container.innerHTML = `
		<button class="lock-btn"
				onclick="location.href='${LOGIN_ENDPOINT}'">
		  <i class="fa-solid fa-lock"></i>
		</button>`;
			return;
		}

		//login & qty=0 → thêm vào giỏ
		if (qty <= 0) {
			container.innerHTML = `
		<button class="add-btn"
				onclick="event.stopPropagation(); addToCart(${id});"
				style="background:none;border:none;cursor:pointer;">
		  <i class="fa fa-shopping-cart" style="font-size:1.5em;color:#4caf50;"></i>
		</button>`;
			return;
		}

		// login & qty>0 → ± input
		container.innerHTML = `
	  <button onclick="event.stopPropagation(); decrement(${id});">
		<i class="fa-solid fa-minus"></i>
	  </button>
	  <input
		id="qty-input-${id}"
		class="qty-display"
		type="number"
		min="1"
		max="${stock}"
		value="${qty}"
		oninput="handleQtyInput(${id});"
		onblur="handleQtyBlur(${id});"
	  />
	  <button onclick="event.stopPropagation(); increment(${id});">
		<i class="fa-solid fa-plus"></i>
	  </button>`;
	}

	// re-scan và render lại **tất cả** containers + patch modal
	function initializeControls() {
		const cart = JSON.parse(localStorage.getItem('cart')) || {};

		// dynamic containers
		document.querySelectorAll('[id^="cart-controls-"]').forEach((c) => {
			const id = c.id.replace('cart-controls-', '');
			const q = cart[id]?.qty || 0;
			renderContainer(c, id, q);
		});

		// patch những input cứng trong modal item.php
		document.querySelectorAll('input[id^="qty-input-"]').forEach((inp) => {
			const id = inp.id.replace('qty-input-', '');
			const q = cart[id]?.qty || 0;
			inp.value = q;
			// gán nút trước và sau
			const dec = inp.previousElementSibling;
			const inc = inp.nextElementSibling;
			if (dec)
				dec.onclick = (e) => {
					e.stopPropagation();
					decrement(id);
				};
			if (inc)
				inc.onclick = (e) => {
					e.stopPropagation();
					increment(id);
				};
			inp.oninput = () => handleQtyInput(id);
			inp.onblur = () => handleQtyBlur(id);
		});

		// patch icon thêm trong modal
		document.querySelectorAll('.add-to-cart-icon').forEach((icon) => {
			const m = icon.getAttribute('onclick')?.match(/addToCart\((\d+)\)/);
			if (m)
				icon.onclick = (e) => {
					e.stopPropagation();
					addToCart(m[1]);
				};
		});
	}

	window.addToCart = (id) => {
		if (!checkLoginStatus()) return;
		const cont = document.getElementById(`cart-controls-${id}`);
		let stock = 99;
		if (cont) {
			stock = getStock(cont);
		}
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		cart[id] = cart[id] || { qty: 0, stock_qty: stock };
		cart[id].qty = Math.min(cart[id].qty + 1, stock);
		localStorage.setItem('cart', JSON.stringify(cart));
		syncWithServer(id, cart[id].qty);
		updateCartIcon();
	};

	window.increment = (id) => {
		const cont = document.getElementById(`cart-controls-${id}`);
		const stock = getStock(cont);
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		cart[id] = cart[id] || { qty: 0, stock_qty: stock };
		if (cart[id].qty < stock) {
			cart[id].qty++;
			localStorage.setItem('cart', JSON.stringify(cart));
			syncWithServer(id, cart[id].qty);
		} else {
			alert(`Chỉ còn ${stock} sản phẩm`);
		}
	};

	window.decrement = (id) => {
		const cont = document.getElementById(`cart-controls-${id}`);
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		if (!cart[id] || cart[id].qty <= 1) {
			cart[id] = {
				qty: 0,
				stock_qty: cart[id]?.stock_qty || getStock(cont),
			};
			localStorage.setItem('cart', JSON.stringify(cart));
			syncWithServer(id, 0);
		} else {
			cart[id].qty--;
			localStorage.setItem('cart', JSON.stringify(cart));
			syncWithServer(id, cart[id].qty);
		}
	};
	window.handleQuantity = (id, delta, stock) => {
		// kiểm tra login
		if (!window.isLoggedIn) {
			alert('Vui lòng đăng nhập');
			return (window.location.href = LOGIN_ENDPOINT);
		}

		// load cart, khởi tạo nếu chưa có
		const cart = JSON.parse(localStorage.getItem('cart')) || {};
		cart[id] = cart[id] || { qty: 0, stock_qty: stock };

		// tính newQty và clamp vào [0, stock]
		let newQty = (cart[id].qty || 0) + delta;
		if (newQty > stock) {
			alert(`Chỉ còn ${stock} sản phẩm`);
			newQty = stock;
		}
		if (newQty < 0) {
			newQty = 0;
		}

		// cập nhật localStorage
		cart[id].qty = newQty;
		localStorage.setItem('cart', JSON.stringify(cart));

		// cập nhật ngay giá trị trên input (nếu có)
		const inp = document.getElementById(`qty-input-${id}`);
		if (inp) inp.value = newQty;

		// gửi lên server
		syncWithServer(id, newQty);
	};

	// alias cho 2 tên cũ
	window.handleIncrease = (id, delta, stock) =>
		window.handleQuantity(id, delta, stock);
	window.handleDecrease = (id, stock) => window.handleQuantity(id, -1, stock);
	function bootstrap() {
		if (!window.isLoggedIn) {
			// nếu chưa login thì xóa cache cart
			localStorage.removeItem('cart');
			updateCartIcon();
			initializeControls();
			return;
		}
		// load cart từ server
		fetch(`${CART_ENDPOINT}&ajax=load`, { credentials: 'same-origin' })
			.then((r) => r.json())
			.then((data) => {
				if (data.status === 'ok' && data.cart) {
					localStorage.setItem('cart', JSON.stringify(data.cart));
				} else {
					localStorage.removeItem('cart');
				}
			})
			.catch(() => localStorage.removeItem('cart'))
			.finally(() => {
				updateCartIcon();
				initializeControls();
			});
	}

	// DOMContentLoaded
	document.addEventListener('DOMContentLoaded', bootstrap);

	// Nếu script load sau DOMContentLoaded (ví dụ inject modal), khởi chạy ngay
	if (document.readyState !== 'loading') bootstrap();
	function showStockInfo(stock) {
		alert(`Hiện còn ${stock} sản phẩm trong kho.`);
	}
	function loadReviews(id_food) {
		console.log('GỌI loadReviews với id:', id_food);
		const reviewList = document.getElementById('review-list');
		if (!reviewList) {
			console.log('Không tìm thấy #review-list');
			return;
		}
		reviewList.innerHTML =
			'<div style="text-align:center;padding:20px;color:#666;"><i class="fa fa-spinner fa-spin"></i> Đang tải đánh giá...</div>';
		fetch(`/healthy/api/get_reviews.php?id_food=${id_food}`)
			.then((r) => {
				console.log('Response status:', r.status);
				console.log('Response URL:', r.url);
				return r.json();
			})
			.then((data) => {
				console.log('Parsed data:', data);
				console.log('Data.reviews:', data.reviews);
				console.log(
					'Reviews length:',
					data.reviews ? data.reviews.length : 'undefined'
				);

				if (!data.reviews || !data.reviews.length) {
					reviewList.innerHTML =
						'<div style="text-align:center;padding:30px;color:#999;background:#f8f8f8;border-radius:10px;border:2px dashed #ddd;"><i class="fa fa-comment-o" style="font-size:2em;margin-bottom:10px;display:block;"></i>Chưa có đánh giá nào cho món này.<br><small>Hãy là người đầu tiên đánh giá!</small></div>';
					return;
				}

				console.log('Processing reviews...');

				const reviewsHtml = data.reviews.map((rv, index) => {
					console.log(`Processing review ${index}:`, rv);
					let photosHtml = '';

					// Handle new images format
					if (
						rv.images &&
						Array.isArray(rv.images) &&
						rv.images.length > 0
					) {
						console.log('Found images:', rv.images);
						photosHtml =
							'<div class="review-photos" style="margin:10px 0 0 4px;display:flex;gap:8px;flex-wrap:wrap;">' +
							rv.images
								.map(
									(filename) =>
										`<img src="/healthy/uploads/reviews/${filename}" style="width:80px;height:80px;border-radius:8px;border:2px solid #e8f0e8;object-fit:cover;cursor:pointer;" onclick="window.open(this.src, '_blank')">`
								)
								.join('') +
							'</div>';
					}
					// Handle old photos format (fallback)
					else if (rv.photos) {
						console.log('Found old photos:', rv.photos);
						try {
							let arr = [];
							if (rv.photos.trim().startsWith('[')) {
								arr = JSON.parse(rv.photos);
							} else if (rv.photos.trim() !== '') {
								arr = [rv.photos.trim()];
							}
							if (Array.isArray(arr) && arr.length) {
								photosHtml =
									'<div class="review-photos" style="margin:10px 0 0 4px;display:flex;gap:8px;flex-wrap:wrap;">' +
									arr
										.map(
											(url) =>
												`<img src="${url}" style="width:80px;height:80px;border-radius:8px;border:2px solid #e8f0e8;object-fit:cover;cursor:pointer;" onclick="window.open(this.src, '_blank')">`
										)
										.join('') +
									'</div>';
							}
						} catch (e) {
							console.error('Error parsing old photos:', e);
						}
					}

					return `
			<div class="review-item">
			  <div class="review-head">
				<span class="review-user">${rv.username}</span>
				<span class="review-star">${'★'.repeat(rv.star)}${'☆'.repeat(
						5 - rv.star
					)}</span>
				<span class="review-date">${rv.date}</span>
			  </div>
			  <div class="review-detail">${rv.detail}</div>
			  ${photosHtml}
			</div>
		  `;
				});

				console.log('Generated HTML:', reviewsHtml);
				reviewList.innerHTML = reviewsHtml.join('');
				console.log('Reviews loaded successfully');
			})
			.catch((err) => {
				console.error('Fetch error:', err);
				reviewList.innerHTML =
					'<div style="text-align:center;padding:20px;color:#e74c3c;background:#fff5f5;border-radius:10px;border:2px solid #fecaca;"><i class="fa fa-exclamation-triangle"></i> Lỗi tải đánh giá.</div>';
			});
	}
	window.loadReviews = loadReviews;
	function addReviewHandler(id_food) {
		// Wait for modal to be fully loaded
		setTimeout(() => {
			const form = document.getElementById('add-review-form');
			if (!form) {
				console.error('Form not found!');
				return;
			}

			console.log('Setting up form handler for id_food:', id_food);

			form.onsubmit = function (e) {
				e.preventDefault();
				console.log('Form submitted');
				console.log('selectedFiles global:', window.selectedFiles);
				console.log(
					'selectedFiles length:',
					window.selectedFiles
						? window.selectedFiles.length
						: 'undefined'
				);

				const username = form.username.value.trim();
				const star = parseInt(form.star.value, 10);
				const detail = form.detail.value.trim();
				if (!username || !star || !detail) {
					alert('Vui lòng nhập đầy đủ thông tin đánh giá!');
					return;
				}

				// Create FormData to handle file uploads
				const formData = new FormData();
				formData.append('id_food', id_food);
				formData.append('username', username);
				formData.append('star', star);
				formData.append('detail', detail);

				// Add images from selectedFiles array (not from input)
				if (window.selectedFiles && window.selectedFiles.length > 0) {
					console.log(
						'Adding files from selectedFiles array:',
						window.selectedFiles.length
					);
					for (let i = 0; i < window.selectedFiles.length; i++) {
						const file = window.selectedFiles[i];
						console.log(`File ${i}:`, {
							name: file.name,
							type: file.type,
							size: file.size,
						});
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

				// Show loading state
				const submitBtn = form.querySelector('button[type="submit"]');
				const originalText = submitBtn.innerHTML;
				submitBtn.innerHTML =
					'<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
				submitBtn.disabled = true;

				fetch('/healthy/api/add_review.php', {
					method: 'POST',
					body: formData,
				})
					.then((r) => r.json())
					.then((data) => {
						if (data.success) {
							form.reset();
							// Clear selectedFiles array
							if (window.selectedFiles) window.selectedFiles = [];
							// Clear image preview
							const imagePreview =
								document.getElementById('image-preview');
							if (imagePreview) imagePreview.innerHTML = '';
							// Reset star rating
							document
								.querySelectorAll('#star-group .star')
								.forEach((star) => {
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
						// Restore button state
						submitBtn.innerHTML = originalText;
						submitBtn.disabled = false;
					});
			};
		}, 100); // Wait 100ms for modal to load
	}
	window.addReviewHandler = addReviewHandler;
})(window, document);
