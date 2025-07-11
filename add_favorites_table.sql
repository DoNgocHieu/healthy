-- Tạo bảng favorites để lưu món ăn yêu thích của người dùng
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `item_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_item` (`user_id`, `item_id`),
  KEY `idx_user_favorites` (`user_id`),
  KEY `idx_item_favorites` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
