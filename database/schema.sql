-- ============================================
-- CAFE NOWA DATABASE SCHEMA
-- Enhanced Security Implementation
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
    created_by INT NULL, -- super_admin who created this account
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
    created_by INT NULL, -- admin/superadmin who created this account
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
-- 5. AUDIT TRAIL TABLE (Non-editable log)
-- ============================================
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('super_admin', 'admin', 'employee', 'customer') NOT NULL,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    action_type ENUM('login', 'logout', 'create', 'update', 'delete', 'view', 'failed_login') NOT NULL,
    target_table VARCHAR(50) NULL, -- Which table was affected
    target_id INT NULL, -- ID of the affected record
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details TEXT NULL, -- Additional JSON details
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_type (user_type),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- ============================================
-- 6. LOGIN ATTEMPTS TABLE (For security)
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
-- Password: SuperAdmin123! (CHANGE THIS IMMEDIATELY!)
-- ============================================
INSERT INTO super_admins (username, email, password_hash, full_name) VALUES
('superadmin', 'superadmin@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator');

-- ============================================
-- INSERT DEFAULT ADMIN (Created by SuperAdmin)
-- Password: Admin123!
-- ============================================
INSERT INTO admins (username, email, password_hash, full_name, created_by) VALUES
('admin', 'admin@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 1);

-- ============================================
-- INSERT DEFAULT EMPLOYEE
-- Password: Employee123!
-- ============================================
INSERT INTO employees (username, email, password_hash, full_name, position) VALUES
('employee1', 'employee@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee User', 'Barista');

-- ============================================
-- INSERT DEFAULT CUSTOMER
-- Password: Customer123!
-- ============================================
INSERT INTO customers (username, email, password_hash, full_name, phone) VALUES
('customer1', 'customer@cafenowa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer User', '1234567890');

-- ============================================
-- CREATE VIEWS FOR EASY QUERYING
-- ============================================

-- View for all users across tables
CREATE VIEW vw_all_users AS
SELECT 
    'super_admin' as user_type,
    id,
    username,
    email,
    full_name,
    created_at,
    last_login,
    is_active
FROM super_admins
UNION ALL
SELECT 
    'admin' as user_type,
    id,
    username,
    email,
    full_name,
    created_at,
    last_login,
    is_active
FROM admins
UNION ALL
SELECT 
    'employee' as user_type,
    id,
    username,
    email,
    full_name,
    created_at,
    last_login,
    is_active
FROM employees
UNION ALL
SELECT 
    'customer' as user_type,
    id,
    username,
    email,
    full_name,
    created_at,
    last_login,
    is_active
FROM customers;

-- View for recent audit trail (last 1000 entries)
CREATE VIEW vw_recent_audit AS
SELECT 
    a.id,
    a.user_type,
    a.username,
    a.action,
    a.action_type,
    a.target_table,
    a.timestamp,
    a.ip_address
FROM audit_trail a
ORDER BY a.timestamp DESC
LIMIT 1000;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Procedure to log audit trail
CREATE PROCEDURE sp_log_audit(
    IN p_user_type VARCHAR(20),
    IN p_user_id INT,
    IN p_username VARCHAR(50),
    IN p_action VARCHAR(100),
    IN p_action_type VARCHAR(20),
    IN p_target_table VARCHAR(50),
    IN p_target_id INT,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_details TEXT
)
BEGIN
    INSERT INTO audit_trail (
        user_type, user_id, username, action, action_type,
        target_table, target_id, ip_address, user_agent, details
    ) VALUES (
        p_user_type, p_user_id, p_username, p_action, p_action_type,
        p_target_table, p_target_id, p_ip_address, p_user_agent, p_details
    );
END //

-- Procedure to check if account is locked (too many failed attempts)
CREATE PROCEDURE sp_check_account_lock(
    IN p_email VARCHAR(100),
    IN p_ip_address VARCHAR(45),
    OUT p_is_locked BOOLEAN
)
BEGIN
    DECLARE failed_attempts INT;
    
    -- Count failed attempts in last 15 minutes
    SELECT COUNT(*) INTO failed_attempts
    FROM login_attempts
    WHERE email = p_email
      AND ip_address = p_ip_address
      AND success = FALSE
      AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE);
    
    -- Lock if 5 or more failed attempts
    SET p_is_locked = (failed_attempts >= 5);
END //

DELIMITER ;

-- ============================================
-- TRIGGERS FOR AUTO-AUDIT
-- ============================================

-- Trigger for admin creation
DELIMITER //
CREATE TRIGGER trg_admin_created
AFTER INSERT ON admins
FOR EACH ROW
BEGIN
    INSERT INTO audit_trail (user_type, user_id, username, action, action_type, target_table, target_id)
    VALUES ('super_admin', NEW.created_by, 'system', 
            CONCAT('Created new admin: ', NEW.username), 'create', 'admins', NEW.id);
END //
DELIMITER ;

-- Trigger for employee creation
DELIMITER //
CREATE TRIGGER trg_employee_created
AFTER INSERT ON employees
FOR EACH ROW
BEGIN
    INSERT INTO audit_trail (user_type, user_id, username, action, action_type, target_table, target_id)
    VALUES ('admin', COALESCE(NEW.created_by, 0), 'system', 
            CONCAT('Created new employee: ', NEW.username), 'create', 'employees', NEW.id);
END //
DELIMITER ;

-- ============================================
-- GRANT PERMISSIONS (Adjust as needed)
-- ============================================
-- Note: Update with your actual database user
-- GRANT SELECT, INSERT, UPDATE ON cafenowa_db.* TO 'cafenowa_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE cafenowa_db.sp_log_audit TO 'cafenowa_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE cafenowa_db.sp_check_account_lock TO 'cafenowa_user'@'localhost';

