<?php
require_once __DIR__ . '/../../config/SiteSettingsManager.php';

$settingsManager = new SiteSettingsManager();

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_settings') {
        $settings = $_POST['settings'] ?? [];

        if ($settingsManager->updateMultipleSettings($settings)) {
            $message = 'Cập nhật thành công!';
            $messageType = 'success';
        } else {
            $message = 'Có lỗi xảy ra khi cập nhật!';
            $messageType = 'danger';
        }
    }
}

// Lấy tất cả settings theo nhóm
$groups = $settingsManager->getGroups();
$allSettings = [];
foreach ($groups as $group) {
    $allSettings[$group] = $settingsManager->getSettingsByGroup($group);
}
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Cài đặt hệ thống</h2>

    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_settings">

        <!-- Header Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-header"></i> Cài đặt Header
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($allSettings['header'])): ?>
                <div class="row">
                    <?php foreach ($allSettings['header'] as $setting): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <?php echo htmlspecialchars($setting['description']); ?>
                        </label>

                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                            <textarea class="form-control"
                                      name="settings[<?php echo $setting['setting_key']; ?>]"
                                      rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        <?php elseif ($setting['setting_type'] === 'image'): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                     alt="Current image"
                                     class="img-thumbnail"
                                     style="max-width: 100px; max-height: 100px;">
                            </div>
                            <input type="text"
                                   class="form-control"
                                   name="settings[<?php echo $setting['setting_key']; ?>]"
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                   placeholder="Đường dẫn ảnh">
                        <?php else: ?>
                            <input type="<?php echo $setting['setting_type'] === 'email' ? 'email' : ($setting['setting_type'] === 'url' ? 'url' : 'text'); ?>"
                                   class="form-control"
                                   name="settings[<?php echo $setting['setting_key']; ?>]"
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                        <?php endif; ?>

                        <small class="text-muted">Key: <?php echo $setting['setting_key']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-telephone"></i> Thông tin liên hệ
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($allSettings['contact'])): ?>
                <div class="row">
                    <?php foreach ($allSettings['contact'] as $setting): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <?php echo htmlspecialchars($setting['description']); ?>
                        </label>

                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                            <textarea class="form-control"
                                      name="settings[<?php echo $setting['setting_key']; ?>]"
                                      rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        <?php else: ?>
                            <input type="<?php echo $setting['setting_type'] === 'email' ? 'email' : 'text'; ?>"
                                   class="form-control"
                                   name="settings[<?php echo $setting['setting_key']; ?>]"
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                        <?php endif; ?>

                        <small class="text-muted">Key: <?php echo $setting['setting_key']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Social Media Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-share"></i> Mạng xã hội
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($allSettings['social'])): ?>
                <div class="row">
                    <?php foreach ($allSettings['social'] as $setting): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <?php echo htmlspecialchars($setting['description']); ?>
                        </label>

                        <input type="url"
                               class="form-control"
                               name="settings[<?php echo $setting['setting_key']; ?>]"
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">

                        <small class="text-muted">Key: <?php echo $setting['setting_key']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- General Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-gear"></i> Cài đặt chung
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($allSettings['general'])): ?>
                <div class="row">
                    <?php foreach ($allSettings['general'] as $setting): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <?php echo htmlspecialchars($setting['description']); ?>
                        </label>

                        <?php if ($setting['setting_type'] === 'textarea'): ?>
                            <textarea class="form-control"
                                      name="settings[<?php echo $setting['setting_key']; ?>]"
                                      rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        <?php else: ?>
                            <input type="text"
                                   class="form-control"
                                   name="settings[<?php echo $setting['setting_key']; ?>]"
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                        <?php endif; ?>

                        <small class="text-muted">Key: <?php echo $setting['setting_key']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Lưu thay đổi
            </button>

            <a href="?page=admin&section=dashboard" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview image khi thay đổi đường dẫn
    document.querySelectorAll('input[type="text"]').forEach(input => {
        if (input.name.includes('logo') || input.name.includes('image')) {
            input.addEventListener('change', function() {
                const imgElement = this.parentElement.querySelector('img');
                if (imgElement && this.value) {
                    imgElement.src = this.value;
                    imgElement.onerror = function() {
                        this.src = '/healthy/img/placeholder.png';
                    };
                }
            });
        }
    });

    // Xác nhận trước khi lưu
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('Bạn có chắc muốn lưu các thay đổi này?')) {
            e.preventDefault();
        }
    });
});
</script>
