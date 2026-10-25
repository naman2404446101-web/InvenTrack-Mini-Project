-- ============================================================
-- Inventory Sales & Tracker System - Database Setup
-- Run this file in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE inventory_db;

-- ============================================================
-- Table: users
-- Stores registered user accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,         -- Stores bcrypt hash
    role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: categories
-- Product categories for filtering
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: products
-- Stores all inventory products
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 10,  -- Alert when stock <= this
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- Table: sales
-- Records every sale transaction
-- ============================================================
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,           -- Price at time of sale
    total_price DECIMAL(10,2) NOT NULL,
    customer_name VARCHAR(150) DEFAULT 'Walk-in Customer',
    sold_by INT,                                  -- FK to users
    sale_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_user   FOREIGN KEY (sold_by)     REFERENCES users(id)    ON DELETE SET NULL
);

-- ============================================================
-- Table: stock_history
-- Tracks every stock change (additions, sales, adjustments)
-- ============================================================
CREATE TABLE IF NOT EXISTS stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    change_type ENUM('add','sale','adjust') NOT NULL,
    quantity_change INT NOT NULL,               -- Positive = added, Negative = removed
    quantity_after INT NOT NULL,                -- Stock level after the change
    note VARCHAR(255),
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sh_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_sh_user    FOREIGN KEY (changed_by)  REFERENCES users(id)    ON DELETE SET NULL
);

-- ============================================================
-- Sample Data
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@inventory.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample categories
INSERT INTO categories (name) VALUES
('Electronics'), ('Clothing'), ('Food & Beverages'), ('Furniture'), ('Stationery');

-- Sample products
INSERT INTO products (name, category, price, quantity, low_stock_threshold) VALUES
('Laptop Pro 15"',       'Electronics',      55999.00, 25, 5),
('Wireless Mouse',       'Electronics',       999.00,  50, 10),
('USB-C Hub 7-in-1',    'Electronics',      2499.00,   8, 10),
('Blue Denim Jeans',    'Clothing',          1499.00,  30, 8),
('Cotton T-Shirt',      'Clothing',           599.00,  60, 15),
('Office Chair',        'Furniture',         8999.00,   6, 3),
('Standing Desk',       'Furniture',        15999.00,   4, 2),
('Mineral Water 1L',    'Food & Beverages',    30.00, 200, 50),
('Premium Coffee Beans','Food & Beverages',   850.00,  15, 10),
('A4 Paper (500 sheets)','Stationery',        350.00,  40, 10),
('Ball Pen Set (12)',    'Stationery',         120.00,  75, 20),
('Webcam HD 1080p',     'Electronics',       3499.00,   3, 5);

-- Sample sales
INSERT INTO sales (product_id, quantity, unit_price, total_price, customer_name, sold_by, sale_date) VALUES
(1,  1, 55999.00, 55999.00, 'Rajesh Kumar',   1, CURDATE() - INTERVAL 6 DAY),
(2,  3,   999.00,  2997.00, 'Priya Sharma',   1, CURDATE() - INTERVAL 5 DAY),
(5,  5,   599.00,  2995.00, 'Amit Patel',     1, CURDATE() - INTERVAL 4 DAY),
(10, 2,   350.00,   700.00, 'Sneha Joshi',    1, CURDATE() - INTERVAL 3 DAY),
(9,  2,   850.00,  1700.00, 'Vikram Singh',   1, CURDATE() - INTERVAL 2 DAY),
(4,  2,  1499.00,  2998.00, 'Meena Iyer',     1, CURDATE() - INTERVAL 1 DAY),
(11, 4,   120.00,   480.00, 'Walk-in Customer',1, CURDATE()),
(8, 10,    30.00,   300.00, 'Walk-in Customer',1, CURDATE());

-- Update quantities after sample sales
UPDATE products SET quantity = quantity - 1  WHERE id = 1;
UPDATE products SET quantity = quantity - 3  WHERE id = 2;
UPDATE products SET quantity = quantity - 5  WHERE id = 5;
UPDATE products SET quantity = quantity - 2  WHERE id = 10;
UPDATE products SET quantity = quantity - 2  WHERE id = 9;
UPDATE products SET quantity = quantity - 2  WHERE id = 4;
UPDATE products SET quantity = quantity - 4  WHERE id = 11;
UPDATE products SET quantity = quantity - 10 WHERE id = 8;

-- Stock history for the sample sales
INSERT INTO stock_history (product_id, change_type, quantity_change, quantity_after, note, changed_by) VALUES
(1,  'sale', -1,  24, 'Sale #1',  1),
(2,  'sale', -3,  47, 'Sale #2',  1),
(5,  'sale', -5,  55, 'Sale #3',  1),
(10, 'sale', -2,  38, 'Sale #4',  1),
(9,  'sale', -2,  13, 'Sale #5',  1),
(4,  'sale', -2,  28, 'Sale #6',  1),
(11, 'sale', -4,  71, 'Sale #7',  1),
(8,  'sale',-10, 190, 'Sale #8',  1);

-- ============================================================
-- Useful views
-- ============================================================

-- View: low stock products
CREATE OR REPLACE VIEW v_low_stock AS
    SELECT id, name, category, quantity, low_stock_threshold
    FROM products
    WHERE quantity <= low_stock_threshold
    ORDER BY quantity ASC;

-- View: sales summary
CREATE OR REPLACE VIEW v_sales_summary AS
    SELECT
        s.id,
        p.name  AS product_name,
        p.category,
        s.quantity,
        s.unit_price,
        s.total_price,
        s.customer_name,
        u.name  AS sold_by_name,
        s.sale_date,
        s.created_at
    FROM sales s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN users u ON s.sold_by = u.id;
