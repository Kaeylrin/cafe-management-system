<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cafe Nowa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-left h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header-left p {
            opacity: 0.9;
            font-size: 14px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            text-align: right;
        }

        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 20px;
            color: #333;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-super-admin {
            background: #d4af37;
            color: white;
        }

        .badge-admin {
            background: #667eea;
            color: white;
        }

        .badge-employee {
            background: #48c774;
            color: white;
        }

        .badge-customer {
            background: #3298dc;
            color: white;
        }

        .badge-login {
            background: #48c774;
            color: white;
        }

        .badge-logout {
            background: #999;
            color: white;
        }

        .badge-create {
            background: #3298dc;
            color: white;
        }

        .badge-update {
            background: #ffdd57;
            color: #333;
        }

        .badge-delete {
            background: #f14668;
            color: white;
        }

        .badge-failed-login {
            background: #f14668;
            color: white;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #666;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 16px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .pagination button:hover:not(:disabled) {
            background: #f0f0f0;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .page-info {
            font-size: 14px;
            color: #666;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-error {
            background: #fee;
            color: #c00;
        }

        .alert-success {
            background: #efe;
            color: #060;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>🔐 Admin Dashboard</h1>
            <p>Cafe Nowa Management System</p>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div id="userName">Loading...</div>
                <div class="user-badge" id="userRole">Admin</div>
            </div>
            <button class="btn-logout" onclick="handleLogout()">Logout</button>
        </div>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('users')">User Management</button>
            <button class="tab" onclick="switchTab('audit')">Audit Trail</button>
        </div>

        <!-- User Management Tab -->
        <div id="tab-users" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h2>User Management</h2>
                    <button class="btn-primary" onclick="openCreateUserModal()" id="btnCreateUser">
                        + Create User
                    </button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>User Type</label>
                        <select id="filterUserType" onchange="loadUsers()">
                            <option value="all">All Types</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="loading">Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" id="usersPagination"></div>
            </div>
        </div>

        <!-- Audit Trail Tab -->
        <div id="tab-audit" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2>Audit Trail (Read-Only)</h2>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label>User Type</label>
                        <select id="auditUserType" onchange="loadAuditTrail()">
                            <option value="">All Types</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Action Type</label>
                        <select id="auditActionType" onchange="loadAuditTrail()">
                            <option value="">All Actions</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                            <option value="create">Create</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                            <option value="failed_login">Failed Login</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" id="auditDateFrom" onchange="loadAuditTrail()">
                    </div>
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" id="auditDateTo" onchange="loadAuditTrail()">
                    </div>
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" id="auditSearch" placeholder="Username, IP..." onchange="loadAuditTrail()">
                    </div>
                </div>

                <div class="table-container">
                    <table id="auditTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Action</th>
                                <th>Action Type</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="auditTableBody">
                            <tr>
                                <td colspan="6" class="loading">Loading audit logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="pagination" id="auditPagination"></div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New User</h3>
                <button class="btn-close" onclick="closeCreateUserModal()">&times;</button>
            </div>

            <div id="modalAlert" class="alert"></div>

            <form id="createUserForm" onsubmit="handleCreateUser(event)">
                <div class="form-group">
                    <label>User Type *</label>
                    <select id="newUserType" required>
                        <option value="">Select Type</option>
                        <option value="admin" id="optionAdmin">Admin</option>
                        <option value="employee">Employee</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="newUsername" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="newEmail" required>
                </div>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="newFullName" required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" id="newPassword" minlength="8" required>
                    <small style="color: #999;">Minimum 8 characters</small>
                </div>

                <div class="form-group" id="positionGroup" style="display: none;">
                    <label>Position</label>
                    <input type="text" id="newPosition" value="Barista">
                </div>

                <div class="form-group" id="phoneGroup" style="display: none;">
                    <label>Phone</label>
                    <input type="tel" id="newPhone">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%;">
                    Create User
                </button>
            </form>
        </div>
    </div>

    <script>
        let currentUserType = '';
        let usersPage = 1;
        let auditPage = 1;

        // Check authentication on page load
        window.addEventListener('DOMContentLoaded', function() {
            checkAuth();
            loadUsers();
            
            // Show/hide create admin option based on user type
            const userType = sessionStorage.getItem('userType');
            if (userType !== 'super_admin') {
                document.getElementById('optionAdmin').style.display = 'none';
            }
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

        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Load data if needed
            if (tabName === 'audit') {
                loadAuditTrail();
            }
        }

        async function loadUsers(page = 1) {
            usersPage = page;
            const userType = document.getElementById('filterUserType').value;
            
            try {
                const response = await fetch(`../api/users.php?user_type=${userType}&page=${page}`);
                const data = await response.json();
                
                if (data.success) {
                    displayUsers(data.data.users);
                    displayPagination(data.data.pagination, 'usersPagination', loadUsers);
                } else {
                    document.getElementById('usersTableBody').innerHTML = 
                        `<tr><td colspan="7" style="text-align: center; color: #c00;">${data.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading users:', error);
                document.getElementById('usersTableBody').innerHTML = 
                    '<tr><td colspan="7" style="text-align: center; color: #c00;">Error loading users</td></tr>';
            }
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No users found</td></tr>';
                return;
            }
            
            tbody.innerHTML = users.map(user => `
                <tr>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.full_name}</td>
                    <td><span class="badge badge-${user.user_type.replace('_', '-')}">${user.user_type.replace('_', ' ')}</span></td>
                    <td>${user.is_active ? '<span style="color: #48c774;">●</span> Active' : '<span style="color: #f14668;">●</span> Inactive'}</td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td>${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</td>
                </tr>
            `).join('');
        }

        async function loadAuditTrail(page = 1) {
            auditPage = page;
            
            const params = new URLSearchParams({
                page: page,
                user_type: document.getElementById('auditUserType').value,
                action_type: document.getElementById('auditActionType').value,
                date_from: document.getElementById('auditDateFrom').value,
                date_to: document.getElementById('auditDateTo').value,
                search: document.getElementById('auditSearch').value
            });
            
            try {
                const response = await fetch(`../api/audit-trail.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    displayAuditLogs(data.data.logs);
                    displayPagination(data.data.pagination, 'auditPagination', loadAuditTrail);
                } else {
                    document.getElementById('auditTableBody').innerHTML = 
                        `<tr><td colspan="6" style="text-align: center; color: #c00;">${data.message}</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading audit trail:', error);
                document.getElementById('auditTableBody').innerHTML = 
                    '<tr><td colspan="6" style="text-align: center; color: #c00;">Error loading audit logs</td></tr>';
            }
        }

        function displayAuditLogs(logs) {
            const tbody = document.getElementById('auditTableBody');
            
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No audit logs found</td></tr>';
                return;
            }
            
            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>${new Date(log.timestamp).toLocaleString()}</td>
                    <td>${log.username}</td>
                    <td><span class="badge badge-${log.user_type.replace('_', '-')}">${log.user_type.replace('_', ' ')}</span></td>
                    <td>${log.action}</td>
                    <td><span class="badge badge-${log.action_type.replace('_', '-')}">${log.action_type.replace('_', ' ')}</span></td>
                    <td>${log.ip_address || 'N/A'}</td>
                </tr>
            `).join('');
        }

        function displayPagination(pagination, elementId, loadFunction) {
            const container = document.getElementById(elementId);
            
            container.innerHTML = `
                <button onclick="${loadFunction.name}(${pagination.current_page - 1})" 
                        ${pagination.current_page === 1 ? 'disabled' : ''}>
                    Previous
                </button>
                <span class="page-info">
                    Page ${pagination.current_page} of ${pagination.total_pages}
                    (${pagination.total_records} total)
                </span>
                <button onclick="${loadFunction.name}(${pagination.current_page + 1})" 
                        ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>
                    Next
                </button>
            `;
        }

        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.add('active');
            document.getElementById('createUserForm').reset();
            document.getElementById('modalAlert').style.display = 'none';
        }

        function closeCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('active');
        }

        // Show/hide fields based on user type
        document.getElementById('newUserType').addEventListener('change', function() {
            const positionGroup = document.getElementById('positionGroup');
            const phoneGroup = document.getElementById('phoneGroup');
            
            positionGroup.style.display = this.value === 'employee' ? 'block' : 'none';
            phoneGroup.style.display = this.value === 'customer' ? 'block' : 'none';
        });

        async function handleCreateUser(event) {
            event.preventDefault();
            
            const modalAlert = document.getElementById('modalAlert');
            const formData = {
                user_type: document.getElementById('newUserType').value,
                username: document.getElementById('newUsername').value,
                email: document.getElementById('newEmail').value,
                full_name: document.getElementById('newFullName').value,
                password: document.getElementById('newPassword').value,
                position: document.getElementById('newPosition').value,
                phone: document.getElementById('newPhone').value
            };
            
            try {
                const response = await fetch('../api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    modalAlert.textContent = 'User created successfully!';
                    modalAlert.className = 'alert alert-success';
                    modalAlert.style.display = 'block';
                    
                    setTimeout(() => {
                        closeCreateUserModal();
                        loadUsers();
                    }, 1500);
                } else {
                    modalAlert.textContent = data.message;
                    modalAlert.className = 'alert alert-error';
                    modalAlert.style.display = 'block';
                }
            } catch (error) {
                console.error('Error creating user:', error);
                modalAlert.textContent = 'An error occurred. Please try again.';
                modalAlert.className = 'alert alert-error';
                modalAlert.style.display = 'block';
            }
        }

        async function handleLogout() {
            try {
                await fetch('../api/logout.php');
                sessionStorage.clear();
                window.location.href = '../login/admin-login.html';
            } catch (error) {
                console.error('Logout error:', error);
                sessionStorage.clear();
                window.location.href = '../login/admin-login.html';
            }
        }

        // Close modal when clicking outside
        document.getElementById('createUserModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeCreateUserModal();
            }
        });
    </script>
</body>
</html>
