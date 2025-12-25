-- Drop the database if it exists (for fresh starts)
-- DROP DATABASE IF EXISTS stunning_pos_db;
-- CREATE DATABASE stunning_pos_db;
-- USE stunning_pos_db;

-- 1. Categories Table (e.g., Drinks, Food)
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- 2. Products Table (Inventory items)
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2) NOT NULL, -- Added for Profit calculation
    stock INT DEFAULT 0,
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- 3. Customers Table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE
);

-- 4. Employees Table (For staff logins/tracking sales)
CREATE TABLE IF NOT EXISTS employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    hire_date DATE
);

-- 5. Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    contact_person VARCHAR(255)
);

-- 6. Orders Table (The main sale record)
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT DEFAULT 1, -- Default to walk-in customer
    employee_id INT DEFAULT 1,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    profit DECIMAL(10, 2) DEFAULT 0.00, -- Stored profit for easy reporting
    order_status VARCHAR(50) DEFAULT 'Pending' -- New column for status tracking
);

-- 7. Order_Items Table (Details of what was sold in an order)
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL, -- Price at time of sale
    cost DECIMAL(10, 2) NOT NULL,   -- Cost at time of sale
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 8. Transactions Table (Detailed payment records)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50), -- e.g., 'Cash', 'Card'
    amount_paid DECIMAL(10, 2),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- 9. Discounts Table
CREATE TABLE IF NOT EXISTS discounts (
    discount_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    percentage DECIMAL(5, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- 10. Inventory_Logs Table (Tracking stock movements)
CREATE TABLE IF NOT EXISTS inventory_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    type VARCHAR(50), -- e.g., 'SALE', 'RESTOCK'
    quantity_change INT,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Initial Data Inserts
INSERT INTO categories (name) VALUES ('Coffee'), ('Pastries'), ('Lunch');
INSERT INTO products (name, price, cost, stock, category_id) VALUES 
('Espresso', 3.50, 0.50, 100, 1), 
('Croissant', 2.00, 0.75, 50, 2),
('Lunch Sandwich', 8.99, 4.00, 30, 3),
('Iced Latte', 4.99, 1.20, 120, 1),
('Cookie', 1.50, 0.30, 75, 2);
INSERT INTO employees (name, role, hire_date) VALUES ('Jane Doe', 'Cashier', CURDATE());
INSERT INTO customers (name, phone) VALUES ('Walk-in Customer', '0000000000');
INSERT INTO discounts (code, percentage) VALUES ('FALL10', 10.00);

-- Example Order for Dashboard Data
INSERT INTO orders (customer_id, employee_id, total_amount, profit, order_status) 
VALUES (1, 1, 10.98, 4.98, 'Delivered'); -- (3.50 + 2.00) * 2 + tax = 10.98, Profit = 10.98 - ((0.50 + 0.75)*2)
INSERT INTO order_items (order_id, product_id, quantity, price, cost) 
VALUES (1, 1, 2, 3.50, 0.50), (1, 2, 2, 2.00, 0.75);
INSERT INTO transactions (order_id, payment_method, amount_paid) VALUES (1, 'Card', 10.98);
INSERT INTO inventory_logs (product_id, type, quantity_change) VALUES (1, 'SALE', -2), (2, 'SALE', -2);