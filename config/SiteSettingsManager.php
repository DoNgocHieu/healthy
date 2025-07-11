<?php
require_once __DIR__ . '/../config/Database.php';

class SiteSettingsManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lấy tất cả settings theo nhóm
     */
    public function getSettingsByGroup($group = null) {
        $sql = "SELECT * FROM site_settings";
        $params = [];

        if ($group) {
            $sql .= " WHERE setting_group = ?";
            $params[] = $group;
        }

        $sql .= " ORDER BY setting_group, setting_key";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy giá trị của một setting
     */
    public function getSetting($key, $default = '') {
        $stmt = $this->db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);

        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    /**
     * Cập nhật giá trị setting
     */
    public function updateSetting($key, $value) {
        $stmt = $this->db->prepare("
            UPDATE site_settings
            SET setting_value = ?, updated_at = NOW()
            WHERE setting_key = ?
        ");

        return $stmt->execute([$value, $key]);
    }

    /**
     * Lấy tất cả các nhóm settings
     */
    public function getGroups() {
        $stmt = $this->db->query("SELECT DISTINCT setting_group FROM site_settings ORDER BY setting_group");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Cập nhật nhiều settings cùng lúc
     */
    public function updateMultipleSettings($settings) {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE site_settings
                SET setting_value = ?, updated_at = NOW()
                WHERE setting_key = ?
            ");

            foreach ($settings as $key => $value) {
                $stmt->execute([$value, $key]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Thêm setting mới
     */
    public function addSetting($key, $value, $type = 'text', $group = 'general', $description = '') {
        $stmt = $this->db->prepare("
            INSERT INTO site_settings (setting_key, setting_value, setting_type, setting_group, description)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$key, $value, $type, $group, $description]);
    }

    /**
     * Xóa setting
     */
    public function deleteSetting($key) {
        $stmt = $this->db->prepare("DELETE FROM site_settings WHERE setting_key = ?");
        return $stmt->execute([$key]);
    }
}
?>
