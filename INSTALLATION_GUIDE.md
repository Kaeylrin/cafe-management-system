# CAFE NOWA - ENHANCED SECURITY SETUP GUIDE

## 📋 Overview

This enhanced version of Cafe Nowa includes:
1. ✅ Separate login portals for Admin and Regular Users
2. ✅ Super Admin and Admin tables with role-based access
3. ✅ SQL injection prevention using prepared statements
4. ✅ Audit trail (view-only for admins)
5. ✅ Secure password hashing
6. ✅ Login attempt tracking and account lockout
7. ✅ Session management

## 🔧 Prerequisites

- XAMPP, WAMP, or LAMP (Apache + MySQL + PHP 7.4+)
- Web browser (Chrome, Firefox, Edge, etc.)
- Basic knowledge of PHP and MySQL

## 📁 File Structure

```
cafenowa_enhanced/
├── config/
│   └── config.php                 # Database configuration
├── database/
│   └── schema.sql                 # Database schema
├── api/
│   ├── login.php                  # Login API
│   ├── logout.php                 # Logout API
│   ├── users.php                  # User management API
│   └── audit-trail.php            # Audit trail API
├── login/
│   ├── admin-login.html           # Admin login page
│   └── login.html                 # User login page
├── admin/
│   └── dashboard.php              # Admin dashboard
├── employee/
│   └── dashboard.php              # Employee dashboard (create from your existing)
└── customer/
    └── landing.php                # Customer landing (create from your existing)
```

## 🚀 STEP-BY-STEP INSTALLATION

### STEP 1: Install XAMPP/WAMP

1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP (default location: C:\xampp)
3. Start Apache and MySQL from XAMPP Control Panel

### STEP 2: Setup the Project Files

1. Copy the `cafenowa_enhanced` folder to your web server:
   - For XAMPP: `C:\xampp\htdocs\cafenowa_enhanced`
   - For WAMP: `C:\wamp64\www\cafenowa_enhanced`

2. Verify the folder structure matches the structure shown above

### STEP 3: Create the Database

1. Open your browser and go to: http://localhost/phpmyadmin

2. Click "SQL" tab at the top

3. Open the file `database/schema.sql` from the project folder

4. Copy the ENTIRE content of `schema.sql`

5. Paste it into the SQL tab in phpMyAdmin

6. Click "Go" to execute

7. You should see "cafenowa_db" database created with these tables:
   - super_admins
   - admins
   - employees
   - customers
   - audit_trail
   - login_attempts

### STEP 4: Configure Database Connection

1. Open `config/config.php` in a text editor

2. Update these lines if needed (default XAMPP settings are already set):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cafenowa_db');
   define('DB_USER', 'root');        // Change in production
   define('DB_PASS', '');            // Change in production
   ```

3. **IMPORTANT FOR PRODUCTION**: Change DB_USER and DB_PASS to secure credentials

4. Update SITE_URL to match your setup:
   ```php
   define('SITE_URL', 'http://localhost/cafenowa_enhanced');
   ```

### STEP 5: Test Database Connection

1. Create a test file `test-connection.php` in the root folder:

```php
<?php
require_once 'config/config.php';

try {
    $db = getDB();
    echo "✅ Database connection successful!<br>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM super_admins");
    $result = $stmt->fetch();
    echo "✅ Found {$result['count']} super admin(s)<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

2. Visit: http://localhost/cafenowa_enhanced/test-connection.php

3. You should see: "✅ Database connection successful!"

4. Delete `test-connection.php` after testing

### STEP 6: Integrate Your Existing Pages

You have existing pages in your original project. Copy them to the new structure:

1. **Employee Dashboard:**
   - Copy your `Employee/employee-dashboard.html` to `cafenowa_enhanced/employee/dashboard.php`
   - Update the file extension from `.html` to `.php`
   - Add authentication check at the top:
   
   ```php
   <?php
   session_start();
   if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee') {
       header('Location: ../login/login.html');
       exit;
   }
   ?>
   <!-- Your existing HTML below -->
   ```

2. **Customer Landing:**
   - Copy your `Landingpage/landing.html` to `cafenowa_enhanced/customer/landing.php`
   - Add authentication check:
   
   ```php
   <?php
   session_start();
   if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'customer') {
       header('Location: ../login/login.html');
       exit;
   }
   ?>
   <!-- Your existing HTML below -->
   ```

3. **Copy Assets:**
   - Copy all your images, CSS, and JS files to the appropriate folders
   - Update paths in your HTML to match the new structure

### STEP 7: Test the System

#### Test 1: Super Admin Login
1. Go to: http://localhost/cafenowa_enhanced/login/admin-login.html
2. Login with:
   - Email: `superadmin@cafenowa.com`
   - Password: `SuperAdmin123!`
3. You should be redirected to the admin dashboard
4. You should see "Create User" button

#### Test 2: Admin Login
1. Logout from super admin
2. Go to: http://localhost/cafenowa_enhanced/login/admin-login.html
3. Login with:
   - Email: `admin@cafenowa.com`
   - Password: `Admin123!`
4. You should be redirected to the admin dashboard
5. You CAN create employees and customers
6. You CANNOT create admins (only super admin can)

#### Test 3: Employee Login
1. Go to: http://localhost/cafenowa_enhanced/login/login.html (NOT admin-login)
2. Login with:
   - Email: `employee@cafenowa.com`
   - Password: `Employee123!`
3. You should be redirected to employee dashboard

#### Test 4: Customer Login
1. Go to: http://localhost/cafenowa_enhanced/login/login.html
2. Login with:
   - Email: `customer@cafenowa.com`
   - Password: `Customer123!`
3. You should be redirected to customer landing page

#### Test 5: Create New Users (as Super Admin)
1. Login as super admin
2. Go to "User Management" tab
3. Click "Create User"
4. Fill in the form:
   - User Type: Admin / Employee / Customer
   - Username: test123
   - Email: test@example.com
   - Full Name: Test User
   - Password: Password123!
5. Click "Create User"
6. New user should appear in the table

#### Test 6: View Audit Trail
1. While logged in as admin/super admin
2. Click "Audit Trail" tab
3. You should see all login attempts, user creations, etc.
4. Try the filters:
   - User Type filter
   - Action Type filter
   - Date range filter
   - Search box
5. Note: Audit trail is READ-ONLY (cannot edit or delete)

#### Test 7: SQL Injection Prevention
1. Try to login with:
   - Email: `admin@cafenowa.com' OR '1'='1`
   - Password: anything
2. Login should FAIL (SQL injection prevented)
3. All attempts are logged in audit trail

#### Test 8: Account Lockout
1. Try to login with wrong password 5 times
2. On the 6th attempt, you should see:
   "Account temporarily locked due to multiple failed login attempts"
3. Wait 30 minutes OR manually clear login_attempts table to unlock

### STEP 8: Security Best Practices

#### Change Default Passwords (CRITICAL!)

After installation, IMMEDIATELY change all default passwords:

```sql
-- Run this in phpMyAdmin SQL tab

-- Change Super Admin password (use your own secure password)
UPDATE super_admins 
SET password_hash = PASSWORD('YourNewSecurePassword123!') 
WHERE email = 'superadmin@cafenowa.com';

-- Change Admin password
UPDATE admins 
SET password_hash = PASSWORD('YourNewSecurePassword123!') 
WHERE email = 'admin@cafenowa.com';
```

**Note:** The PASSWORD() function is deprecated. For production, use this PHP script instead:

```php
<?php
// change-passwords.php (delete after use)
require_once 'config/config.php';

$db = getDB();

// New passwords
$newSuperAdminPass = 'YourSecurePassword123!';
$newAdminPass = 'YourSecurePassword456!';

// Hash passwords
$hash1 = password_hash($newSuperAdminPass, PASSWORD_BCRYPT, ['cost' => 12]);
$hash2 = password_hash($newAdminPass, PASSWORD_BCRYPT, ['cost' => 12]);

// Update
$db->prepare("UPDATE super_admins SET password_hash = ? WHERE email = ?")->execute([$hash1, 'superadmin@cafenowa.com']);
$db->prepare("UPDATE admins SET password_hash = ? WHERE email = ?")->execute([$hash2, 'admin@cafenowa.com']);

echo "Passwords updated successfully!";
// DELETE THIS FILE AFTER RUNNING
?>
```

#### Production Checklist

Before deploying to production:

1. ✅ Change database credentials in `config/config.php`
2. ✅ Set `ENABLE_DEBUG` to `false` in `config/config.php`
3. ✅ Change all default passwords
4. ✅ Set up HTTPS (SSL certificate)
5. ✅ Update `SITE_URL` in `config/config.php`
6. ✅ Restrict database user permissions (don't use root)
7. ✅ Enable PHP error logging (don't display errors to users)
8. ✅ Set proper file permissions (644 for files, 755 for folders)
9. ✅ Create regular database backups
10. ✅ Delete test files (test-connection.php, change-passwords.php)

## 🔐 Security Features Explained

### 1. Separate Login Portals

- **Admin Portal** (`admin-login.html`): Only checks super_admins and admins tables
- **User Portal** (`login.html`): Only checks employees and customers tables
- Prevents customers/employees from accessing admin functions

### 2. Role-Based Access Control

- **Super Admin**: Can create/manage all user types including admins
- **Admin**: Can create/manage employees and customers only
- **Employee**: Limited dashboard access
- **Customer**: Public-facing features only

### 3. SQL Injection Prevention

All database queries use PDO prepared statements:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```
This prevents SQL injection attacks.

### 4. Audit Trail

- Every action is logged (login, logout, create, update, delete)
- Includes timestamp, user, IP address, action type
- Read-only access (cannot be edited or deleted)
- Viewable by admins and super admins only

### 5. Password Security

- Passwords hashed using bcrypt with cost factor 12
- Password verify function protects against timing attacks
- Minimum 8 character requirement
- Plain text passwords never stored

### 6. Account Lockout

- After 5 failed login attempts from same IP
- Account locked for 30 minutes
- All attempts logged in audit trail

## 🐛 Troubleshooting

### Error: "Database Connection Failed"
- Check XAMPP MySQL is running
- Verify database credentials in config.php
- Ensure cafenowa_db database exists

### Error: "Access denied for user"
- Check DB_USER and DB_PASS in config.php
- Default XAMPP: user='root', password=''

### Login redirects to login page
- Check session is working (php.ini session settings)
- Verify authentication code in dashboard pages
- Clear browser cache and cookies

### Audit trail shows "Error loading"
- Check user has admin or super_admin role
- Verify audit_trail table exists
- Check browser console for errors

### Cannot create admin users
- Only super admin can create admin users
- Regular admins can only create employees/customers
- Check your user role in database

## 📞 Need Help?

If you encounter issues:
1. Check browser console (F12) for JavaScript errors
2. Check Apache error logs (xampp/apache/logs/error.log)
3. Check PHP error logs
4. Verify all files are in correct locations
5. Ensure correct file permissions

## 🎓 Learning Resources

- PHP PDO Tutorial: https://www.php.net/manual/en/book.pdo.php
- Password Hashing: https://www.php.net/manual/en/function.password-hash.php
- Session Security: https://www.php.net/manual/en/session.security.php

## ✅ You're Done!

Your Cafe Nowa system now has:
- ✅ Separate login portals
- ✅ Super admin and admin roles
- ✅ SQL injection prevention
- ✅ Complete audit trail
- ✅ Secure authentication
- ✅ Account lockout protection

Enjoy your secure cafe management system! ☕🔐
