# Cafe Nowa - Enhanced Security System 🔐☕

A comprehensive cafe management system with enterprise-level security features including role-based access control, SQL injection prevention, and complete audit trail.

## 🌟 Features Implemented

### ✅ Requirement 1: Separate Login Portals
- **Admin Portal** (`admin-login.html`): For Super Admins and Admins only
- **User Portal** (`login.html`): For Employees and Customers
- Prevents unauthorized role access

### ✅ Requirement 2: Super Admin & Admin Tables
- **Super Admins**: Highest privilege, can create admin accounts
- **Admins**: Can create employee and customer accounts
- **Employees**: Staff with limited access
- **Customers**: Public users
- Hierarchical role-based permissions

### ✅ Requirement 3: SQL Injection Prevention
- All database queries use **PDO Prepared Statements**
- Input sanitization with `htmlspecialchars()`
- Parameterized queries throughout
- No direct SQL string concatenation

### ✅ Requirement 4: Audit Trail
- **Read-Only** log of all system activities
- Tracks: Login/Logout, Create, Update, Delete, Failed logins
- Records: User, Timestamp, IP Address, Action Details
- Viewable by Admins and Super Admins only
- Cannot be edited or deleted (data integrity)

## 🔐 Additional Security Features

- **Password Hashing**: Bcrypt with cost factor 12
- **Account Lockout**: 5 failed attempts = 30-minute lockout
- **Session Management**: Secure session handling with regeneration
- **Login Attempt Tracking**: All attempts logged
- **CSRF Protection**: Ready for token implementation
- **XSS Prevention**: Output escaping and sanitization

## 📁 Project Structure

```
cafenowa_enhanced/
├── config/
│   └── config.php              # Database config & security settings
├── database/
│   └── schema.sql              # Complete database schema
├── api/
│   ├── login.php               # Secure authentication
│   ├── logout.php              # Session termination
│   ├── users.php               # User management CRUD
│   └── audit-trail.php         # Audit log viewer (read-only)
├── login/
│   ├── admin-login.html        # Admin portal login
│   └── login.html              # User portal login
├── admin/
│   └── dashboard.php           # Admin dashboard with user mgmt & audit
├── employee/
│   └── dashboard.php           # Employee dashboard
├── customer/
│   └── landing.php             # Customer landing page
├── INSTALLATION_GUIDE.md       # Step-by-step setup instructions
├── QUICK_REFERENCE.md          # Default accounts & URLs
└── password-utility.html       # Password hash generator
```

## 🚀 Quick Start

### Prerequisites
- XAMPP/WAMP/LAMP (PHP 7.4+, MySQL 5.7+)
- Web browser
- Basic PHP/MySQL knowledge

### Installation (5 Minutes)

1. **Install XAMPP**
   ```bash
   Download from: https://www.apachefriends.org/
   Start Apache and MySQL
   ```

2. **Copy Files**
   ```bash
   Copy cafenowa_enhanced/ to C:\xampp\htdocs\
   ```

3. **Create Database**
   - Open http://localhost/phpmyadmin
   - Go to SQL tab
   - Paste contents of `database/schema.sql`
   - Click "Go"

4. **Configure**
   - Edit `config/config.php` if needed
   - Default settings work with standard XAMPP

5. **Test**
   - Admin: http://localhost/cafenowa_enhanced/login/admin-login.html
   - User: http://localhost/cafenowa_enhanced/login/login.html

See **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** for detailed instructions.

## 🔑 Default Accounts

| Role | Email | Password | Portal |
|------|-------|----------|--------|
| Super Admin | superadmin@cafenowa.com | SuperAdmin123! | admin-login.html |
| Admin | admin@cafenowa.com | Admin123! | admin-login.html |
| Employee | employee@cafenowa.com | Employee123! | login.html |
| Customer | customer@cafenowa.com | Customer123! | login.html |

⚠️ **CHANGE ALL PASSWORDS IMMEDIATELY** after installation!

See **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** for complete reference.

## 🎯 Usage Examples

### Creating a New Admin (Super Admin Only)
1. Login as super admin
2. Go to User Management tab
3. Click "Create User"
4. Select "Admin" type
5. Fill in details
6. Submit

### Viewing Audit Trail
1. Login as admin or super admin
2. Click "Audit Trail" tab
3. Use filters:
   - User Type
   - Action Type
   - Date Range
   - Search

### Account Security
- **Locked out?** Wait 30 minutes or clear login_attempts table
- **Forgot password?** Use password-utility.html to generate new hash
- **Suspicious activity?** Check audit trail

## 🛡️ Security Implementation Details

### SQL Injection Prevention Example
```php
// ❌ VULNERABLE (Never do this)
$query = "SELECT * FROM users WHERE email = '$email'";

// ✅ SECURE (What we use)
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### Password Security Example
```php
// Store password (never store plain text!)
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verify password (timing-attack resistant)
if (password_verify($inputPassword, $storedHash)) {
    // Login successful
}
```

### Audit Trail Example
```php
logAudit(
    'admin',              // User type
    123,                  // User ID
    'john_admin',         // Username
    'Created new user',   // Action description
    'create',             // Action type
    'employees',          // Target table
    456,                  // Target ID
    '192.168.1.1',       // IP address
    'Mozilla/5.0...',    // User agent
    '{"username":"new_employee"}' // Details (JSON)
);
```

## 📊 Database Schema

### Tables Overview
- `super_admins` - Highest privilege accounts
- `admins` - Mid-level administrative accounts
- `employees` - Staff accounts
- `customers` - Public user accounts
- `audit_trail` - System activity log (read-only)
- `login_attempts` - Login tracking for security

All tables include:
- Timestamps (created_at, last_login)
- Active status flag
- Indexed email and username fields

## 🔄 Workflow Diagrams

### Login Flow
```
User enters credentials
    ↓
Check if admin or user portal
    ↓
Query appropriate tables only
    ↓
Check account lockout status
    ↓
Verify password hash
    ↓
Log attempt in login_attempts
    ↓
If success: Create session & redirect
If fail: Log failed attempt & show error
```

### User Creation Flow (Super Admin)
```
Super Admin clicks "Create User"
    ↓
Fills form (type, email, password, etc.)
    ↓
Frontend validation
    ↓
API receives request
    ↓
Check permissions (only super admin can create admin)
    ↓
Validate input & check duplicates
    ↓
Hash password
    ↓
Insert into appropriate table
    ↓
Log creation in audit_trail
    ↓
Return success
```

## 🧪 Testing

### Security Tests
1. **SQL Injection Test**
   - Try: `admin' OR '1'='1` as email
   - Expected: Login fails, attempt logged

2. **Account Lockout Test**
   - Enter wrong password 5 times
   - Expected: "Account locked" message

3. **Role Access Test**
   - Login as employee
   - Try to access admin dashboard
   - Expected: Redirect to login

4. **Audit Trail Test**
   - Perform various actions
   - Check audit trail tab
   - Expected: All actions logged

## ⚠️ Important Notes

### Before Production
- [ ] Change all default passwords
- [ ] Set `ENABLE_DEBUG = false`
- [ ] Use strong database credentials
- [ ] Enable HTTPS (SSL certificate)
- [ ] Restrict database user permissions
- [ ] Set up regular backups
- [ ] Configure proper file permissions
- [ ] Delete test/utility files

### Security Best Practices
- Never commit `config.php` to public repos
- Regularly review audit trail
- Monitor failed login attempts
- Keep PHP and MySQL updated
- Use environment variables for sensitive data
- Implement rate limiting on APIs
- Add CSRF tokens to forms

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection failed | Check XAMPP MySQL is running, verify credentials |
| Can't login | Clear browser cache, check user exists in correct table |
| Audit trail empty | Verify user has admin/super_admin role |
| Account locked | Clear login_attempts table or wait 30 minutes |
| Cannot create admins | Only super admins can create admin accounts |

See [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) for more troubleshooting.

## 📚 Documentation

- **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** - Complete setup instructions
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Default accounts and URLs
- **password-utility.html** - Password hash generator (delete after use!)

## 🤝 Contributing

This is a custom project for Cafe Nowa. Security improvements are welcome:
- SQL injection tests
- Additional security features
- Performance optimizations
- Bug fixes

## 📝 License

Proprietary - Cafe Nowa Internal Use Only

## 🙋 Support

For issues or questions:
1. Check INSTALLATION_GUIDE.md
2. Check QUICK_REFERENCE.md
3. Review audit trail for clues
4. Check Apache/PHP error logs

## 🎓 Learning Resources

- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Password Hashing Best Practices](https://www.php.net/manual/en/function.password-hash.php)
- [Session Security](https://www.php.net/manual/en/session.security.php)
- [OWASP Security Guidelines](https://owasp.org/)

## ✨ Features Comparison

| Feature | Before | After |
|---------|--------|-------|
| Login Security | localStorage | PDO prepared statements |
| Password Storage | Plain text | Bcrypt hash (cost 12) |
| Admin Access | Same portal | Separate admin portal |
| User Management | Manual | UI with role permissions |
| Audit Trail | None | Complete activity log |
| Account Protection | None | Automatic lockout |
| SQL Injection | Vulnerable | Protected |
| Session Security | Basic | Regenerated + validated |

---

## 🎉 You're All Set!

Your Cafe Nowa system is now secured with:
- ✅ Separate login portals for admins and users
- ✅ Super admin and admin role hierarchy
- ✅ Complete SQL injection protection
- ✅ Read-only audit trail
- ✅ Secure password hashing
- ✅ Account lockout protection
- ✅ Comprehensive activity logging

**Remember to:**
1. Change default passwords immediately
2. Delete password-utility.html after use
3. Review security settings before going live
4. Check audit trail regularly

Enjoy your secure cafe management system! ☕🔐

---

**Version:** 2.0 Enhanced Security  
**Last Updated:** February 2026  
**PHP Version:** 7.4+  
**MySQL Version:** 5.7+
