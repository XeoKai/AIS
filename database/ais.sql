CREATE DATABASE IF NOT EXISTS ais_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ais_db;

-- Inventory table used by the cashier and inventory screens.
CREATE TABLE IF NOT EXISTS products (
    product_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Other',
    sell DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    barcode VARCHAR(64) NULL,
    supplier VARCHAR(190) NOT NULL DEFAULT '',
    UNIQUE KEY uq_products_barcode (barcode)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    sale_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    total DECIMAL(12,2) NOT NULL,
    amount_received DECIMAL(12,2) NOT NULL,
    change_due DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
    sale_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(190) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- Sample inventory for a fresh local installation. Existing stock is never overwritten.
INSERT IGNORE INTO products (name, category, sell, cost, stock, barcode, supplier) VALUES
('Arabica Coffee Beans 250g', 'Beverages', 285.00, 198.00, 18, '4800012345678', 'Brew & Co.'),
('Oat Milk Original 1L', 'Dairy & Alternatives', 139.00, 103.00, 6, '4800098765432', 'Daily Harvest'),
('Sea Salt Potato Chips', 'Snacks', 52.00, 34.00, 2, '4800044455566', 'Good Snacks'),
('Sea Salt Potato Chips', 'Snacks', 52.00, 34.00, 0, '4800044455573', 'Good Snacks');
