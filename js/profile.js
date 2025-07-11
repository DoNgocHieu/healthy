$(document).ready(function () {
	// Handle profile form submission
	$('#profile-form').on('submit', function (e) {
		e.preventDefault();
		$.ajax({
			url: '/healthy/views/user/profile.php',
			method: 'POST',
			data: $(this).serialize(),
			success: function (response) {
				if (response.success) {
					showAlert('success', response.message);
				} else {
					showAlert('danger', response.message);
				}
			},
		});
	});

	// Handle avatar change
	$('#avatar-input').on('change', function () {
		const formData = new FormData($('#avatar-form')[0]);
		$.ajax({
			url: '/healthy/views/user/profile.php',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				if (response.success) {
					location.reload();
				} else {
					showAlert('danger', response.message);
				}
			},
		});
	});

	// Handle password form submission
	$('#password-form').on('submit', function (e) {
		e.preventDefault();
		if ($('#new_password').val() !== $('#confirm_password').val()) {
			showAlert('danger', 'Mật khẩu xác nhận không khớp');
			return;
		}
		$.ajax({
			url: '/healthy/views/user/profile.php',
			method: 'POST',
			data: $(this).serialize(),
			success: function (response) {
				if (response.success) {
					showAlert('success', response.message);
					$('#password-form')[0].reset();
				} else {
					showAlert('danger', response.message);
				}
			},
		});
	});

	// Handle address form submission
	$('#address-form').on('submit', function (e) {
		e.preventDefault();
		saveAddress();
	});
});

// Show bootstrap alert
function showAlert(type, message) {
	const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
	$('.tab-pane.active .card-body').prepend(alertHtml);
	setTimeout(() => {
		$('.alert').alert('close');
	}, 5000);
}

// Edit address
function editAddress(addressId) {
	$.get(
		'/healthy/api/user/get_address.php',
		{ id: addressId },
		function (response) {
			if (response.success) {
				const address = response.address;
				$('#addressModal').modal('show');
				$('#addressModal .modal-title').text('Sửa địa chỉ');
				$('#address-form [name="action"]').val('update_address');
				$('#address-form [name="address_id"]').val(address.id);
				$('#address-form [name="recipient_name"]').val(
					address.recipient_name
				);
				$('#address-form [name="phone"]').val(address.phone);
				$('#address-form [name="province"]').val(address.province);
				$('#address-form [name="district"]').val(address.district);
				$('#address-form [name="ward"]').val(address.ward);
				$('#address-form [name="street_address"]').val(
					address.street_address
				);
			}
		}
	);
}

// Save address
function saveAddress() {
	$.ajax({
		url: '/healthy/views/user/profile.php',
		method: 'POST',
		data: $('#address-form').serialize(),
		success: function (response) {
			if (response.success) {
				location.reload();
			} else {
				showAlert('danger', response.message);
			}
		},
	});
}

// Delete address
function deleteAddress(addressId) {
	if (confirm('Bạn có chắc muốn xóa địa chỉ này?')) {
		$.ajax({
			url: '/healthy/views/user/profile.php',
			method: 'POST',
			data: {
				action: 'delete_address',
				address_id: addressId,
			},
			success: function (response) {
				if (response.success) {
					location.reload();
				} else {
					showAlert('danger', response.message);
				}
			},
		});
	}
}

// Set default address
function setDefaultAddress(addressId) {
	$.ajax({
		url: '/healthy/views/user/profile.php',
		method: 'POST',
		data: {
			action: 'set_default_address',
			address_id: addressId,
		},
		success: function (response) {
			if (response.success) {
				location.reload();
			} else {
				showAlert('danger', response.message);
			}
		},
	});
}

// Reset address form when modal is closed
$('#addressModal').on('hidden.bs.modal', function () {
	$('#address-form')[0].reset();
	$('#address-form [name="action"]').val('add_address');
	$('#address-form [name="address_id"]').val('');
	$('#addressModal .modal-title').text('Thêm địa chỉ mới');
});
