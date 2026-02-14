# QUICK REFERENCE - DEFAULT ACCOUNTS

## 🔑 Default Login Credentials

### SUPER ADMIN
- **Portal:** http://localhost/cafenowa_enhanced/login/admin-login.html
- **Email:** superadmin@cafenowa.com
- **Password:** SuperAdmin123!
- **Permissions:**
  - ✅ Create/manage all user types (including admins)
  - ✅ View audit trail
  - ✅ Full system access

### ADMIN
- **Portal:** http://localhost/cafenowa_enhanced/login/admin-login.html
- **Email:** admin@cafenowa.com
- **Password:** Admin123!
- **Permissions:**
  - ✅ Create/manage employees and customers
  - ✅ View audit trail
  - ❌ Cannot create other admins

### EMPLOYEE
- **Portal:** http://localhost/cafenowa_enhanced/login/login.html
- **Email:** employee@cafenowa.com
- **Password:** Employee123!
- **Permissions:**
  - ✅ Access employee dashboard
  - ✅ Manage orders (if implemented)
  - ❌ Cannot access admin functions

### CUSTOMER
- **Portal:** http://localhost/cafenowa_enhanced/login/login.html
- **Email:** customer@cafenowa.com
- **Password:** Customer123!
- **Permissions:**
  - ✅ Access customer landing page
  - ✅ Place orders (if implemented)
  - ❌ Cannot access admin or employee functions

---

## 🌐 URLs Quick Access

### Login Portals
- Admin Login: http://localhost/cafenowa_enhanced/login/admin-login.html
- User Login: http://localhost/cafenowa_enhanced/login/login.html

### Dashboards
- Admin Dashboard: http://localhost/cafenowa_enhanced/admin/dashboard.php
- Employee Dashboard: http://localhost/cafenowa_enhanced/employee/dashboard.php
- Customer Landing: http://localhost/cafenowa_enhanced/customer/landing.php

### APIs
- Login: http://localhost/cafenowa_enhanced/api/login.php
- Logout: http://localhost/cafenowa_enhanced/api/logout.php
- User Management: http://localhost/cafenowa_enhanced/api/users.php
- Audit Trail: http://localhost/cafenowa_enhanced/api/audit-trail.php

---

## 🔐 Security Features

### SQL Injection Prevention
✅ All queries use PDO prepared statements
```php
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### Password Hashing
✅ Bcrypt with cost factor 12
✅ Never store plain text passwords
✅ Timing attack resistant verification

### Account Lockout
✅ 5 failed attempts = 30 minute lockout
✅ Tracked by IP address
✅ All attempts logged

### Audit Trail
✅ Every action logged
✅ Read-only (cannot edit/delete)
✅ Includes IP, timestamp, user details

---

## 📊 Database Tables

### super_admins
- Highest privilege level
- Can create admin accounts
- Full system access

### admins
- Can manage employees and customers
- Cannot create other admins
- Created by super admins

### employees
- Staff members
- Limited dashboard access
- Created by admins or super admins

### customers
- Public users
- Customer-facing features
- Self-registration enabled

### audit_trail
- All system actions logged
- Non-editable
- Viewable by admins only

### login_attempts
- Tracks all login attempts
- Used for account lockout
- Success/failure tracking

---

## ⚠️ IMPORTANT SECURITY NOTES

### BEFORE GOING LIVE:
1. ❗ Change ALL default passwords
2. ❗ Set ENABLE_DEBUG to false in config.php
3. ❗ Use strong database credentials
4. ❗ Enable HTTPS (SSL)
5. ❗ Restrict database user permissions
6. ❗ Regular backups
7. ❗ Update SITE_URL in config.php

### Password Requirements:
- Minimum 8 characters
- Mix of uppercase, lowercase, numbers recommended
- Special characters recommended

---

## 🚦 User Role Matrix

| Action | Super Admin | Admin | Employee | Customer |
|--------|------------|-------|----------|----------|
| Create Super Admins | ✅ | ❌ | ❌ | ❌ |
| Create Admins | ✅ | ❌ | ❌ | ❌ |
| Create Employees | ✅ | ✅ | ❌ | ❌ |
| Create Customers | ✅ | ✅ | ❌ | ❌ |
| View Audit Trail | ✅ | ✅ | ❌ | ❌ |
| Manage Orders | ✅ | ✅ | ✅ | ❌ |
| Place Orders | ✅ | ✅ | ✅ | ✅ |

---

## 🛠️ Common Tasks

### Creating a New Admin (as Super Admin)
1. Login as super admin
2. Go to "User Management" tab
3. Click "Create User"
4. Select "Admin" as user type
5. Fill in details
6. Click "Create User"

### Viewing Audit Logs
1. Login as admin or super admin
2. Go to "Audit Trail" tab
3. Use filters to narrow results:
   - User Type
   - Action Type
   - Date Range
   - Search by username/IP

### Unlocking a Locked Account
**Option 1 - Wait:**
- Account auto-unlocks after 30 minutes

**Option 2 - Manual (phpMyAdmin):**
```sql
DELETE FROM login_attempts 
WHERE email = 'user@example.com';
```

### Resetting a User's Password
Run in phpMyAdmin:
```sql
-- For super admin
UPDATE super_admins 
SET password_hash = '$2y$12$...' 
WHERE email = 'user@example.com';

-- For admin
UPDATE admins 
SET password_hash = '$2y$12$...' 
WHERE email = 'user@example.com';

-- For employee
UPDATE employees 
SET password_hash = '$2y$12$...' 
WHERE email = 'user@example.com';

-- For customer
UPDATE customers 
SET password_hash = '$2y$12$...' 
WHERE email = 'user@example.com';
```

Generate hash with provided password utility.

---

## 📝 Notes

- All timestamps are server time (PHP timezone)
- Session timeout: 1 hour (configurable in config.php)
- Maximum login attempts: 5 (configurable)
- Lockout duration: 30 minutes (configurable)
- Audit logs are permanent (never deleted automatically)

---

## 🆘 Emergency Access

If locked out of all accounts:

1. Access database via phpMyAdmin
2. Clear login attempts:
   ```sql
   TRUNCATE TABLE login_attempts;
   ```
3. Reset password (see password utility)
4. Or create new super admin:
   ```sql
   INSERT INTO super_admins (username, email, password_hash, full_name) 
   VALUES ('emergency', 'emergency@cafenowa.com', 
           '$2y$12$...', 'Emergency Admin');
   ```

---

**Last Updated:** February 2026
**System Version:** 2.0 Enhanced Security
