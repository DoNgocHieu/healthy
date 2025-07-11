-- Tạo bảng voucher_usage để theo dõi việc sử dụng voucher
CREATE TABLE IF NOT EXISTS voucher_usage (
  id INT PRIMARY KEY AUTO_INCREMENT,
  voucher_id INT,
  user_id INT,
  order_id INT,
  used_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng food để hỗ trợ reviews
CREATE TABLE IF NOT EXISTS food (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255),
  description TEXT,
  price DECIMAL(10,2),
  category VARCHAR(100),
  image VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Chèn dữ liệu mẫu vào bảng food từ items
INSERT IGNORE INTO food (id, name, description, price, category, image)
SELECT id, name, description, price, TT, image_url
FROM items;
