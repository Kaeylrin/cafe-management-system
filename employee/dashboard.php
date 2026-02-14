<?php
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: ../login/login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Café Nowa</title>
    <link rel="stylesheet" href="employee-styles.css">
    <style>
        /* Additional Inventory Styles */
        .inventory-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }

        .inventory-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .category-filter {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .inventory-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }

        .inventory-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .inventory-card.low-stock {
            border-left: 4px solid #ff4444;
            background: #fff5f5;
        }

        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .inventory-header h3 {
            font-size: 16px;
            color: #333;
            margin: 0;
        }

        .inventory-category {
            background: #f0f0f0;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            color: #666;
            font-weight: 600;
        }

        .inventory-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stock-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stock-label {
            font-size: 13px;
            color: #666;
        }

        .stock-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .stock-status {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.low {
            background: #ffe0e0;
            color: #ff4444;
        }

        .status-badge.ok {
            background: #e0f7e0;
            color: #00C851;
        }

        .error, .loading {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 14px;
        }

        .error {
            color: #ff4444;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Café Nowa</div>
        <nav class="nav-menu">
            <a href="#" class="nav-item active" data-section="orders">
                <span class="icon"></span> Orders
            </a>
            <a href="#" class="nav-item" data-section="menu">
                <span class="icon"></span> Menu
            </a>
            <a href="#" class="nav-item" data-section="inventory">
                <span class="icon"></span> Inventory
            </a>
            <a href="#" class="nav-item" data-section="profile">
                <span class="icon"></span> Profile
            </a>
        </nav>
        <div class="sidebar-footer">
            <button class="logout-btn" id="logoutBtn">Logout</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h1 id="pageTitle">Order Management</h1>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">E</div>
                    <div class="user-details">
                        <span class="user-name" id="userName">Employee</span>
                        <span class="user-role">Staff Member</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Orders Section -->
        <section id="orders" class="content-section active">
            <div class="stats-row">
                <div class="stat-box">
                    <h3>Pending Orders</h3>
                    <p class="stat-number" id="pendingCount">0</p>
                </div>
                <div class="stat-box">
                    <h3>In Progress</h3>
                    <p class="stat-number" id="progressCount">0</p>
                </div>
                <div class="stat-box">
                    <h3>Completed Today</h3>
                    <p class="stat-number" id="completedCount">0</p>
                </div>
            </div>

            <div class="orders-container">
                <h2>Active Orders</h2>
                <div class="filter-controls">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="confirmed">Confirmed</button>
                    <button class="filter-btn" data-status="preparing">Preparing</button>
                    <button class="filter-btn" data-status="ready">Ready</button>
                </div>
                <div class="orders-grid" id="ordersGrid">
                    <div class="loading">Loading orders...</div>
                </div>
            </div>
        </section>

        <!-- Menu Section -->
        <section id="menu" class="content-section">
            <h2>Menu Items</h2>
            <div class="menu-grid" id="menuGrid">
                <div class="loading">Loading menu...</div>
            </div>
        </section>

        <!-- Inventory Section -->
        <section id="inventory" class="content-section">
            <h2>Inventory Status</h2>
            <div class="inventory-stats">
                <div class="stat-box">
                    <h3>Low Stock Items</h3>
                    <p class="stat-number" id="lowStockCount">0</p>
                </div>
                <div class="stat-box">
                    <h3>Total Items</h3>
                    <p class="stat-number" id="totalItemsCount">0</p>
                </div>
            </div>
            <div class="inventory-container">
                <div class="filter-controls">
                    <input type="text" id="inventorySearch" placeholder="Search inventory..." class="search-input">
                    <select id="categoryFilter" class="category-filter">
                        <option value="all">All Categories</option>
                    </select>
                </div>
                <div class="inventory-grid" id="inventoryGrid">
                    <div class="loading">Loading inventory...</div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile" class="content-section">
            <h2>My Profile</h2>
            <div class="profile-container">
                <div class="profile-info">
                    <div class="info-row">
                        <label>Full Name:</label>
                        <span id="profileName">Loading...</span>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <span id="profileEmail">Loading...</span>
                    </div>
                    <div class="info-row">
                        <label>Position:</label>
                        <span id="profilePosition">Employee</span>
                    </div>
                    <div class="info-note">
                        <strong>Note:</strong> To update your profile information, please contact your administrator.
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Global variables
        let currentFilter = 'all';
        let ordersData = [];
        let inventoryData = [];
        let refreshInterval = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        function initializeApp() {
            // Get user info from PHP session via sessionStorage (set during login)
            const userName = sessionStorage.getItem('userName') || '<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Employee'); ?>';
            document.getElementById('userName').textContent = userName;

            // Load data
            loadOrders();
            loadMenu();
            loadInventory();
            loadProfile();

            // Set up auto-refresh for orders (every 10 seconds)
            refreshInterval = setInterval(loadOrders, 10000);

            // Navigation
            setupNavigation();

            // Filter buttons
            setupFilterButtons();

            // Inventory filters
            setupInventoryFilters();

            // Logout button
            document.getElementById('logoutBtn').addEventListener('click', handleLogout);

            // Mobile menu toggle
            document.getElementById('menuToggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
        }

        function setupNavigation() {
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.content-section');

            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    navItems.forEach(nav => nav.classList.remove('active'));
                    sections.forEach(section => section.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const sectionName = this.dataset.section;
                    document.getElementById(sectionName).classList.add('active');
                    
                    const titles = {
                        'orders': 'Order Management',
                        'menu': 'Menu Items',
                        'inventory': 'Inventory Status',
                        'profile': 'My Profile'
                    };
                    document.getElementById('pageTitle').textContent = titles[sectionName];

                    if (sectionName === 'inventory') {
                        loadInventory();
                    }
                });
            });
        }

        function setupFilterButtons() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.status;
                    renderOrders();
                });
            });
        }

        function setupInventoryFilters() {
            const searchInput = document.getElementById('inventorySearch');
            const categoryFilter = document.getElementById('categoryFilter');

            if (searchInput) searchInput.addEventListener('input', filterInventory);
            if (categoryFilter) categoryFilter.addEventListener('change', filterInventory);
        }

        async function loadOrders() {
            try {
                const response = await fetch('../api/orders.php');
                const data = await response.json();

                if (data.success) {
                    ordersData = data.data;
                    updateOrderStats();
                    renderOrders();
                } else {
                    console.error('Failed to load orders:', data.message);
                }
            } catch (error) {
                console.error('Error loading orders:', error);
                document.getElementById('ordersGrid').innerHTML = '<div class="error">Error loading orders. Please refresh the page.</div>';
            }
        }

        function updateOrderStats() {
            const pending = ordersData.filter(o => o.status === 'pending').length;
            const inProgress = ordersData.filter(o => ['confirmed', 'preparing', 'ready'].includes(o.status)).length;
            const today = ordersData.filter(o => {
                const orderDate = new Date(o.created_at).toDateString();
                const todayDate = new Date().toDateString();
                return orderDate === todayDate && o.status === 'completed';
            }).length;

            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('progressCount').textContent = inProgress;
            document.getElementById('completedCount').textContent = today;
        }

        function renderOrders() {
            const grid = document.getElementById('ordersGrid');
            
            let filtered = ordersData;
            if (currentFilter !== 'all') {
                filtered = ordersData.filter(o => o.status === currentFilter);
            }

            filtered = filtered.filter(o => !['completed', 'cancelled'].includes(o.status));

            if (filtered.length === 0) {
                grid.innerHTML = '<div class="loading">No orders to display</div>';
                return;
            }

            grid.innerHTML = filtered.map(order => `
                <div class="order-card ${order.status}">
                    <div class="order-header">
                        <span class="order-id">${order.order_number}</span>
                        <span class="order-status status-${order.status}">${order.status.toUpperCase()}</span>
                    </div>
                    <div class="order-details">
                        <p class="customer-name">${order.customer_name}</p>
                        <p class="order-time">${formatTime(order.created_at)}</p>
                        ${order.table_number ? `<p class="table-number">Table: ${order.table_number}</p>` : ''}
                    </div>
                    <div class="order-items">
                        ${order.items_summary || 'No items'}
                    </div>
                    <div class="order-total">Total: ₱${parseFloat(order.total_amount).toFixed(2)}</div>
                    <div class="order-actions">
                        ${getOrderActions(order)}
                    </div>
                </div>
            `).join('');

            setupOrderActions();
        }

        function getOrderActions(order) {
            switch(order.status) {
                case 'pending':
                    return `<button class="btn-accept" data-id="${order.id}">Accept Order</button>`;
                case 'confirmed':
                    return `<button class="btn-start" data-id="${order.id}">Start Preparing</button>`;
                case 'preparing':
                    return `<button class="btn-ready" data-id="${order.id}">Mark as Ready</button>`;
                case 'ready':
                    return `<button class="btn-complete" data-id="${order.id}">Complete Order</button>`;
                default:
                    return '';
            }
        }

        function setupOrderActions() {
            document.querySelectorAll('.btn-accept').forEach(btn => {
                btn.addEventListener('click', function() {
                    updateOrderStatus(this.dataset.id, 'confirmed');
                });
            });

            document.querySelectorAll('.btn-start').forEach(btn => {
                btn.addEventListener('click', function() {
                    updateOrderStatus(this.dataset.id, 'preparing');
                });
            });

            document.querySelectorAll('.btn-ready').forEach(btn => {
                btn.addEventListener('click', function() {
                    updateOrderStatus(this.dataset.id, 'ready');
                });
            });

            document.querySelectorAll('.btn-complete').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Mark this order as completed?')) {
                        updateOrderStatus(this.dataset.id, 'completed');
                    }
                });
            });
        }

        async function updateOrderStatus(orderId, status) {
            try {
                const response = await fetch('../api/orders.php?action=update_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status
                    })
                });

                const data = await response.json();

                if (data.success) {
                    loadOrders();
                } else {
                    alert('Error updating order: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating order:', error);
                alert('Error updating order. Please try again.');
            }
        }

        async function loadMenu() {
            try {
                const response = await fetch('../api/menu.php');
                const data = await response.json();

                if (data.success) {
                    renderMenu(data.data);
                } else {
                    document.getElementById('menuGrid').innerHTML = '<div class="error">Error loading menu: ' + data.message + '</div>';
                }
            } catch (error) {
                console.error('Error loading menu:', error);
                document.getElementById('menuGrid').innerHTML = '<div class="error">Error loading menu. Please check your connection.</div>';
            }
        }

        function renderMenu(items) {
            const grid = document.getElementById('menuGrid');
            
            if (items.length === 0) {
                grid.innerHTML = '<div class="loading">No menu items available</div>';
                return;
            }

            grid.innerHTML = items.map(item => `
                <div class="menu-card ${!item.is_available ? 'unavailable' : ''}">
                    <h3>${item.name}</h3>
                    <p class="menu-description">${item.description || 'No description'}</p>
                    <p class="menu-price">₱${parseFloat(item.price).toFixed(2)}</p>
                    <span class="menu-status ${item.is_available ? 'available' : 'unavailable'}">
                        ${item.is_available ? 'Available' : 'Unavailable'}
                    </span>
                </div>
            `).join('');
        }

        async function loadInventory() {
            try {
                const response = await fetch('../api/inventory.php');
                const data = await response.json();

                if (data.success) {
                    inventoryData = data.data;
                    updateInventoryStats();
                    populateCategories();
                    renderInventory(data.data);
                } else {
                    document.getElementById('inventoryGrid').innerHTML = '<div class="error">Error loading inventory: ' + data.message + '</div>';
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
                document.getElementById('inventoryGrid').innerHTML = '<div class="error">Error loading inventory. Please check your connection.</div>';
            }
        }

        function updateInventoryStats() {
            const lowStockItems = inventoryData.filter(item => 
                parseFloat(item.current_stock) < parseFloat(item.minimum_stock)
            ).length;

            document.getElementById('lowStockCount').textContent = lowStockItems;
            document.getElementById('totalItemsCount').textContent = inventoryData.length;
        }

        function populateCategories() {
            const categories = [...new Set(inventoryData.map(item => item.category))];
            const categoryFilter = document.getElementById('categoryFilter');
            
            categoryFilter.innerHTML = '<option value="all">All Categories</option>';
            categories.forEach(category => {
                categoryFilter.innerHTML += `<option value="${category}">${category}</option>`;
            });
        }

        function renderInventory(items) {
            const grid = document.getElementById('inventoryGrid');
            
            if (items.length === 0) {
                grid.innerHTML = '<div class="loading">No inventory items found</div>';
                return;
            }

            grid.innerHTML = items.map(item => {
                const isLow = parseFloat(item.current_stock) < parseFloat(item.minimum_stock);
                
                return `
                    <div class="inventory-card ${isLow ? 'low-stock' : ''}">
                        <div class="inventory-header">
                            <h3>${item.item_name}</h3>
                            <span class="inventory-category">${item.category}</span>
                        </div>
                        <div class="inventory-details">
                            <div class="stock-info">
                                <span class="stock-label">Current Stock:</span>
                                <span class="stock-value">${parseFloat(item.current_stock).toFixed(2)} ${item.unit}</span>
                            </div>
                            <div class="stock-info">
                                <span class="stock-label">Minimum:</span>
                                <span class="stock-value">${parseFloat(item.minimum_stock).toFixed(2)} ${item.unit}</span>
                            </div>
                            <div class="stock-status">
                                ${isLow ? '<span class="status-badge low">⚠️ Low Stock</span>' : '<span class="status-badge ok">✓ OK</span>'}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterInventory() {
            const searchTerm = document.getElementById('inventorySearch').value.toLowerCase();
            const selectedCategory = document.getElementById('categoryFilter').value;

            let filtered = inventoryData;

            if (searchTerm) {
                filtered = filtered.filter(item => 
                    item.item_name.toLowerCase().includes(searchTerm) ||
                    item.category.toLowerCase().includes(searchTerm)
                );
            }

            if (selectedCategory !== 'all') {
                filtered = filtered.filter(item => item.category === selectedCategory);
            }

            renderInventory(filtered);
        }

        function loadProfile() {
            const fullName = sessionStorage.getItem('userName') || '<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'N/A'); ?>';
            const email = sessionStorage.getItem('userEmail') || '<?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?>';
            
            document.getElementById('profileName').textContent = fullName;
            document.getElementById('profileEmail').textContent = email;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
            
            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            
            return date.toLocaleDateString();
        }

        async function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                clearInterval(refreshInterval);
                try {
                    await fetch('../api/logout.php', { method: 'POST' });
                } catch (error) {
                    console.error('Logout error:', error);
                }
                sessionStorage.clear();
                window.location.href = '../login/login.html';
            }
        }
    </script>
</body>
</html>
