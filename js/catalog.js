// Toggle favorite
function toggleFavorite(itemId) {
	$.ajax({
		url: '/healthy/api/catalog/toggle_favorite.php',
		method: 'POST',
		data: { item_id: itemId },
		success: function (response) {
			if (response.success) {
				const btn = $(
					`.btn-favorite[onclick="toggleFavorite(${itemId})"]`
				);
				if (response.is_favorite) {
					btn.addClass('active');
				} else {
					btn.removeClass('active');
				}
				showToast(response.message);
			} else {
				if (response.message === 'Unauthorized') {
					window.location.href = '/healthy/views/auth/login.php';
				} else {
					showToast(response.message, 'danger');
				}
			}
		},
	});
}

// Add to cart
function addToCart(itemId) {
	const quantity = $('#quantity').val() || 1;
	$.ajax({
		url: '/healthy/api/cart/add_item.php',
		method: 'POST',
		data: {
			item_id: itemId,
			quantity: quantity,
		},
		success: function (response) {
			if (response.success) {
				showToast(response.message);
				updateCartCount(response.cart_count);
			} else {
				if (response.message === 'Unauthorized') {
					window.location.href = '/healthy/views/auth/login.php';
				} else {
					showToast(response.message, 'danger');
				}
			}
		},
	});
}

// Update quantity
function updateQuantity(change) {
	const input = $('#quantity');
	let value = parseInt(input.val()) + change;
	if (value < 1) value = 1;
	input.val(value);
}

// Show toast notification
function showToast(message, type = 'success') {
	const toast = `
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;

	$('body').append(toast);
	const toastEl = $('.toast').toast('show');
	toastEl.on('hidden.bs.toast', function () {
		$(this).parent().remove();
	});
}

// Update cart count in header
function updateCartCount(count) {
	const cartCount = $('.cart-count');
	if (cartCount.length) {
		cartCount.text(count);
		if (count > 0) {
			cartCount.removeClass('d-none');
		} else {
			cartCount.addClass('d-none');
		}
	}
}

// Image preview in reviews
$(document).on('click', '.review-images img', function () {
	const src = $(this).attr('src');
	const modal = `
        <div class="modal fade" id="imagePreview" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2"
                                data-bs-dismiss="modal"></button>
                        <img src="${src}" class="img-fluid" alt="Review image">
                    </div>
                </div>
            </div>
        </div>
    `;

	$('body').append(modal);
	const modalEl = $('#imagePreview');
	modalEl.modal('show');
	modalEl.on('hidden.bs.modal', function () {
		$(this).remove();
	});
});

// Initialize tooltips
$(function () {
	$('[data-bs-toggle="tooltip"]').tooltip();
});
