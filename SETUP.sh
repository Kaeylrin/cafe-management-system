#!/bin/bash

# Cafe Nowa - Quick Setup Script
# This script helps set up the enhanced version

echo "============================================"
echo "  CAFE NOWA - ENHANCED VERSION SETUP"
echo "============================================"
echo ""

# Check if MySQL is running
echo "Checking MySQL service..."
if command -v mysql &> /dev/null; then
    echo "✓ MySQL is installed"
else
    echo "✗ MySQL is not installed. Please install MySQL first."
    exit 1
fi

echo ""
echo "SETUP STEPS:"
echo "1. Import the database schema"
echo "2. Configure database connection"
echo "3. Test the login"
echo ""

# Database setup
read -p "Do you want to import the database now? (y/n): " answer
if [ "$answer" = "y" ]; then
    read -p "MySQL username [root]: " db_user
    db_user=${db_user:-root}
    
    read -sp "MySQL password: " db_pass
    echo ""
    
    echo "Importing database..."
    mysql -u "$db_user" -p"$db_pass" < database/schema_enhanced.sql
    
    if [ $? -eq 0 ]; then
        echo "✓ Database imported successfully!"
    else
        echo "✗ Database import failed. Please check your credentials."
        exit 1
    fi
fi

echo ""
echo "============================================"
echo "  SETUP COMPLETE!"
echo "============================================"
echo ""
echo "DEFAULT LOGIN CREDENTIALS:"
echo ""
echo "Employee/Customer Login (login/login.html):"
echo "  - Employee: employee@cafenowa.com / password"
echo "  - Customer: customer@cafenowa.com / password"
echo ""
echo "Admin Login (login/admin-login.html):"
echo "  - Admin: admin@cafenowa.com / password"
echo "  - Super Admin: superadmin@cafenowa.com / password"
echo ""
echo "NEXT STEPS:"
echo "1. Edit config/config.php to set your database credentials"
echo "2. Navigate to login/login.html to test employee login"
echo "3. Navigate to login/admin-login.html to access admin dashboard"
echo "4. Read FIXES_AND_ENHANCEMENTS.md for complete documentation"
echo ""
echo "API ENDPOINTS AVAILABLE:"
echo "  - /api/login.php - Authentication"
echo "  - /api/menu.php - Menu management"
echo "  - /api/inventory.php - Inventory management"
echo "  - /api/users.php - User management"
echo ""
echo "For help, see FIXES_AND_ENHANCEMENTS.md"
echo "============================================"
