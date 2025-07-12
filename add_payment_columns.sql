-- Add payment columns to orders table
ALTER TABLE orders
ADD COLUMN payment_transaction_no VARCHAR(50) NULL,
ADD COLUMN payment_bank_code VARCHAR(10) NULL,
ADD COLUMN payment_date DATETIME NULL,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
