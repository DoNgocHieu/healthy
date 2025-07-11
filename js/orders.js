function updatePaymentStatus(orderId, status) {
	fetch('/healthy/admin/api/update_payment_status.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({ order_id: orderId, status }),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				// Refresh trang để cập nhật danh sách
				location.reload();
			} else {
				alert(
					data.message ||
						'Có lỗi xảy ra khi cập nhật trạng thái thanh toán'
				);
			}
		})
		.catch((error) => {
			console.error('Error:', error);
			alert('Có lỗi xảy ra khi cập nhật trạng thái thanh toán');
		});
}

function updateOrderStatus(orderId, status) {
	fetch('/healthy/admin/api/update_order_status.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({ order_id: orderId, status }),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				// Refresh trang để cập nhật danh sách
				location.reload();
			} else {
				alert(
					data.message ||
						'Có lỗi xảy ra khi cập nhật trạng thái đơn hàng'
				);
			}
		})
		.catch((error) => {
			console.error('Error:', error);
			alert('Có lỗi xảy ra khi cập nhật trạng thái đơn hàng');
		});
}

// Format currency
function formatPrice(price) {
	return new Intl.NumberFormat('vi-VN').format(price);
}

// Get text for order status
function getOrderStatusText(status) {
	const statusMap = {
		pending: 'Chờ xử lý',
		processing: 'Đang xử lý',
		shipping: 'Đang giao',
		delivered: 'Đã giao',
		cancelled: 'Đã hủy',
	};
	return statusMap[status] || status;
}

// Get text for payment status
function getPaymentStatusText(status) {
	const statusMap = {
		pending: 'Chờ thanh toán',
		paid: 'Đã thanh toán',
		failed: 'Thanh toán thất bại',
		refunded: 'Đã hoàn tiền',
	};
	return statusMap[status] || status;
}
