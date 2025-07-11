<?php
require_once __DIR__ . '/../config/config.php';

class UserProfile {
    private $pdo;

    public function __construct() {
        $this->pdo = getDb();
    }

    public function getUser($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, phone, points, avatar_url, created_at
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function updateProfile($userId, $data) {
        $allowedFields = ['username', 'email', 'phone'];
        $updates = [];
        $params = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields) && !empty($value)) {
                $updates[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return ['success' => false, 'message' => 'Không có thông tin nào được cập nhật'];
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công'];
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry error
                return ['success' => false, 'message' => 'Email hoặc username đã tồn tại'];
            }
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function updateAvatar($userId, $file) {
        // Kiểm tra file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Lỗi upload file'];
        }

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)'];
        }

        // Kiểm tra kích thước file (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File ảnh không được lớn hơn 5MB'];
        }

        // Tạo tên file mới
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;
        $uploadPath = __DIR__ . '/../uploads/avatars/' . $newFileName;

        // Di chuyển file upload
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'message' => 'Không thể lưu file'];
        }

        // Cập nhật database
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $avatarUrl = '/healthy/uploads/avatars/' . $newFileName;
            $stmt->execute([$avatarUrl, $userId]);

            // Xóa avatar cũ nếu có
            $oldAvatar = $this->getUser($userId)['avatar_url'];
            if ($oldAvatar) {
                $oldPath = __DIR__ . '/../../' . $oldAvatar;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            return [
                'success' => true,
                'message' => 'Cập nhật avatar thành công',
                'avatar_url' => $avatarUrl
            ];
        } catch (PDOException $e) {
            // Xóa file đã upload nếu cập nhật database thất bại
            unlink($uploadPath);
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        // Kiểm tra mật khẩu hiện tại
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
        }

        // Cập nhật mật khẩu mới
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getUserAddresses($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM addresses
            WHERE user_id = ?
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function addAddress($userId, $data) {
        try {
            // Nếu đây là địa chỉ đầu tiên, đặt làm mặc định
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = ?");
            $stmt->execute([$userId]);
            $isDefault = ($stmt->fetchColumn() == 0);

            $stmt = $this->pdo->prepare("
                INSERT INTO addresses (
                    user_id, recipient_name, phone, province, district,
                    ward, street_address, is_default
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $data['recipient_name'],
                $data['phone'],
                $data['province'],
                $data['district'],
                $data['ward'],
                $data['street_address'],
                $isDefault
            ]);

            return ['success' => true, 'message' => 'Thêm địa chỉ thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function updateAddress($addressId, $userId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE addresses
                SET recipient_name = ?, phone = ?, province = ?,
                    district = ?, ward = ?, street_address = ?
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $data['recipient_name'],
                $data['phone'],
                $data['province'],
                $data['district'],
                $data['ward'],
                $data['street_address'],
                $addressId,
                $userId
            ]);

            return ['success' => true, 'message' => 'Cập nhật địa chỉ thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function deleteAddress($addressId, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM addresses
                WHERE id = ? AND user_id = ? AND is_default = 0
            ");
            $stmt->execute([$addressId, $userId]);

            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa địa chỉ (có thể là địa chỉ mặc định)'
                ];
            }

            return ['success' => true, 'message' => 'Xóa địa chỉ thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function setDefaultAddress($addressId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Bỏ mặc định cho tất cả địa chỉ của user
            $stmt = $this->pdo->prepare("
                UPDATE addresses SET is_default = 0
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            // Đặt địa chỉ mới làm mặc định
            $stmt = $this->pdo->prepare("
                UPDATE addresses SET is_default = 1
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$addressId, $userId]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Đã đặt làm địa chỉ mặc định'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}
