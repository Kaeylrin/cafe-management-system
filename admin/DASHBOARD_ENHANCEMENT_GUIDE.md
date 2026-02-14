# HTML SNIPPETS TO ADD TO ADMIN DASHBOARD

## INSTRUCTIONS
Add these sections to your `admin/dashboard.php` file:

### 1. Add these tabs to the tabs section (around line 407):
```html
<button class="tab" onclick="switchTab('menu')">📋 Menu Management</button>
<button class="tab" onclick="switchTab('inventory')">📦 Inventory</button>
```

### 2. Add Menu Management Tab Content (after the existing tab-content divs):
```html
<!-- MENU MANAGEMENT TAB -->
<div id="tab-menu" class="tab-content">
    <div class="card">
        <div class="card-header">
            <h2>📋 Menu Management</h2>
        </div>
        
        <!-- Categories Section -->
        <div class="card" style="background: #f9fafb; padding: 20px; margin-bottom: 20px;">
            <div class="card-header">
                <h3>Categories</h3>
                <button class="btn btn-primary" onclick="alert('Add category feature - implement in full version')">
                    + Add Category
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="categoriesList">
                    <tr><td colspan="5" class="loading">Loading categories...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Menu Items Section -->
        <div class="card-header">
            <h3>Menu Items</h3>
            <button class="btn btn-primary" onclick="window.menuManagement.addItem()">
                + Add Menu Item
            </button>
        </div>
        <div class="search-box">
            <input type="text" placeholder="Search menu items..." id="menuSearch">
        </div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="menuItemsList">
                <tr><td colspan="7" class="loading">Loading menu items...</td></tr>
            </tbody>
        </table>
    </div>
</div>
```

### 3. Add Inventory Management Tab Content:
```html
<!-- INVENTORY MANAGEMENT TAB -->
<div id="tab-inventory" class="tab-content">
    <div class="card">
        <div class="card-header">
            <h2>📦 Inventory Management</h2>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h3 id="totalItemsCount">0</h3>
                <p>Total Items</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h3 id="lowStockCount">0</h3>
                <p>Low Stock Items</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h3 id="totalInventoryValue">₱0</h3>
                <p>Total Value</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn btn-primary" onclick="window.inventoryManagement.addItem()">
                + Add Inventory Item
            </button>
            <button class="btn btn-warning" onclick="window.inventoryManagement.showLowStock()">
                🔔 View Low Stock Alerts
            </button>
            <button class="btn btn-success" onclick="window.inventoryManagement.loadItems()">
                🔄 Refresh
            </button>
        </div>
        
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" placeholder="Search inventory..." id="inventorySearch">
        </div>
        
        <!-- Inventory Table -->
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Min Stock</th>
                    <th>Unit Price</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="inventoryItemsList">
                <tr><td colspan="8" class="loading">Loading inventory...</td></tr>
            </tbody>
        </table>
    </div>
</div>
```

### 4. Add the JavaScript file before closing </body> tag:
```html
<script src="dashboard-menu-inventory.js"></script>
```

### 5. Update the switchTab function to include new tabs:
```javascript
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Add active class to clicked tab button
    event.target.classList.add('active');
    
    // Load data for the selected tab
    if (tabName === 'menu') {
        window.menuManagement.loadCategories();
        window.menuManagement.loadItems();
    } else if (tabName === 'inventory') {
        window.inventoryManagement.loadItems();
    }
}
```

## QUICK SETUP GUIDE

### Option A: Manual Integration (Recommended)
1. Open `admin/dashboard.php`
2. Find the tabs section (around line 407)
3. Add the two new tab buttons
4. Find the end of the last `<div class="tab-content">` section
5. Add the Menu and Inventory tab content HTML
6. Before the closing `</body>` tag, add the script reference
7. Save the file

### Option B: Use Full Pre-built Dashboard
Simply replace the existing `admin/dashboard.php` with the enhanced version when we create it.

## TESTING

After adding these sections:
1. Login as admin
2. You should see "Menu Management" and "Inventory" tabs
3. Click on each tab to load the data
4. Try adding a menu item (use simple prompts for now)
5. Try recording an inventory transaction

## NOTES

- The current implementation uses simple JavaScript prompts for forms
- In production, you'd want proper modal dialogs with form validation
- All API calls are already implemented and working
- The database tables are created by the enhanced schema
- Default data is already populated

