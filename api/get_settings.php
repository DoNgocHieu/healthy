<?php
require_once __DIR__ . '/../config/SiteSettingsManager.php';

header('Content-Type: application/json');

try {
    $settingsManager = new SiteSettingsManager();

    // Lấy group từ query parameter, mặc định là tất cả
    $group = $_GET['group'] ?? null;

    if ($group) {
        $settings = $settingsManager->getSettingsByGroup($group);
    } else {
        $settings = $settingsManager->getSettingsByGroup();
    }

    // Chuyển đổi thành format key-value dễ sử dụng
    $result = [];
    foreach ($settings as $setting) {
        $result[$setting['setting_key']] = $setting['setting_value'];
    }

    echo json_encode([
        'success' => true,
        'settings' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
