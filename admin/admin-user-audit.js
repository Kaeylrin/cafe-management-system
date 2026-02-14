/**
 * USER MANAGEMENT AND AUDIT TRAIL MODULE
 * For Super Admin Dashboard
 * 
 * This module provides:
 * - User management (create, read, update, delete)
 * - Read-only audit trail viewing
 * - Export functionality
 */

// ============================================================================
// USER MANAGEMENT
// ============================================================================

let allUsers = [];
let filteredUsers = [];

async function loadUsers() {
    try {
        const userType = document.getElementById('userTypeFilter').value;
        const url = userType === 'all' ? '../api/users.php' : `../api/users.php?user_type=${userType}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allUsers = data.data.users;
            filteredUsers = allUsers;
            renderUsersTable();
        } else {
            console.error('Failed to load users:', data.message);
            document.getElementById('usersTableBody').innerHTML = 
                `<tr><td colspan="7" class="error">${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading users:', error);
        document.getElementById('usersTableBody').innerHTML = 
            '<tr><td colspan="7" class="error">Error loading users. Please try again.</td></tr>';
    }
}

function renderUsersTable() {
    const tbody = document.getElementById('usersTableBody');
    
    if (filteredUsers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 40px; color: #999;">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredUsers.map(user => `
        <tr>
            <td><strong>${user.username}</strong></td>
            <td>${user.email}</td>
            <td>${user.full_name}</td>
            <td><span class="badge" style="background: #e0e0e0; color: #333;">${user.user_type.toUpperCase().replace('_', ' ')}</span></td>
            <td>
                <span class="badge ${user.is_active ? 'badge-available' : 'badge-unavailable'}">
                    ${user.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</td>
            <td class="action-buttons">
                <button class="btn-primary btn-sm" onclick="openEditUserModal(${user.id}, '${user.user_type}')">Edit</button>
                <button class="btn-danger btn-sm" onclick="deleteUser(${user.id}, '${user.user_type}', '${user.username}')">Delete</button>
            </td>
        </tr>
    `).join('');
}

function filterUsers() {
    const userType = document.getElementById('userTypeFilter').value;
    
    if (userType === 'all') {
        filteredUsers = allUsers;
    } else {
        filteredUsers = allUsers.filter(user => user.user_type === userType);
    }
    
    searchUsers(); // Apply search filter too
}

function searchUsers() {
    const searchTerm = document.getElementById('userSearchInput').value.toLowerCase();
    
    if (!searchTerm) {
        filterUsers(); // Just apply type filter
        return;
    }
    
    const userType = document.getElementById('userTypeFilter').value;
    let baseUsers = userType === 'all' ? allUsers : allUsers.filter(u => u.user_type === userType);
    
    filteredUsers = baseUsers.filter(user =>
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm) ||
        user.full_name.toLowerCase().includes(searchTerm)
    );
    
    renderUsersTable();
}

// Open Add User Modal
function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
    document.getElementById('addUserForm').reset();
    document.getElementById('addUserAlert').innerHTML = '';
    toggleUserTypeFields();
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
}

function toggleUserTypeFields() {
    const userType = document.getElementById('newUserType').value;
    const positionGroup = document.getElementById('positionGroup');
    const phoneGroup = document.getElementById('phoneGroup');
    
    if (userType === 'employee') {
        positionGroup.style.display = 'block';
        phoneGroup.style.display = 'none';
    } else if (userType === 'customer') {
        positionGroup.style.display = 'none';
        phoneGroup.style.display = 'block';
    } else {
        positionGroup.style.display = 'none';
        phoneGroup.style.display = 'none';
    }
}

async function handleAddUser(event) {
    event.preventDefault();
    
    const password = document.getElementById('newPassword').value;
    const passwordConfirm = document.getElementById('newPasswordConfirm').value;
    
    if (password !== passwordConfirm) {
        document.getElementById('addUserAlert').innerHTML = 
            '<div class="alert alert-error">Passwords do not match!</div>';
        return;
    }
    
    const userData = {
        user_type: document.getElementById('newUserType').value,
        username: document.getElementById('newUsername').value,
        email: document.getElementById('newEmail').value,
        full_name: document.getElementById('newFullName').value,
        password: password
    };
    
    if (userData.user_type === 'employee') {
        userData.position = document.getElementById('newPosition').value || 'Barista';
    } else if (userData.user_type === 'customer') {
        userData.phone = document.getElementById('newPhone').value;
    }
    
    try {
        const response = await fetch('../api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(userData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('addUserAlert').innerHTML = 
                '<div class="alert alert-success">User created successfully!</div>';
            setTimeout(() => {
                closeAddUserModal();
                loadUsers();
            }, 1500);
        } else {
            document.getElementById('addUserAlert').innerHTML = 
                `<div class="alert alert-error">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error creating user:', error);
        document.getElementById('addUserAlert').innerHTML = 
            '<div class="alert alert-error">Error creating user. Please try again.</div>';
    }
}

// Open Edit User Modal
async function openEditUserModal(userId, userType) {
    try {
        // Find user in current list
        const user = allUsers.find(u => u.id === userId && u.user_type === userType);
        
        if (!user) {
            alert('User not found');
            return;
        }
        
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserType').value = userType;
        document.getElementById('editFullName').value = user.full_name;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editIsActive').value = user.is_active ? '1' : '0';
        document.getElementById('editPassword').value = '';
        document.getElementById('editUserAlert').innerHTML = '';
        
        document.getElementById('editUserModal').classList.add('active');
    } catch (error) {
        console.error('Error opening edit modal:', error);
        alert('Error loading user data');
    }
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

async function handleEditUser(event) {
    event.preventDefault();
    
    const userId = parseInt(document.getElementById('editUserId').value);
    const userType = document.getElementById('editUserType').value;
    
    const updateData = {
        user_id: userId,
        user_type: userType,
        full_name: document.getElementById('editFullName').value,
        email: document.getElementById('editEmail').value,
        is_active: document.getElementById('editIsActive').value === '1'
    };
    
    const newPassword = document.getElementById('editPassword').value;
    if (newPassword) {
        updateData.password = newPassword;
    }
    
    try {
        const response = await fetch('../api/users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updateData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('editUserAlert').innerHTML = 
                '<div class="alert alert-success">User updated successfully!</div>';
            setTimeout(() => {
                closeEditUserModal();
                loadUsers();
            }, 1500);
        } else {
            document.getElementById('editUserAlert').innerHTML = 
                `<div class="alert alert-error">${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error updating user:', error);
        document.getElementById('editUserAlert').innerHTML = 
            '<div class="alert alert-error">Error updating user. Please try again.</div>';
    }
}

async function deleteUser(userId, userType, username) {
    if (!confirm(`Are you sure you want to deactivate user "${username}"?\n\nThis will set the user to inactive but keep the account in the database.`)) {
        return;
    }
    
    try {
        const response = await fetch('../api/users.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                user_type: userType,
                permanent: false  // Soft delete
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('User deactivated successfully');
            loadUsers();
        } else {
            alert(`Error: ${data.message}`);
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('Error deactivating user. Please try again.');
    }
}

// ============================================================================
// AUDIT TRAIL
// ============================================================================

let auditLogs = [];
let auditCurrentPage = 1;
let auditTotalPages = 1;

async function loadAuditLog(page = 1) {
    try {
        const userType = document.getElementById('auditUserTypeFilter').value;
        const actionType = document.getElementById('auditActionTypeFilter').value;
        const dateFrom = document.getElementById('auditDateFrom').value;
        const dateTo = document.getElementById('auditDateTo').value;
        const search = document.getElementById('auditSearchInput').value;
        
        let url = `../api/audit-trail.php?page=${page}&limit=50`;
        if (userType) url += `&user_type=${userType}`;
        if (actionType) url += `&action_type=${actionType}`;
        if (dateFrom) url += `&date_from=${dateFrom}`;
        if (dateTo) url += `&date_to=${dateTo}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            auditLogs = data.data.logs;
            auditCurrentPage = data.data.pagination.current_page;
            auditTotalPages = data.data.pagination.total_pages;
            renderAuditTable();
            renderAuditPagination();
        } else {
            console.error('Failed to load audit logs:', data.message);
            document.getElementById('auditTableBody').innerHTML = 
                `<tr><td colspan="8" class="error">${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading audit logs:', error);
        document.getElementById('auditTableBody').innerHTML = 
            '<tr><td colspan="8" class="error">Error loading audit logs. Please try again.</td></tr>';
    }
}

function renderAuditTable() {
    const tbody = document.getElementById('auditTableBody');
    
    if (auditLogs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 40px; color: #999;">No audit logs found</td></tr>';
        return;
    }
    
    tbody.innerHTML = auditLogs.map(log => {
        const actionColor = {
            'login': '#2196F3',
            'create': '#4CAF50',
            'update': '#FF9800',
            'delete': '#f44336',
            'view': '#9C27B0'
        }[log.action_type] || '#666';
        
        return `
            <tr>
                <td>${new Date(log.timestamp).toLocaleString()}</td>
                <td><span class="badge" style="background: #e0e0e0; color: #333;">${log.user_type || 'N/A'}</span></td>
                <td>${log.username || 'System'}</td>
                <td>${log.action}</td>
                <td><span class="badge" style="background: ${actionColor}; color: white;">${log.action_type}</span></td>
                <td>${log.target_table || '-'} ${log.target_id ? '#' + log.target_id : ''}</td>
                <td><code style="font-size: 11px;">${log.ip_address || '-'}</code></td>
                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" 
                    title="${log.details || '-'}">${(log.details || '-').substring(0, 50)}${log.details && log.details.length > 50 ? '...' : ''}</td>
            </tr>
        `;
    }).join('');
}

function renderAuditPagination() {
    const container = document.getElementById('auditPagination');
    
    if (auditTotalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    if (auditCurrentPage > 1) {
        html += `<button class="btn-secondary" onclick="loadAuditLog(${auditCurrentPage - 1})">← Previous</button>`;
    }
    
    html += `<span style="padding: 10px 20px; background: #f0f0f0; border-radius: 6px;">
        Page ${auditCurrentPage} of ${auditTotalPages}
    </span>`;
    
    if (auditCurrentPage < auditTotalPages) {
        html += `<button class="btn-secondary" onclick="loadAuditLog(${auditCurrentPage + 1})">Next →</button>`;
    }
    
    container.innerHTML = html;
}

function filterAuditLog() {
    auditCurrentPage = 1;
    loadAuditLog(1);
}

function searchAuditLog() {
    // Debounce search
    clearTimeout(window.auditSearchTimeout);
    window.auditSearchTimeout = setTimeout(() => {
        auditCurrentPage = 1;
        loadAuditLog(1);
    }, 500);
}

function refreshAuditLog() {
    loadAuditLog(auditCurrentPage);
}

function exportAuditLog() {
    // Get current filters
    const userType = document.getElementById('auditUserTypeFilter').value;
    const actionType = document.getElementById('auditActionTypeFilter').value;
    const dateFrom = document.getElementById('auditDateFrom').value;
    const dateTo = document.getElementById('auditDateTo').value;
    const search = document.getElementById('auditSearchInput').value;
    
    // Build CSV
    let csv = 'Timestamp,User Type,Username,Action,Action Type,Target Table,Target ID,IP Address,User Agent,Details\n';
    
    auditLogs.forEach(log => {
        csv += [
            new Date(log.timestamp).toLocaleString(),
            log.user_type || '',
            log.username || '',
            log.action.replace(/,/g, ';'),
            log.action_type || '',
            log.target_table || '',
            log.target_id || '',
            log.ip_address || '',
            (log.user_agent || '').replace(/,/g, ';'),
            (log.details || '').replace(/,/g, ';').replace(/\n/g, ' ')
        ].map(field => `"${field}"`).join(',') + '\n';
    });
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `audit-log-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// ============================================================================
// INITIALIZE
// ============================================================================

console.log('✅ User Management and Audit Trail module loaded');
