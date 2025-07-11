if (window.cartJsInitialized) {
	console.warn('cart.js đã được khởi tạo trước đó, bỏ qua.');
} else {
	window.cartJsInitialized = true;
	window.addEventListener('cart-updated', () => {
		refreshSummary();
		updateCartIcon?.();
	});

	document.addEventListener('DOMContentLoaded', () => {
		const buyBtn = document.querySelector('.cart-checkout-btn');
		const countEl = document.getElementById('selected-count');
		const checkAllEl = document.getElementById('check-all');

		// Thêm xử lý click cho nút Mua Hàng
		if (buyBtn) {
			buyBtn.addEventListener('click', () => {
				// Kiểm tra xem có món hàng nào được chọn không
				const selectedItems = document.querySelectorAll(
					'.cart-checkbox:checked'
				);
				if (selectedItems.length === 0) {
					alert('Vui lòng chọn ít nhất một món để mua hàng');
					return;
				}

				// Kiểm tra xem có địa chỉ giao hàng không
				const addressDetails =
					document.querySelector('.address-details');
				if (
					!addressDetails ||
					addressDetails.textContent.trim() ===
						'Vui lòng thêm địa chỉ'
				) {
					alert(
						'Vui lòng thêm địa chỉ giao hàng trước khi tiến hành thanh toán'
					);
					return;
				}

				// Chuyển đến trang thanh toán
				window.location.href = 'layout.php?page=checkout';
			});
		}

		function refreshSummary() {
			let totalQty = 0;
			let subtotal = 0;

			document.querySelectorAll('.cart-item').forEach((item) => {
				const qty = parseInt(item.querySelector('.cart-qty-input').value, 10) || 0;
				const line = parseInt(item.querySelector('.cart-line-total').textContent.replace(/\D/g, ''), 10) || 0;
				totalQty += qty;
				subtotal += line;
			});

			// Cập nhật nút Mua Hàng và đếm
			const checkoutCountEl = document.getElementById('checkout-count');
			if (checkoutCountEl) checkoutCountEl.textContent = totalQty;

			// Cập nhật các con số bên phải
			const fmt = (x) => x.toLocaleString('vi-VN') + ' đ';
			document.getElementById('subtotal').textContent = fmt(subtotal);
			document.getElementById('discount').textContent = '0 đ';
			document.getElementById('shipping').textContent = '0 đ';
			document.getElementById('total').textContent = fmt(subtotal);
		}
		function handleIncrease(itemId, qtyChange, maxQty) {
			const qtyInput = document.getElementById(`qty-${itemId}`);
			let qty = parseInt(qtyInput.value, 10);
			qty += qtyChange;

			if (qty < 1) qty = 1;
			if (qty > maxQty) qty = maxQty;

			fetch('cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: `ajax=1&id=${itemId}&qty=${qty}`,
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error('Lỗi kết nối máy chủ');
					}
					return response.json(); // parse the JSON data
				})
				.then((data) => {
					if (data.status === 'ok') {
						qtyInput.value = qty;
						// Cập nhật thông tin giao diện nếu cần thiết
					} else if (data.status === 'max_limit') {
						alert(`Chỉ còn ${data.stock_qty} sản phẩm trong kho.`);
					} else {
						alert('Cập nhật thất bại.');
					}
				})
				.catch((error) => {
					console.error('Lỗi kết nối:', error);
					alert('Lỗi kết nối máy chủ. Vui lòng thử lại.');
				});
		}

		function updateQty(itemId, newQty) {
			fetch('cart.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: `ajax=1&id=${itemId}&qty=${newQty}`,
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error('Lỗi kết nối máy chủ');
					}
					return response.json(); // Parse dữ liệu JSON
				})
				.then((data) => {
					if (data.status === 'ok') {
						const itemEl = document.querySelector(
							`.cart-item[data-id="${itemId}"]`
						);
						if (itemEl) {
							const lineTotalElement =
								itemEl.querySelector('.cart-line-total');
							if (lineTotalElement) {
								lineTotalElement.textContent = data.line_total;
							} else {
								console.error(
									'Không tìm thấy phần tử .cart-line-total'
								);
							}
							const grandTotalElement =
								document.getElementById('grand-total');
							if (grandTotalElement) {
								grandTotalElement.textContent =
									data.grand_total;
							}
							const totalElement =
								document.getElementById('total');
							if (totalElement) {
								totalElement.textContent = data.grand_total;
							}
							refreshSummary(); // Cập nhật tổng giỏ hàng
						} else {
							console.error('Không tìm thấy phần tử .cart-item');
						}
					} else if (data.status === 'max_limit') {
						alert(`Chỉ còn ${data.stock_qty} sản phẩm trong kho.`);
					} else {
						alert('Cập nhật thất bại.');
					}
				})
				.catch((error) => {
					console.error('Lỗi kết nối:', error);
					// alert('Lỗi kết nối máy chủ. Vui lòng thử lại.');
				});
		}
		document
			.querySelector('.qty-increase')
			.addEventListener('click', function (e) {
				e.preventDefault();
				const button = this;
				button.disabled = true;

				setTimeout(() => {
					button.disabled = false;
				}, 10);
			});
		// CHECK ALL
		if (checkAllEl) {
			checkAllEl.addEventListener('change', () => {
				document
					.querySelectorAll('.cart-checkbox')
					.forEach((cb) => (cb.checked = checkAllEl.checked));
				refreshSummary();
			});
		}

		// Checkbox riêng
		document
			.querySelectorAll('.cart-checkbox')
			.forEach((cb) => cb.addEventListener('change', refreshSummary));

		// Tăng số lượng
		document.querySelectorAll('.qty-increase').forEach((btn) => {
			btn.addEventListener('click', () => {
				const item = btn.closest('.cart-item');
				const input = item.querySelector('.cart-qty-input');
				const id = item.dataset.id;
				const max = parseInt(input.max, 10) || Infinity;
				let qty = parseInt(input.value, 10) || 1;
				if (qty < max) {
					qty++;
					input.value = qty;
					updateQty(id, qty);
				} else {
					alert(`Số lượng tối đa là ${max}.`);
				}
			});
		});

		// Giảm số lượng
		document.querySelectorAll('.qty-decrease').forEach((btn) => {
			btn.addEventListener('click', () => {
				const item = btn.closest('.cart-item');
				const input = item.querySelector('.cart-qty-input');
				const id = item.dataset.id;
				let qty = parseInt(input.value, 10) || 1;
				if (qty > 1) {
					qty--;
					input.value = qty;
					updateQty(id, qty);
				} else {
					if (confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
						window.location.href = `cart.php?del=${id}`;
					}
				}
			});
		});

		// Nhập tay số lượng
		document.querySelectorAll('.cart-qty-input').forEach((input) => {
			input.addEventListener('change', () => {
				const id = input.dataset.id;
				let qty = parseInt(input.value, 10);
				if (!qty || qty < 1) qty = 1;
				if (qty > parseInt(input.max, 10)) {
					qty = parseInt(input.max, 10);
					alert(`Chỉ còn ${qty} sản phẩm trong kho.`);
				}
				input.value = qty;
				updateQty(id, qty);
			});
		});

		// Nút xóa
		document.querySelectorAll('.cart-remove').forEach((link) => {
			link.addEventListener('click', (e) => {
				e.preventDefault();
				const url = link.getAttribute('href');

				fetch(url, { method: 'GET' })
					.then((res) => {
						if (!res.ok) throw new Error('Xóa lỗi');
						// Xóa DOM node
						const itemEl = link.closest('.cart-item');
						itemEl.remove();
						refreshSummary();
					})
					.catch(() => alert('Xóa thất bại'));
			});
		});
		// Khởi tạo summary lần đầu
		refreshSummary();
	});
	function refreshSummary() {
		let totalQty = 0;
		let subtotal = 0;

		document.querySelectorAll('.cart-item').forEach((item) => {
			const qty = parseInt(item.querySelector('.cart-qty-input').value, 10) || 0;
			const line = parseInt(item.querySelector('.cart-line-total').textContent.replace(/\D/g, ''), 10) || 0;
			totalQty += qty;
			subtotal += line;
		});

		// Cập nhật nút Mua Hàng và đếm
		const checkoutCountEl = document.getElementById('checkout-count');
		if (checkoutCountEl) checkoutCountEl.textContent = totalQty;

		// Cập nhật các con số bên phải
		const fmt = (x) => x.toLocaleString('vi-VN') + ' đ';
		document.getElementById('subtotal').textContent = fmt(subtotal);
		document.getElementById('discount').textContent = '0 đ';
		document.getElementById('shipping').textContent = '0 đ';
		document.getElementById('total').textContent = fmt(subtotal);

		// Cập nhật checkbox “Chọn tất cả”
		const allCb = document.getElementById('check-all');
		const itemsCb = Array.from(document.querySelectorAll('.cart-checkbox'));
		const checkedCb = itemsCb.filter((c) => c.checked);
		if (allCb)
			allCb.checked =
				itemsCb.length > 0 && checkedCb.length === itemsCb.length;
	}

	// Gắn sự kiện
	document.getElementById('check-all')?.addEventListener('change', (e) => {
		document
			.querySelectorAll('.cart-checkbox')
			.forEach((cb) => (cb.checked = e.target.checked));
		refreshSummary();
	});

	document
		.querySelectorAll('.cart-checkbox')
		.forEach((cb) => cb.addEventListener('change', refreshSummary));
	refreshSummary();
}


