<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/UserProfile.php';

$auth = new Auth();
$profile = new UserProfile();

if (!$auth->isLoggedIn()) {
    header('Location: /healthy/views/auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$addresses = $profile->getUserAddresses($user['id']);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = ['success' => false, 'message' => 'Invalid action'];

    switch ($action) {
        case 'update_profile':
            $result = $profile->updateProfile($user['id'], [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ]);
            break;

        case 'update_avatar':
            if (isset($_FILES['avatar'])) {
                $result = $profile->updateAvatar($user['id'], $_FILES['avatar']);
            }
            break;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $result = ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp'];
            } else {
                $result = $profile->changePassword($user['id'], $currentPassword, $newPassword);
            }
            break;

        case 'add_address':
            $result = $profile->addAddress($user['id'], $_POST);
            break;

        case 'update_address':
            $addressId = $_POST['address_id'] ?? 0;
            $result = $profile->updateAddress($addressId, $user['id'], $_POST);
            break;

        case 'delete_address':
            $addressId = $_POST['address_id'] ?? 0;
            $result = $profile->deleteAddress($addressId, $user['id']);
            break;

        case 'set_default_address':
            $addressId = $_POST['address_id'] ?? 0;
            $result = $profile->setDefaultAddress($addressId, $user['id']);
            break;
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

// Refresh user data after updates
$user = $auth->getCurrentUser();
$addresses = $profile->getUserAddresses($user['id']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản - Healthy Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/healthy/css/profile.css" rel="stylesheet">
</head>
<body>
    <?php include '../layout/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="profile-sidebar card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-wrapper">
                                <img src="<?php echo htmlspecialchars($user['avatar_url'] ?? '/healthy/img/default-avatar.png'); ?>"
                                     class="rounded-circle" alt="Avatar" width="100" height="100">
                                <form id="avatar-form" class="d-none">
                                    <input type="file" name="avatar" id="avatar-input" accept="image/*">
                                    <input type="hidden" name="action" value="update_avatar">
                                </form>
                                <button class="btn btn-sm btn-light avatar-edit" onclick="document.getElementById('avatar-input').click()">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                            <p class="text-muted">Thành viên từ <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                        </div>

                        <div class="profile-points text-center mb-4">
                            <h6>Điểm tích lũy</h6>
                            <h4><?php echo number_format($user['points']); ?></h4>
                        </div>

                        <div class="list-group">
                            <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="bi bi-person me-2"></i> Thông tin cá nhân
                            </a>
                            <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-geo-alt me-2"></i> Địa chỉ
                            </a>
                            <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-shield-lock me-2"></i> Bảo mật
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Thông tin cá nhân</h5>
                                <form id="profile-form">
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="mb-3">
                                        <label for="username" class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" id="username" name="username"
                                               value="<?php echo htmlspecialchars($user['username']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                               value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>

                                    <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses tab -->
                    <div class="tab-pane fade" id="addresses">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Địa chỉ giao hàng</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                        <i class="bi bi-plus"></i> Thêm địa chỉ mới
                                    </button>
                                </div>

                                <div class="addresses-list">
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="address-item card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-2">
                                                        <?php echo htmlspecialchars($address['recipient_name']); ?>
                                                        <?php if ($address['is_default']): ?>
                                                            <span class="badge bg-primary">Mặc định</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div class="address-actions">
                                                        <button class="btn btn-sm btn-light"
                                                                onclick="editAddress(<?php echo $address['id']; ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if (!$address['is_default']): ?>
                                                            <button class="btn btn-sm btn-light"
                                                                    onclick="deleteAddress(<?php echo $address['id']; ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-light"
                                                                    onclick="setDefaultAddress(<?php echo $address['id']; ?>)">
                                                                Đặt mặc định
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($address['phone']); ?></p>
                                                <p class="mb-0">
                                                    <?php echo htmlspecialchars($address['street_address']); ?>,
                                                    <?php echo htmlspecialchars($address['ward']); ?>,
                                                    <?php echo htmlspecialchars($address['district']); ?>,
                                                    <?php echo htmlspecialchars($address['province']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Đổi mật khẩu</h5>
                                <form id="password-form">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                        <input type="password" class="form-control" id="current_password"
                                               name="current_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="new_password"
                                               name="new_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" class="form-control" id="confirm_password"
                                               name="confirm_password" required>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Address Modal -->
    <div class="modal fade" id="addressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm địa chỉ mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="address-form">
                        <input type="hidden" name="action" value="add_address">
                        <input type="hidden" name="address_id" value="">

                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Tên người nhận</label>
                            <input type="text" class="form-control" id="recipient_name"
                                   name="recipient_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="recipient_phone"
                                   name="phone" required>
                        </div>

                        <div class="mb-3">
                            <label for="province" class="form-label">Tỉnh/Thành phố</label>
                            <input type="text" class="form-control" id="province"
                                   name="province" required>
                        </div>

                        <div class="mb-3">
                            <label for="district" class="form-label">Quận/Huyện</label>
                            <input type="text" class="form-control" id="district"
                                   name="district" required>
                        </div>

                        <div class="mb-3">
                            <label for="ward" class="form-label">Phường/Xã</label>
                            <input type="text" class="form-control" id="ward"
                                   name="ward" required>
                        </div>

                        <div class="mb-3">
                            <label for="street_address" class="form-label">Địa chỉ cụ thể</label>
                            <textarea class="form-control" id="street_address"
                                      name="street_address" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="saveAddress()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../layout/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/healthy/js/profile.js"></script>
</body>
</html>
