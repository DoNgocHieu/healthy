-- Migration: create orders & order_items tables for ecommerce flow
-- =============================================
-- Run in MySQL/MariaDB 10.4+ (utf8mb4)

-- Table: orders (đơn hàng tổng)
CREATE TABLE IF NOT EXISTS `orders` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL COMMENT 'Tham chiếu users.id',
  `address_id`    INT UNSIGNED NOT NULL COMMENT 'Địa chỉ giao hàng người dùng',
  `voucher_id`    INT UNSIGNED DEFAULT NULL COMMENT 'Voucher đã áp dụng (nullable)',
  `payment_method` ENUM('cod','bank_transfer','e_wallet') NOT NULL DEFAULT 'cod' COMMENT 'Phương thức thanh toán',
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid' COMMENT 'Trạng thái thanh toán',
  `status`        ENUM('pending','confirmed','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal`      INT NOT NULL COMMENT 'Tổng tiền hàng trước giảm',
  `discount`      INT NOT NULL DEFAULT 0 COMMENT 'Giảm giá (voucher + điểm)',
  `shipping_fee`  INT NOT NULL DEFAULT 0 COMMENT 'Phí giao hàng',
  `grand_total`   INT NOT NULL COMMENT 'Tổng thanh toán cuối cùng',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_address` (`address_id`),
  KEY `idx_orders_voucher` (`voucher_id`),
  CONSTRAINT `fk_orders_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)         ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_address` FOREIGN KEY (`address_id`) REFERENCES `user_addresses`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`)      ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: order_items (chi tiết món trong đơn)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED NOT NULL,
  `item_id`     INT NOT NULL COMMENT 'Tham chiếu items.id',
  `quantity`    INT NOT NULL,
  `unit_price`  INT NOT NULL COMMENT 'Đơn giá tại thời điểm đặt',
  `total_price` INT NOT NULL COMMENT '= quantity * unit_price',
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `idx_oi_item`  (`item_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_item`  FOREIGN KEY (`item_id`)  REFERENCES `items`(`id`)  ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- END 