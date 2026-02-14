-- ============================================
-- CAFE NOWA DATABASE SCHEMA - ENHANCED
-- With Menu and Inventory Management
-- ============================================

-- Drop existing database if exists
DROP DATABASE IF EXISTS cafenowa_db;
CREATE DATABASE cafenowa_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cafenowa_db;

-- ============================================
-- 1. SUPER ADMIN TABLE (Highest privilege)
-- ============================================
CREATE TABLE super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- 2. ADMIN TABLE (Can manage employees and customers)
-- ============================================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (created_by) REFERENCES super_admins(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- 3. EMPLOYEES TABLE
-- ============================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    position VARCHAR(50) DEFAULT 'Barista',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- 4. CUSTOMERS TABLE
-- ============================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- 5. MENU CATEGORIES TABLE
-- ============================================
CREATE TABLE menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB;

-- ============================================
-- 6. MENU ITEMS TABLE
-- ============================================
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_available (is_available),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- ============================================
-- 7. INVENTORY ITEMS TABLE
-- ============================================
CREATE TABLE inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    unit VARCHAR(20) NOT NULL, -- kg, liters, pieces, etc.
    current_stock DECIMAL(10, 2) NOT NULL DEFAULT 0,
    minimum_stock DECIMAL(10, 2) NOT NULL DEFAULT 0,
    maximum_stock DECIMAL(10, 2) DEFAULT NULL,
    unit_price DECIMAL(10, 2) DEFAULT 0,
    supplier VARCHAR(100),
    last_restocked TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_stock_level (current_stock, minimum_stock)
) ENGINE=InnoDB;

-- ============================================
-- 8. INVENTORY TRANSACTIONS TABLE
-- ============================================
CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('restock', 'usage', 'adjustment', 'waste') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    previous_stock DECIMAL(10, 2) NOT NULL,
    new_stock DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    performed_by INT NOT NULL,
    user_type ENUM('super_admin', 'admin', 'employee') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_date (transaction_date),
    INDEX idx_type (transaction_type)
) ENGINE=InnoDB;

-- ============================================
-- 9. AUDIT TRAIL TABLE (Non-editable log)
-- ============================================
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('super_admin', 'admin', 'employee', 'customer') NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    action_type ENUM('login', 'logout', 'create', 'update', 'delete', 'view', 'failed_login') NOT NULL,
    target_table VARCHAR(50) NULL,
    target_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details TEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_type),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- ============================================
-- 10. LOGIN ATTEMPTS TABLE (For security)
-- ============================================
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_email_ip (email, ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT SUPER ADMIN
-- Password: password
-- ============================================
INSERT INTO super_admins (username, email, password_hash, full_name) VALUES
('superadmin', 'superadmin@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator');

-- ============================================
-- INSERT DEFAULT ADMIN
-- Password: password
-- ============================================
INSERT INTO admins (username, email, password_hash, full_name, created_by) VALUES
('admin', 'admin@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 1);

-- ============================================
-- INSERT DEFAULT EMPLOYEE
-- Password: password
-- ============================================
INSERT INTO employees (username, email, password_hash, full_name, position) VALUES
('employee1', 'employee@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee User', 'Barista');

-- ============================================
-- INSERT DEFAULT CUSTOMER
-- Password: password
-- ============================================
INSERT INTO customers (username, email, password_hash, full_name, phone) VALUES
('customer1', 'customer@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer User', '1234567890');

-- ============================================
-- INSERT DEFAULT MENU CATEGORIES
-- ============================================
INSERT INTO menu_categories (name, description, display_order) VALUES
('Hot Coffee', 'Freshly brewed hot coffee beverages', 1),
('Iced Coffee', 'Refreshing cold coffee drinks', 2),
('Specialty Drinks', 'Unique signature beverages', 3),
('Pastries', 'Fresh baked goods and pastries', 4),
('Sandwiches', 'Delicious sandwiches and meals', 5);

-- ============================================
-- INSERT SAMPLE MENU ITEMS
-- ============================================
INSERT INTO menu_items (category_id, name, description, price, is_featured, display_order) VALUES
-- Hot Coffee
(1, 'Espresso', 'Rich and bold single shot', 95.00, FALSE, 1),
(1, 'Americano', 'Espresso with hot water', 120.00, FALSE, 2),
(1, 'Cappuccino', 'Espresso with steamed milk and foam', 150.00, TRUE, 3),
(1, 'Latte', 'Smooth espresso with steamed milk', 150.00, TRUE, 4),
(1, 'Mocha', 'Chocolate and espresso blend', 165.00, FALSE, 5),

-- Iced Coffee
(2, 'Iced Americano', 'Chilled espresso with water', 130.00, FALSE, 1),
(2, 'Iced Latte', 'Cold espresso with milk', 160.00, TRUE, 2),
(2, 'Cold Brew', 'Smooth cold-steeped coffee', 170.00, TRUE, 3),
(2, 'Frappe', 'Blended iced coffee drink', 180.00, FALSE, 4),

-- Specialty Drinks
(3, 'Matcha Latte', 'Japanese green tea latte', 170.00, TRUE, 1),
(3, 'Chai Latte', 'Spiced tea with steamed milk', 160.00, FALSE, 2),
(3, 'Caramel Macchiato', 'Vanilla and caramel espresso', 175.00, TRUE, 3),

-- Pastries
(4, 'Croissant', 'Buttery French pastry', 85.00, FALSE, 1),
(4, 'Chocolate Muffin', 'Rich chocolate muffin', 95.00, FALSE, 2),
(4, 'Blueberry Scone', 'Fresh baked scone', 90.00, FALSE, 3),

-- Sandwiches
(5, 'Club Sandwich', 'Triple-decker classic', 195.00, FALSE, 1),
(5, 'Grilled Cheese', 'Melted cheese on toast', 145.00, FALSE, 2);

-- ============================================
-- INSERT SAMPLE INVENTORY ITEMS
-- ============================================
INSERT INTO inventory_items (item_name, category, unit, current_stock, minimum_stock, maximum_stock, unit_price, supplier) VALUES
-- Coffee & Tea
('Coffee Beans - Arabica', 'Coffee', 'kg', 25.00, 10.00, 50.00, 450.00, 'Premium Coffee Suppliers'),
('Coffee Beans - Robusta', 'Coffee', 'kg', 15.00, 8.00, 40.00, 350.00, 'Premium Coffee Suppliers'),
('Matcha Powder', 'Tea', 'kg', 5.00, 2.00, 10.00, 850.00, 'Tea Imports Co.'),
('Chai Tea', 'Tea', 'kg', 3.00, 1.00, 8.00, 320.00, 'Tea Imports Co.'),

-- Dairy
('Whole Milk', 'Dairy', 'liters', 50.00, 20.00, 100.00, 85.00, 'Local Dairy Farm'),
('Non-Fat Milk', 'Dairy', 'liters', 30.00, 15.00, 80.00, 80.00, 'Local Dairy Farm'),
('Heavy Cream', 'Dairy', 'liters', 20.00, 10.00, 40.00, 150.00, 'Local Dairy Farm'),
('Almond Milk', 'Dairy Alternative', 'liters', 15.00, 8.00, 30.00, 120.00, 'Organic Supplies'),

-- Syrups & Flavors
('Vanilla Syrup', 'Syrups', 'liters', 8.00, 3.00, 15.00, 180.00, 'Flavor House'),
('Caramel Syrup', 'Syrups', 'liters', 7.00, 3.00, 15.00, 190.00, 'Flavor House'),
('Chocolate Syrup', 'Syrups', 'liters', 10.00, 4.00, 20.00, 165.00, 'Flavor House'),
('Hazelnut Syrup', 'Syrups', 'liters', 5.00, 2.00, 10.00, 185.00, 'Flavor House'),

-- Baking & Pastries
('All-Purpose Flour', 'Baking', 'kg', 40.00, 15.00, 80.00, 55.00, 'Baking Supplies Inc'),
('Sugar', 'Baking', 'kg', 35.00, 15.00, 70.00, 42.00, 'Baking Supplies Inc'),
('Butter', 'Baking', 'kg', 20.00, 10.00, 40.00, 280.00, 'Local Dairy Farm'),
('Eggs', 'Baking', 'pieces', 200.00, 100.00, 500.00, 8.00, 'Local Farm'),
('Chocolate Chips', 'Baking', 'kg', 12.00, 5.00, 25.00, 320.00, 'Baking Supplies Inc'),

-- Disposables
('Paper Cups 12oz', 'Disposables', 'pieces', 500.00, 200.00, 1000.00, 2.50, 'Packaging Supplier'),
('Paper Cups 16oz', 'Disposables', 'pieces', 600.00, 250.00, 1200.00, 2.80, 'Packaging Supplier'),
('Lids', 'Disposables', 'pieces', 800.00, 300.00, 1500.00, 1.20, 'Packaging Supplier'),
('Straws', 'Disposables', 'pieces', 1000.00, 400.00, 2000.00, 0.50, 'Packaging Supplier'),
('Napkins', 'Disposables', 'pieces', 2000.00, 500.00, 3000.00, 0.30, 'Packaging Supplier');

-- ============================================
-- INSERT SAMPLE ORDERS
-- ============================================
INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, total_amount, status, order_type, table_number, created_at) VALUES
('ORD-001', 'Walk-in Customer', NULL, NULL, 315.00, 'pending', 'dine-in', 'T5', NOW() - INTERVAL 5 MINUTE),
('ORD-002', 'Sarah Johnson', 'sarah@email.com', '555-0123', 485.00, 'pending', 'takeout', NULL, NOW() - INTERVAL 3 MINUTE),
('ORD-003', 'Mike Chen', 'mike@email.com', '555-0124', 230.00, 'confirmed', 'dine-in', 'T2', NOW() - INTERVAL 15 MINUTE),
('ORD-004', 'Emily Davis', NULL, '555-0125', 390.00, 'preparing', 'delivery', NULL, NOW() - INTERVAL 20 MINUTE);

-- ============================================
-- INSERT SAMPLE ORDER ITEMS
-- ============================================
-- Order 1 items
INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES
(1, 3, 'Cappuccino', 2, 150.00, 300.00),
(1, 19, 'Croissant', 1, 85.00, 85.00);

-- Order 2 items  
INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES
(2, 8, 'Cold Brew', 2, 170.00, 340.00),
(2, 20, 'Chocolate Muffin', 1, 95.00, 95.00),
(2, 18, 'Caramel Macchiato', 1, 175.00, 175.00);

-- Order 3 items
INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES
(3, 4, 'Latte', 1, 150.00, 150.00),
(3, 21, 'Blueberry Scone', 1, 90.00, 90.00);

-- Order 4 items
INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price, subtotal) VALUES
(4, 7, 'Iced Latte', 2, 160.00, 320.00),
(4, 19, 'Croissant', 2, 85.00, 170.00);

-- ============================================
-- 11. ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NULL,
    customer_phone VARCHAR(20) NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    order_type ENUM('dine-in', 'takeout', 'delivery') NOT NULL DEFAULT 'dine-in',
    table_number VARCHAR(10) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB;

-- ============================================
-- 12. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    special_instructions TEXT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_menu_item_id (menu_item_id)
) ENGINE=InnoDB;

-- ============================================
-- CREATE VIEWS
-- ============================================

-- View for all users across tables
CREATE VIEW vw_all_users AS
SELECT 
    'super_admin' as user_type,
    id, username, email, full_name, created_at, last_login, is_active
FROM super_admins
UNION ALL
SELECT 
    'admin' as user_type,
    id, username, email, full_name, created_at, last_login, is_active
FROM admins
UNION ALL
SELECT 
    'employee' as user_type,
    id, username, email, full_name, created_at, last_login, is_active
FROM employees
UNION ALL
SELECT 
    'customer' as user_type,
    id, username, email, full_name, created_at, last_login, is_active
FROM customers;

-- View for low stock items
CREATE VIEW vw_low_stock_items AS
SELECT 
    id, item_name, category, unit,
    current_stock, minimum_stock,
    (minimum_stock - current_stock) as shortage,
    supplier, last_restocked
FROM inventory_items
WHERE current_stock < minimum_stock
AND is_active = TRUE
ORDER BY shortage DESC;

-- View for menu items with category names
CREATE VIEW vw_menu_items_full AS
SELECT 
    mi.id, mi.name, mi.description, mi.price,
    mi.image_url, mi.is_available, mi.is_featured,
    mc.name as category_name, mc.id as category_id,
    mi.display_order, mi.created_at, mi.updated_at
FROM menu_items mi
JOIN menu_categories mc ON mi.category_id = mc.id
ORDER BY mc.display_order, mi.display_order;

-- View for active orders (for employees)
CREATE VIEW vw_active_orders AS
SELECT 
    o.id, o.order_number, o.customer_name, o.customer_phone,
    o.total_amount, o.status, o.order_type, o.table_number,
    o.notes, o.created_at, o.updated_at,
    e.full_name as assigned_employee,
    COUNT(oi.id) as item_count
FROM orders o
LEFT JOIN employees e ON o.assigned_to = e.id
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.status IN ('pending', 'confirmed', 'preparing', 'ready')
GROUP BY o.id
ORDER BY o.created_at ASC;

-- View for sales statistics
CREATE VIEW vw_sales_stats AS
SELECT 
    DATE(completed_at) as sale_date,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    COUNT(CASE WHEN order_type = 'dine-in' THEN 1 END) as dine_in_orders,
    COUNT(CASE WHEN order_type = 'takeout' THEN 1 END) as takeout_orders,
    COUNT(CASE WHEN order_type = 'delivery' THEN 1 END) as delivery_orders
FROM orders
WHERE status = 'completed' AND completed_at IS NOT NULL
GROUP BY DATE(completed_at)
ORDER BY sale_date DESC;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Procedure to update inventory stock
CREATE PROCEDURE sp_update_inventory_stock(
    IN p_item_id INT,
    IN p_transaction_type VARCHAR(20),
    IN p_quantity DECIMAL(10, 2),
    IN p_user_id INT,
    IN p_user_type VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_current_stock DECIMAL(10, 2);
    DECLARE v_new_stock DECIMAL(10, 2);
    
    -- Get current stock
    SELECT current_stock INTO v_current_stock
    FROM inventory_items
    WHERE id = p_item_id;
    
    -- Calculate new stock based on transaction type
    IF p_transaction_type IN ('restock', 'adjustment') THEN
        SET v_new_stock = v_current_stock + p_quantity;
    ELSE -- usage or waste
        SET v_new_stock = v_current_stock - p_quantity;
    END IF;
    
    -- Ensure stock doesn't go negative
    IF v_new_stock < 0 THEN
        SET v_new_stock = 0;
    END IF;
    
    -- Update inventory
    UPDATE inventory_items
    SET current_stock = v_new_stock,
        last_restocked = IF(p_transaction_type = 'restock', NOW(), last_restocked),
        updated_at = NOW()
    WHERE id = p_item_id;
    
    -- Log transaction
    INSERT INTO inventory_transactions (
        item_id, transaction_type, quantity,
        previous_stock, new_stock, notes,
        performed_by, user_type
    ) VALUES (
        p_item_id, p_transaction_type, p_quantity,
        v_current_stock, v_new_stock, p_notes,
        p_user_id, p_user_type
    );
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger for menu item creation
CREATE TRIGGER trg_menu_item_created
AFTER INSERT ON menu_items
FOR EACH ROW
BEGIN
    INSERT INTO audit_trail (user_type, user_id, username, action, action_type, target_table, target_id)
    VALUES ('admin', COALESCE(NEW.created_by, 0), 'system', 
            CONCAT('Created menu item: ', NEW.name), 'create', 'menu_items', NEW.id);
END //

-- Trigger for menu item update
CREATE TRIGGER trg_menu_item_updated
AFTER UPDATE ON menu_items
FOR EACH ROW
BEGIN
    INSERT INTO audit_trail (user_type, user_id, username, action, action_type, target_table, target_id)
    VALUES ('admin', COALESCE(NEW.created_by, 0), 'system', 
            CONCAT('Updated menu item: ', NEW.name), 'update', 'menu_items', NEW.id);
END //

DELIMITER ;

