document.addEventListener('DOMContentLoaded', () => {
    // 1. Security & Auth Check
    const isLoggedIn = sessionStorage.getItem('isLoggedIn');
    const userRole = sessionStorage.getItem('userType'); // Changed from 'userRole' to match login
    const userFullName = sessionStorage.getItem('fullName');

    // Strict check: Must be logged in AND be an employee
    if (!isLoggedIn || !['employee', 'admin', 'super_admin'].includes(userRole)) {
        alert('Access denied. Employee privileges required.');
        window.location.href = '../login/login.html';
        return;
    }

    // Display user name
    const userNameElement = document.getElementById('userName');
    if (userNameElement) userNameElement.textContent = userFullName || 'Staff';

    // 2. Initialize App
    init();
});

// Global variables
let ordersData = [];
let refreshInterval = null;

function init() {
    // Load initial data
    loadOrders();
    loadMenu();
    loadProfile();

    // Auto-refresh orders every 10 seconds
    refreshInterval = setInterval(loadOrders, 10000);

    // Setup Event Listeners
    setupNavigation();
    setupGlobalClicks(); // Uses Event Delegation for dynamic buttons
}

// --- API FUNCTIONS ---

async function loadOrders() {
    try {
        // Fetch from the PHP API we fixed earlier
        const response = await fetch('../api/orders.php?action=list');
        const data = await response.json();

        if (data.success) {
            ordersData = data.data;
            updateStats();
            renderOrders();
        } else {
            console.error('API Error:', data.message);
        }
    } catch (error) {
        console.error('Connection error loading orders:', error);
    }
}

async function updateOrderStatus(orderId, newStatus, cardElement) {
    try {
        const response = await fetch('../api/orders.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus
            })
        });

        const data = await response.json();

        if (data.success) {
            if (newStatus === 'completed' || newStatus === 'cancelled') {
                // Animate removal
                if (cardElement) {
                    cardElement.style.animation = 'fadeOut 0.5s ease';
                    setTimeout(() => loadOrders(), 500); // Reload after animation
                } else {
                    loadOrders();
                }
                showNotification(`Order ${newStatus} successfully!`);
            } else {
                // Just refresh to show new buttons/status
                loadOrders();
                showNotification(`Order status updated to ${newStatus}`);
            }
        } else {
            showNotification(`Error: ${data.message}`, 'error');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showNotification('Connection error', 'error');
    }
}

async function loadMenu() {
    try {
        const response = await fetch('../api/menu.php?action=items');
        const data = await response.json();
        if (data.success) renderMenu(data.data);
    } catch (error) {
        console.error('Error loading menu:', error);
    }
}

// --- RENDERING FUNCTIONS ---

function renderOrders() {
    const grid = document.getElementById('ordersGrid');
    const currentFilter = document.querySelector('.filter-btn.active')?.dataset.status || 'all';
    
    // Filter logic
    let filtered = ordersData.filter(o => {
        if (currentFilter === 'all') return !['completed', 'cancelled'].includes(o.status);
        return o.status === currentFilter;
    });

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="no-orders">No active orders found.</div>';
        return;
    }

    grid.innerHTML = filtered.map(order => `
        <div class="order-card ${order.status}" data-id="${order.id}">
            <div class="order-header">
                <span class="order-id">#${order.order_number}</span>
                <span class="order-status status-${order.status}">${order.status.toUpperCase()}</span>
            </div>
            <div class="order-details">
                <h3>${order.customer_name}</h3>
                <p class="time">${formatTime(order.created_at)}</p>
                ${order.table_number ? `<p class="table">Table: ${order.table_number}</p>` : ''}
            </div>
            <div class="order-items">
                ${renderOrderItems(order.items)}
            </div>
            <div class="order-total">
                Total: ₱${parseFloat(order.total_amount).toFixed(2)}
            </div>
            <div class="order-actions">
                ${getActionButtons(order)}
            </div>
        </div>
    `).join('');
}

function renderOrderItems(items) {
    if (!items || items.length === 0) return '<p>No items</p>';
    return items.map(item => `
        <div class="item-row">
            <span>${item.quantity}x ${item.item_name}</span>
        </div>
        ${item.special_instructions ? `<div class="item-note">Note: ${item.special_instructions}</div>` : ''}
    `).join('');
}

function getActionButtons(order) {
    // Generates buttons based on the current status
    switch(order.status) {
        case 'pending':
            return `<button class="btn-action btn-accept" data-action="confirmed">Accept Order</button>
                    <button class="btn-action btn-reject" data-action="cancelled">Reject</button>`;
        case 'confirmed':
            return `<button class="btn-action btn-start" data-action="preparing">Start Preparing</button>`;
        case 'preparing':
            return `<button class="btn-action btn-ready" data-action="ready">Mark Ready</button>`;
        case 'ready':
            return `<button class="btn-action btn-complete" data-action="completed">Complete Order</button>`;
        default:
            return '';
    }
}

function renderMenu(items) {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;

    if (items.length === 0) {
        grid.innerHTML = '<div class="no-items">No menu items.</div>';
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="menu-card ${item.is_available == 0 ? 'unavailable' : ''}">
            <h3>${item.name}</h3>
            <p>${item.description || ''}</p>
            <div class="price">₱${parseFloat(item.price).toFixed(2)}</div>
            <div class="status-badge ${item.is_available == 1 ? 'available' : 'unavailable'}">
                ${item.is_available == 1 ? 'Available' : 'Unavailable'}
            </div>
        </div>
    `).join('');
}

function loadProfile() {
    const nameEl = document.getElementById('profileName');
    const emailEl = document.getElementById('profileEmail');
    if(nameEl) nameEl.textContent = sessionStorage.getItem('fullName');
    if(emailEl) emailEl.textContent = sessionStorage.getItem('email');
}

function updateStats() {
    const pending = ordersData.filter(o => o.status === 'pending').length;
    const progress = ordersData.filter(o => ['confirmed', 'preparing', 'ready'].includes(o.status)).length;
    // Note: completed count resets on page load unless we fetch "completed today" specifically from API
    // For now, we calculate based on loaded data or leave as 0
    document.getElementById('pendingCount').textContent = pending;
    document.getElementById('progressCount').textContent = progress;
}

// --- INTERACTION & EVENT DELEGATION ---

function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');
    const pageTitle = document.getElementById('pageTitle');
    const sidebar = document.querySelector('.sidebar');

    // Tab switching
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');
            
            sections.forEach(section => section.classList.remove('active'));
            const sectionId = item.getAttribute('data-section');
            document.getElementById(sectionId).classList.add('active');
            
            if(pageTitle) pageTitle.textContent = item.textContent.trim();

            // Mobile: close sidebar on selection
            if(window.innerWidth <= 768) {
                sidebar.classList.remove('active');
            }
        });
    });

    // Mobile Toggle
    document.getElementById('menuToggle').addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    // Filter Buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            renderOrders();
        });
    });
}

function setupGlobalClicks() {
    // We use "Event Delegation" here. Instead of adding listeners to buttons that don't exist yet,
    // we listen to the document and check what was clicked.
    document.addEventListener('click', async (e) => {
        
        // 1. Handle Order Actions (Accept, Start, Complete, etc.)
        if (e.target.matches('.btn-action')) {
            const btn = e.target;
            const action = btn.dataset.action; // e.g., 'confirmed', 'preparing'
            const card = btn.closest('.order-card');
            const orderId = card.dataset.id;

            if (action === 'cancelled') {
                if (!confirm('Are you sure you want to reject this order?')) return;
            } else if (action === 'completed') {
                if (!confirm('Finish this order?')) return;
            }

            // Call API
            await updateOrderStatus(orderId, action, card);
        }

        // 2. Handle Logout
        if (e.target.id === 'logoutBtn' || e.target.closest('#logoutBtn')) {
            if (confirm('Are you sure you want to logout?')) {
                clearInterval(refreshInterval);
                try {
                    await fetch('../api/logout.php', { method: 'POST' });
                } catch (err) { console.error(err); }
                sessionStorage.clear();
                window.location.href = '../login/login.html';
            }
        }
    });
}

// --- UTILITIES ---

function formatTime(sqlDate) {
    const date = new Date(sqlDate);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} mins ago`;
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.textContent = message;
    const bgColor = type === 'error' ? '#f44336' : '#4caf50'; // Red for error, Green for success
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${bgColor};
        color: white;
        padding: 1rem 2rem;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-family: 'Segoe UI', sans-serif;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animations programmatically
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(120%); opacity: 0; }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: scale(1); }
        to { opacity: 0; transform: scale(0.9); }
    }
    /* Button Styles for actions */
    .btn-action { padding: 8px 16px; border: none; border-radius: 4px; color: white; cursor: pointer; margin-right: 5px; font-weight: 600; }
    .btn-accept { background: #4caf50; }
    .btn-reject { background: #f44336; }
    .btn-start { background: #2196F3; }
    .btn-ready { background: #FF9800; }
    .btn-complete { background: #4caf50; }
    .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }
`;
document.head.appendChild(style);