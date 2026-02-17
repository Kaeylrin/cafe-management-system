<?php
session_start();
// Check if user is logged in and is admin or super_admin
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'super_admin'])) {
    header('Location: ../login/admin-login.html');
    exit;
}

// Get user info for display
$userName = $_SESSION['full_name'] ?? 'Admin';
$userType = $_SESSION['user_type'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cafe Nowa</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        
        .header {
            background: linear-gradient(135deg, #FFCE99 0%, #562F00 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-left h1 { font-size: 24px; margin-bottom: 5px; }
        .header-left p { opacity: 0.9; font-size: 14px; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .user-info { text-align: right; }
        .user-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .btn-logout { background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; transition: background 0.3s; }
        .btn-logout:hover { background: rgba(255,255,255,0.3); }

        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        
        /* TABS */
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; }
        .tab { padding: 12px 24px; background: none; border: none; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab.active { color: #667eea; border-bottom-color: #667eea; }
        .tab:hover { color: #667eea; }
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* CARDS */
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { font-size: 20px; color: #333; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #667eea; }
        .stat-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; font-weight: 600; }
        .stat-card .stat-value { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 5px; }
        .stat-card .stat-label { font-size: 12px; color: #999; }

        /* BUTTONS */
        .btn-primary { background: linear-gradient(135deg, #FFCE99 0%, #562F00 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: transform 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: #e0e0e0; color: #333; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-left: 8px; }
        .btn-danger { background: #ff4444; color: white; }
        .btn-success { background: #00C851; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; border: 1px solid #ddd; background: white; cursor: pointer; margin-left: 5px; border-radius: 4px;}
        .btn-sm.active { background: #667eea; color: white; border-color: #667eea; }
        
        /* Action buttons styling - FIX for white buttons */
        .action-buttons { white-space: nowrap; }
        .action-buttons .btn-sm { margin: 0 2px; }
        .action-buttons .btn-primary { background: #667eea; color: white; border-color: #667eea; }
        .action-buttons .btn-primary:hover { background: #5568d3; }
        .action-buttons .btn-danger { background: #ff4444; color: white; border-color: #ff4444; }
        .action-buttons .btn-danger:hover { background: #cc0000; }
        .action-buttons .btn-warning { background: #ffbb33; color: #333; border-color: #ffbb33; }
        .action-buttons .btn-warning:hover { background: #ff8800; color: white; }

        /* TABLES */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #666; border-bottom: 2px solid #e0e0e0; }
        table td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        table tr:hover { background: #f8f9fa; }

        /* BADGES */
        .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-available { background: #d4edda; color: #155724; }
        .badge-unavailable { background: #f8d7da; color: #721c24; }
        .badge-low { background: #fff3cd; color: #856404; }
        .badge-ok { background: #d4edda; color: #155724; }

        /* MODALS */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; animation: fadeIn 0.3s; }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
        .modal-body { padding: 20px; }

        /* FORMS */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .top-items-list { list-style: none; padding: 0; }
        .top-items-list li { padding: 12px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .item-name { font-weight: 600; color: #333; }
        .item-stats { text-align: right; font-size: 12px; color: #666; }
        
        .loading { text-align: center; padding: 20px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1> Cafe Nowa</h1>
            <p>Admin Dashboard</p>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div id="userName"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-badge" id="userRole"><?php echo strtoupper($userType); ?></div>
            </div>
            <button class="btn-logout" onclick="handleLogout()">Logout</button>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('sales')">Sales & Statistics</button>
            <button class="tab" onclick="switchTab('menu')">Menu Management</button>
            <button class="tab" onclick="switchTab('inventory')">Inventory</button>
            <button class="tab" onclick="switchTab('statistics')">Detailed Stats</button>
            <?php if ($userType === 'super_admin'): ?>
            <button class="tab" onclick="switchTab('users')">User Management</button>
            <button class="tab" onclick="switchTab('audit')">Audit Trail</button>
            <?php endif; ?>
        </div>

        <div id="tab-sales" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Today's Revenue</h3>
                    <div class="stat-value" id="todayRevenue">₱0.00</div>
                    <div class="stat-label" id="todayOrders">0 orders</div>
                </div>
                <div class="stat-card">
                    <h3>This Week</h3>
                    <div class="stat-value" id="weekRevenue">₱0.00</div>
                    <div class="stat-label" id="weekOrders">0 orders</div>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <div class="stat-value" id="monthRevenue">₱0.00</div>
                    <div class="stat-label" id="monthOrders">0 orders</div>
                </div>
                <div class="stat-card">
                    <h3>Average Order Value</h3>
                    <div class="stat-value" id="avgOrderValue">₱0.00</div>
                    <div class="stat-label">Today</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Top Selling Items (Last 30 Days)</h2>
                </div>
                <ul class="top-items-list" id="topItemsList">
                    <li class="loading">Loading...</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Employee Performance</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Orders Handled</th>
                            <th>Total Sales</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody id="employeePerformanceBody">
                        <tr><td colspan="5" class="loading">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-menu" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Menu Items</h2>
                    <button class="btn-primary" onclick="openCreateMenuModal()">+ Add Menu Item</button>
                </div>
                
                <div class="form-group">
                    <label>Filter by Category</label>
                    <select id="filterCategory" onchange="loadMenuItems()">
                        <option value="all">All Categories</option>
                    </select>
                </div>

                <table id="menuTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menuTableBody">
                        <tr><td colspan="6" class="loading">Loading menu items...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-inventory" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Inventory Items</h2>
                    <button class="btn-primary" onclick="openCreateInventoryModal()">+ Add Inventory Item</button>
                </div>
                
                <table id="inventoryTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Unit</th>
                            <th>Min Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr><td colspan="7" class="loading">Loading inventory...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-statistics" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Today's Revenue</h3>
                    <div class="stat-value" id="stat-today-revenue">₱0.00</div>
                    <div class="stat-label" id="stat-today-orders">0 orders</div>
                </div>
                <div class="stat-card">
                    <h3>This Week</h3>
                    <div class="stat-value" id="stat-week-revenue">₱0.00</div>
                    <div class="stat-label" id="stat-week-orders">0 orders</div>
                </div>
                <div class="stat-card">
                    <h3>This Month</h3>
                    <div class="stat-value" id="stat-month-revenue">₱0.00</div>
                    <div class="stat-label" id="stat-month-orders">0 orders</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Revenue Trends</h2>
                    <div>
                        <button class="btn-sm" onclick="updateRevenueChart(7)">7 Days</button>
                        <button class="btn-sm active" onclick="updateRevenueChart(14)">14 Days</button>
                        <button class="btn-sm" onclick="updateRevenueChart(30)">30 Days</button>
                    </div>
                </div>
                <div style="position: relative; height:40vh; width:80vw">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Order Types</h2>
                </div>
                <div style="position: relative; height:40vh; width:80vw">
                    <canvas id="orderTypesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- USER MANAGEMENT TAB (Super Admin Only) -->
        <div id="tab-users" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>User Management</h2>
                    <button class="btn-primary" onclick="openAddUserModal()">➕ Add New User</button>
                </div>
                
                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <select id="userTypeFilter" onchange="filterUsers()" style="padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <option value="all">All Users</option>
                        <option value="super_admin">Super Admins</option>
                        <option value="admin">Admins</option>
                        <option value="employee">Employees</option>
                        <option value="customer">Customers</option>
                    </select>
                    <input type="text" id="userSearchInput" placeholder="Search users..." 
                           onkeyup="searchUsers()" 
                           style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr><td colspan="7" class="loading">Loading users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AUDIT TRAIL TAB (Super Admin Only) -->
        <div id="tab-audit" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>📋 Audit Trail (Read-Only)</h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-secondary" onclick="exportAuditLog()">📥 Export CSV</button>
                        <button class="btn-secondary" onclick="refreshAuditLog()">🔄 Refresh</button>
                    </div>
                </div>

                <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <select id="auditUserTypeFilter" onchange="filterAuditLog()" style="padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <option value="">All User Types</option>
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                        <option value="customer">Customer</option>
                    </select>
                    
                    <select id="auditActionTypeFilter" onchange="filterAuditLog()" style="padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="view">View</option>
                    </select>
                    
                    <input type="date" id="auditDateFrom" onchange="filterAuditLog()" style="padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                    <input type="date" id="auditDateTo" onchange="filterAuditLog()" style="padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                    
                    <input type="text" id="auditSearchInput" placeholder="Search logs..." 
                           onkeyup="searchAuditLog()" 
                           style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #e0e0e0;">
                </div>

                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ff9800;">
                    <strong>🔒 Security Notice:</strong> This audit trail is READ-ONLY and cannot be modified or deleted. All actions are permanently logged for compliance and security purposes.
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Type</th>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Action Type</th>
                            <th>Target</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr><td colspan="8" class="loading">Loading audit logs...</td></tr>
                    </tbody>
                </table>

                <div id="auditPagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                </div>
            </div>
        </div>

        <!-- ADD USER MODAL -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add New User</h3>
                    <button class="btn-close" onclick="closeAddUserModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="addUserAlert"></div>
                    <form id="addUserForm" onsubmit="handleAddUser(event)">
                        <div class="form-group">
                            <label>User Type *</label>
                            <select id="newUserType" required onchange="toggleUserTypeFields()">
                                <option value="">Select Type</option>
                                <option value="admin">Admin</option>
                                <option value="employee">Employee</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" id="newUsername" required pattern="[a-zA-Z0-9_]{3,50}" 
                                       title="3-50 characters, letters, numbers, underscore only">
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" id="newEmail" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" id="newFullName" required>
                            </div>
                            <div class="form-group" id="positionGroup" style="display: none;">
                                <label>Position</label>
                                <input type="text" id="newPosition" placeholder="e.g., Barista, Cashier">
                            </div>
                            <div class="form-group" id="phoneGroup" style="display: none;">
                                <label>Phone</label>
                                <input type="tel" id="newPhone" placeholder="e.g., 09123456789">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" id="newPassword" required minlength="8" 
                                       title="At least 8 characters">
                            </div>
                            <div class="form-group">
                                <label>Confirm Password *</label>
                                <input type="password" id="newPasswordConfirm" required minlength="8">
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                            <button type="submit" class="btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- EDIT USER MODAL -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit User</h3>
                    <button class="btn-close" onclick="closeEditUserModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="editUserAlert"></div>
                    <form id="editUserForm" onsubmit="handleEditUser(event)">
                        <input type="hidden" id="editUserId">
                        <input type="hidden" id="editUserType">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" id="editFullName" required>
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" id="editEmail" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select id="editIsActive">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" id="editPassword" minlength="8">
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                            <button type="submit" class="btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="menuModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="menuModalTitle">Add Menu Item</h3>
                <button class="btn-close" onclick="closeMenuModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="menuModalAlert"></div>
                <form id="menuForm" onsubmit="handleMenuSubmit(event)">
                    <input type="hidden" id="menuItemId">
                    <div class="form-group">
                        <label>Category *</label>
                        <select id="menuCategory" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" id="menuName" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="menuDescription"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price (₱) *</label>
                        <input type="number" id="menuPrice" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" id="menuAvailable" checked> Available</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" id="menuFeatured"> Featured Item</label>
                    </div>
                    <button type="submit" class="btn-primary">Save Menu Item</button>
                    <button type="button" class="btn-secondary" onclick="closeMenuModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div id="inventoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="inventoryModalTitle">Add Inventory Item</h3>
                <button class="btn-close" onclick="closeInventoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="inventoryModalAlert"></div>
                <form id="inventoryForm" onsubmit="handleInventorySubmit(event)">
                    <input type="hidden" id="inventoryItemId">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" id="inventoryItemName" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category *</label>
                            <input type="text" id="inventoryCategory" required placeholder="e.g., Coffee, Dairy">
                        </div>
                        <div class="form-group">
                            <label>Unit *</label>
                            <input type="text" id="inventoryUnit" required placeholder="e.g., kg, liters">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Stock *</label>
                            <input type="number" id="inventoryCurrentStock" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Minimum Stock *</label>
                            <input type="number" id="inventoryMinStock" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Maximum Stock</label>
                            <input type="number" id="inventoryMaxStock" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Unit Price (₱)</label>
                            <input type="number" id="inventoryUnitPrice" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" id="inventorySupplier">
                    </div>
                    <button type="submit" class="btn-primary">Save Inventory Item</button>
                    <button type="button" class="btn-secondary" onclick="closeInventoryModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div id="restockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Restock Item</h3>
                <button class="btn-close" onclick="closeRestockModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="restockModalAlert"></div>
                <form id="restockForm" onsubmit="handleRestockSubmit(event)">
                    <input type="hidden" id="restockItemId">
                    <div class="form-group">
                        <label>Item: <strong id="restockItemName"></strong></label>
                    </div>
                    <div class="form-group">
                        <label>Current Stock: <strong id="restockCurrentStock"></strong> <span id="restockUnit"></span></label>
                    </div>
                    <div class="form-group">
                        <label>Add Quantity *</label>
                        <input type="number" id="restockQuantity" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="restockNotes"></textarea>
                    </div>
                    <button type="submit" class="btn-success">Restock Item</button>
                    <button type="button" class="btn-secondary" onclick="closeRestockModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // 2. FIXED: Attach switchTab to window object immediately so it's globally available
        window.switchTab = function(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from buttons
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            const tab = document.getElementById('tab-' + tabName);
            if (tab) tab.classList.add('active');
            
            // Make button active (if triggered by event)
            if (event && event.target && event.target.classList.contains('tab')) {
                event.target.classList.add('active');
            } else {
                // Fallback: highlight button by onclick attribute if event.target isn't the button
                const btn = document.querySelector(`.tab[onclick*="'${tabName}'"]`);
                if(btn) btn.classList.add('active');
            }
            
            // Load specific data based on tab
            if (tabName === 'sales') {
                loadSalesStats();
                loadEmployeePerformance();
            } else if (tabName === 'menu') {
                loadMenuItems();
            } else if (tabName === 'inventory') {
                loadInventory();
            } else if (tabName === 'statistics') {
                loadStatistics();
            } else if (tabName === 'users') {
                // Load users when switching to user management tab
                if (typeof loadUsers === 'function') {
                    loadUsers();
                }
            } else if (tabName === 'audit') {
                // Load audit trail when switching to audit tab
                if (typeof loadAuditLog === 'function') {
                    loadAuditLog(1);
                }
            }
        };

        // Global variables
        let currentUserType = '';
        let menuCategories = [];
        let menuItems = [];
        let inventoryItems = [];
        let revenueChart = null;
        let orderTypesChart = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
            loadSalesStats();
            loadCategories();
            loadMenuItems();
            loadInventory();
            loadEmployeePerformance();
            
            // Refresh stats every 30 seconds
            setInterval(loadSalesStats, 30000);
        });

        function checkAuth() {
            const isLoggedIn = sessionStorage.getItem('isLoggedIn');
            const userType = sessionStorage.getItem('userType');

            if (!isLoggedIn || !['super_admin', 'admin'].includes(userType)) {
                window.location.href = '../login/admin-login.html';
                return;
            }

            document.getElementById('userName').textContent = sessionStorage.getItem('fullName');
            document.getElementById('userRole').textContent = userType.replace('_', ' ').toUpperCase();
            currentUserType = userType;
        }

        // --- SALES & STATS ---
        async function loadSalesStats() {
            try {
                const response = await fetch('../api/sales.php?action=overview');
                const data = await response.json();

                if (data.success) {
                    const stats = data.data;
                    document.getElementById('todayRevenue').textContent = '₱' + parseFloat(stats.today.total_revenue).toFixed(2);
                    document.getElementById('todayOrders').textContent = stats.today.total_orders + ' orders';
                    document.getElementById('avgOrderValue').textContent = '₱' + parseFloat(stats.today.avg_order_value).toFixed(2);
                    
                    document.getElementById('weekRevenue').textContent = '₱' + parseFloat(stats.this_week.total_revenue).toFixed(2);
                    document.getElementById('weekOrders').textContent = stats.this_week.total_orders + ' orders';
                    
                    document.getElementById('monthRevenue').textContent = '₱' + parseFloat(stats.this_month.total_revenue).toFixed(2);
                    document.getElementById('monthOrders').textContent = stats.this_month.total_orders + ' orders';
                    
                    const topItemsList = document.getElementById('topItemsList');
                    if (stats.top_items && stats.top_items.length > 0) {
                        topItemsList.innerHTML = stats.top_items.map(item => `
                            <li>
                                <span class="item-name">${item.item_name}</span>
                                <span class="item-stats">
                                    Sold: ${item.total_sold} | Revenue: ₱${parseFloat(item.total_revenue).toFixed(2)}
                                </span>
                            </li>
                        `).join('');
                    } else {
                        topItemsList.innerHTML = '<li>No sales data available</li>';
                    }
                }
            } catch (error) {
                console.error('Error loading sales stats:', error);
            }
        }

        async function loadEmployeePerformance() {
            try {
                const response = await fetch('../api/sales.php?action=employee_performance');
                const data = await response.json();

                if (data.success) {
                    const tbody = document.getElementById('employeePerformanceBody');
                    if (data.data && data.data.length > 0) {
                        tbody.innerHTML = data.data.map(emp => `
                            <tr>
                                <td>${emp.full_name}</td>
                                <td>${emp.position}</td>
                                <td>${emp.orders_handled || 0}</td>
                                <td>₱${parseFloat(emp.total_sales || 0).toFixed(2)}</td>
                                <td>${emp.completed_orders || 0}</td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5">No data available</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Error loading employee performance:', error);
            }
        }

        // --- MENU ---
        async function loadCategories() {
            try {
                const response = await fetch('../api/menu.php?action=categories');
                const data = await response.json();
                if (data.success) {
                    menuCategories = data.data;
                    const filterSelect = document.getElementById('filterCategory');
                    const modalSelect = document.getElementById('menuCategory');
                    const options = data.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
                    filterSelect.innerHTML = '<option value="all">All Categories</option>' + options;
                    modalSelect.innerHTML = '<option value="">Select Category</option>' + options;
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        async function loadMenuItems() {
            try {
                const categoryFilter = document.getElementById('filterCategory').value;
                let url = '../api/menu.php?action=items';
                if (categoryFilter && categoryFilter !== 'all') {
                    url += '&category_id=' + categoryFilter;
                }
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    menuItems = data.data;
                    renderMenuItems();
                }
            } catch (error) {
                console.error('Error loading menu items:', error);
            }
        }

        function renderMenuItems() {
            const tbody = document.getElementById('menuTableBody');
            if (menuItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">No menu items found</td></tr>';
                return;
            }
            tbody.innerHTML = menuItems.map(item => `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>${item.category_name || 'N/A'}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td><span class="badge ${item.is_available ? 'badge-available' : 'badge-unavailable'}">${item.is_available ? 'Available' : 'Unavailable'}</span></td>
                    <td>${item.is_featured ? '⭐ Featured' : ''}</td>
                    <td>
                        <button class="btn-primary btn-sm" onclick="editMenuItem(${item.id})">Edit</button>
                        <button class="btn-danger btn-sm" onclick="deleteMenuItem(${item.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function openCreateMenuModal() {
            document.getElementById('menuModalTitle').textContent = 'Add Menu Item';
            document.getElementById('menuForm').reset();
            document.getElementById('menuItemId').value = '';
            document.getElementById('menuAvailable').checked = true;
            document.getElementById('menuModal').classList.add('active');
        }

        function closeMenuModal() {
            document.getElementById('menuModal').classList.remove('active');
            document.getElementById('menuModalAlert').innerHTML = '';
        }

        async function editMenuItem(id) {
            const item = menuItems.find(i => i.id == id);
            if (!item) return;
            document.getElementById('menuModalTitle').textContent = 'Edit Menu Item';
            document.getElementById('menuItemId').value = item.id;
            document.getElementById('menuCategory').value = item.category_id;
            document.getElementById('menuName').value = item.name;
            document.getElementById('menuDescription').value = item.description || '';
            document.getElementById('menuPrice').value = item.price;
            document.getElementById('menuAvailable').checked = item.is_available == 1;
            document.getElementById('menuFeatured').checked = item.is_featured == 1;
            document.getElementById('menuModal').classList.add('active');
        }

        async function handleMenuSubmit(event) {
            event.preventDefault();
            const itemId = document.getElementById('menuItemId').value;
            const formData = {
                category_id: document.getElementById('menuCategory').value,
                name: document.getElementById('menuName').value,
                description: document.getElementById('menuDescription').value,
                price: document.getElementById('menuPrice').value,
                image_url: '',
                display_order: 0,
                is_available: document.getElementById('menuAvailable').checked ? 1 : 0,
                is_featured: document.getElementById('menuFeatured').checked ? 1 : 0
            };

            try {
                let url, method;
                if (itemId) {
                    url = '../api/menu.php?action=item';
                    method = 'PUT';
                    formData.id = itemId;
                } else {
                    url = '../api/menu.php?action=item';
                    method = 'POST';
                }
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();
                if (data.success) {
                    closeMenuModal();
                    loadMenuItems();
                    alert(itemId ? 'Menu item updated!' : 'Menu item created!');
                } else {
                    document.getElementById('menuModalAlert').innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                document.getElementById('menuModalAlert').innerHTML = '<div class="alert alert-error">Error saving item.</div>';
            }
        }

        async function deleteMenuItem(id) {
            if (!confirm('Delete this menu item?')) return;
            try {
                const response = await fetch('../api/menu.php?action=item', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    loadMenuItems();
                    alert('Deleted successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error(error);
            }
        }

        // --- INVENTORY ---
        async function loadInventory() {
            try {
                const response = await fetch('../api/inventory.php');
                const data = await response.json();
                if (data.success) {
                    inventoryItems = data.data;
                    renderInventory(data.data);
                } else {
                    document.getElementById('inventoryTableBody').innerHTML = '<tr><td colspan="7">No inventory data</td></tr>';
                }
            } catch (error) {
                console.error(error);
            }
        }

        function renderInventory(items) {
            const tbody = document.getElementById('inventoryTableBody');
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7">No inventory items found</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(item => {
                const isLow = parseFloat(item.current_stock) < parseFloat(item.minimum_stock);
                return `
                    <tr>
                        <td><strong>${item.item_name}</strong></td>
                        <td>${item.category}</td>
                        <td>${parseFloat(item.current_stock).toFixed(2)}</td>
                        <td>${item.unit}</td>
                        <td>${parseFloat(item.minimum_stock).toFixed(2)}</td>
                        <td><span class="badge ${isLow ? 'badge-low' : 'badge-ok'}">${isLow ? '⚠️ Low Stock' : '✓ OK'}</span></td>
                        <td>
                            <button class="btn-success btn-sm" onclick="openRestockModal(${item.id})">Restock</button>
                            <button class="btn-primary btn-sm" onclick="editInventoryItem(${item.id})">Edit</button>
                            <button class="btn-danger btn-sm" onclick="deleteInventoryItem(${item.id})">Delete</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function openCreateInventoryModal() {
            document.getElementById('inventoryModalTitle').textContent = 'Add Inventory Item';
            document.getElementById('inventoryForm').reset();
            document.getElementById('inventoryItemId').value = '';
            document.getElementById('inventoryModal').classList.add('active');
        }

        function closeInventoryModal() {
            document.getElementById('inventoryModal').classList.remove('active');
            document.getElementById('inventoryModalAlert').innerHTML = '';
        }

        async function editInventoryItem(id) {
            const item = inventoryItems.find(i => i.id == id);
            if (!item) return;
            document.getElementById('inventoryModalTitle').textContent = 'Edit Inventory Item';
            document.getElementById('inventoryItemId').value = item.id;
            document.getElementById('inventoryItemName').value = item.item_name;
            document.getElementById('inventoryCategory').value = item.category;
            document.getElementById('inventoryUnit').value = item.unit;
            document.getElementById('inventoryCurrentStock').value = item.current_stock;
            document.getElementById('inventoryMinStock').value = item.minimum_stock;
            document.getElementById('inventoryMaxStock').value = item.maximum_stock || '';
            document.getElementById('inventoryUnitPrice').value = item.unit_price || '';
            document.getElementById('inventorySupplier').value = item.supplier || '';
            document.getElementById('inventoryModal').classList.add('active');
        }

        async function handleInventorySubmit(event) {
            event.preventDefault();
            const itemId = document.getElementById('inventoryItemId').value;
            const formData = {
                item_name: document.getElementById('inventoryItemName').value,
                category: document.getElementById('inventoryCategory').value,
                unit: document.getElementById('inventoryUnit').value,
                current_stock: document.getElementById('inventoryCurrentStock').value,
                minimum_stock: document.getElementById('inventoryMinStock').value,
                maximum_stock: document.getElementById('inventoryMaxStock').value || null,
                unit_price: document.getElementById('inventoryUnitPrice').value || 0,
                supplier: document.getElementById('inventorySupplier').value || null
            };

            try {
                let url, method;
                if (itemId) {
                    url = '../api/inventory.php?action=item';
                    method = 'PUT';
                    formData.id = itemId;
                } else {
                    url = '../api/inventory.php?action=item';
                    method = 'POST';
                }
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();
                if (data.success) {
                    closeInventoryModal();
                    loadInventory();
                    alert(itemId ? 'Item updated!' : 'Item created!');
                } else {
                    document.getElementById('inventoryModalAlert').innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                document.getElementById('inventoryModalAlert').innerHTML = '<div class="alert alert-error">Error saving item.</div>';
            }
        }

        async function deleteInventoryItem(id) {
            if (!confirm('Delete this inventory item?')) return;
            try {
                const response = await fetch('../api/inventory.php?action=item', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    loadInventory();
                    alert('Deleted successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error(error);
            }
        }

        // --- RESTOCK ---
        function openRestockModal(id) {
            const item = inventoryItems.find(i => i.id == id);
            if (!item) return;
            document.getElementById('restockItemId').value = item.id;
            document.getElementById('restockItemName').textContent = item.item_name;
            document.getElementById('restockCurrentStock').textContent = parseFloat(item.current_stock).toFixed(2);
            document.getElementById('restockUnit').textContent = item.unit;
            document.getElementById('restockForm').reset();
            document.getElementById('restockModal').classList.add('active');
        }

        function closeRestockModal() {
            document.getElementById('restockModal').classList.remove('active');
            document.getElementById('restockModalAlert').innerHTML = '';
        }

        async function handleRestockSubmit(event) {
            event.preventDefault();
            const itemId = document.getElementById('restockItemId').value;
            const quantity = document.getElementById('restockQuantity').value;
            const notes = document.getElementById('restockNotes').value;

            try {
                const response = await fetch('../api/inventory.php?action=restock', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, quantity: quantity, notes: notes })
                });
                const data = await response.json();
                if (data.success) {
                    closeRestockModal();
                    loadInventory();
                    alert('Item restocked successfully!');
                } else {
                    document.getElementById('restockModalAlert').innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                document.getElementById('restockModalAlert').innerHTML = '<div class="alert alert-error">Error restocking item.</div>';
            }
        }

        async function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                try {
                    await fetch('../api/logout.php', { method: 'POST' });
                    sessionStorage.clear();
                    window.location.href = '../login/admin-login.html';
                } catch (error) {
                    console.error('Logout error:', error);
                    sessionStorage.clear();
                    window.location.href = '../login/admin-login.html';
                }
            }
        }

        // --- STATISTICS CHART.JS ---
        async function loadStatistics() {
            try {
                // Reuse overview for top cards
                const response = await fetch('../api/sales.php?action=overview');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('stat-today-revenue').textContent = '₱' + parseFloat(stats.today.total_revenue).toFixed(2);
                    document.getElementById('stat-today-orders').textContent = stats.today.total_orders + ' orders';
                    document.getElementById('stat-week-revenue').textContent = '₱' + parseFloat(stats.this_week.total_revenue).toFixed(2);
                    document.getElementById('stat-week-orders').textContent = stats.this_week.total_orders + ' orders';
                    document.getElementById('stat-month-revenue').textContent = '₱' + parseFloat(stats.this_month.total_revenue).toFixed(2);
                    document.getElementById('stat-month-orders').textContent = stats.this_month.total_orders + ' orders';
                }
                
                // Initialize Charts
                await createRevenueChart(14);
                await createOrderTypesChart();
                
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        async function createRevenueChart(days = 14) {
            try {
                const response = await fetch(`../api/sales.php?action=daily&days=${days}`);
                const data = await response.json();
                
                if (!data.success) return;
                
                const chartData = data.data;
                const labels = chartData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const revenues = chartData.map(item => parseFloat(item.total_revenue));
                const orders = chartData.map(item => parseInt(item.total_orders));
                
                const ctx = document.getElementById('revenueChart').getContext('2d');
                
                if (revenueChart) {
                    revenueChart.destroy();
                }
                
                revenueChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Revenue (₱)',
                                data: revenues,
                                borderColor: 'rgb(102, 126, 234)',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Orders',
                                data: orders,
                                borderColor: 'rgb(0, 200, 81)',
                                backgroundColor: 'rgba(0, 200, 81, 0.1)',
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Revenue (₱)' } },
                            y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating revenue chart:', error);
            }
        }

        async function createOrderTypesChart() {
            try {
                const response = await fetch('../api/sales.php?action=daily&days=30');
                const data = await response.json();
                
                if (!data.success) return;
                
                let dineIn = 0, takeout = 0, delivery = 0;
                data.data.forEach(day => {
                    dineIn += parseInt(day.dine_in || 0);
                    takeout += parseInt(day.takeout || 0);
                    delivery += parseInt(day.delivery || 0);
                });
                
                const ctx = document.getElementById('orderTypesChart').getContext('2d');
                
                if (orderTypesChart) {
                    orderTypesChart.destroy();
                }
                
                orderTypesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Dine-in', 'Takeout', 'Delivery'],
                        datasets: [{
                            data: [dineIn, takeout, delivery],
                            backgroundColor: ['rgba(102, 126, 234, 0.8)', 'rgba(0, 200, 81, 0.8)', 'rgba(255, 187, 51, 0.8)'],
                            borderWidth: 2,
                            borderColor: 'white'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            } catch (error) {
                console.error('Error creating order types chart:', error);
            }
        }

        async function updateRevenueChart(days) {
            // Update button states
            if(event && event.target) {
                document.querySelectorAll('.card-header button').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.classList.add('active');
            }
            await createRevenueChart(days);
        }
    </script>

    <!-- User Management and Audit Trail Module (Super Admin Only) -->
    <script src="admin-user-audit.js"></script>
</body>
</html>