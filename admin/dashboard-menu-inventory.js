// Enhanced Admin Dashboard - Menu and Inventory Management
// Add this script to the existing admin dashboard

// API helper function
async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

// ==================== MENU MANAGEMENT ====================

let menuCategories = [];
let menuItems = [];

async function loadMenuCategories() {
    const result = await apiCall('../api/menu.php?action=categories');
    if (result.success) {
        menuCategories = result.data;
        renderMenuCategories();
    }
}

async function loadMenuItems() {
    const result = await apiCall('../api/menu.php?action=items');
    if (result.success) {
        menuItems = result.data;
        renderMenuItems();
    }
}

function renderMenuCategories() {
    const container = document.getElementById('categoriesList');
    if (!container) return;
    
    container.innerHTML = menuCategories.map(cat => `
        <tr>
            <td>${cat.name}</td>
            <td>${cat.description || '-'}</td>
            <td>${cat.display_order}</td>
            <td>
                <span class="badge ${cat.is_active ? 'badge-success' : 'badge-danger'}">
                    ${cat.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="action-buttons">
                <button class="btn btn-primary btn-sm" onclick="editCategory(${cat.id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteCategory(${cat.id})">Delete</button>
            </td>
        </tr>
    `).join('');
}

function renderMenuItems() {
    const container = document.getElementById('menuItemsList');
    if (!container) return;
    
    container.innerHTML = menuItems.map(item => `
        <tr>
            <td><strong>${item.name}</strong></td>
            <td>${item.category_name}</td>
            <td>${item.description || '-'}</td>
            <td>₱${parseFloat(item.price).toFixed(2)}</td>
            <td>
                <span class="badge ${item.is_available ? 'badge-success' : 'badge-danger'}">
                    ${item.is_available ? 'Available' : 'Unavailable'}
                </span>
            </td>
            <td>
                ${item.is_featured ? '<span class="badge badge-warning">⭐ Featured</span>' : ''}
            </td>
            <td class="action-buttons">
                <button class="btn btn-primary btn-sm" onclick="editMenuItem(${item.id})">Edit</button>
                <button class="btn btn-warning btn-sm" onclick="toggleItemAvailability(${item.id})">
                    ${item.is_available ? 'Disable' : 'Enable'}
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteMenuItem(${item.id})">Delete</button>
            </td>
        </tr>
    `).join('');
}

async function addMenuItem() {
    const name = prompt('Enter item name:');
    if (!name) return;
    
    const categoryId = prompt('Enter category ID:');
    const price = prompt('Enter price:');
    const description = prompt('Enter description (optional):');
    
    const result = await apiCall('../api/menu.php?action=item', {
        method: 'POST',
        body: JSON.stringify({
            name,
            category_id: parseInt(categoryId),
            price: parseFloat(price),
            description,
            is_available: true,
            is_featured: false
        })
    });
    
    if (result.success) {
        alert('Menu item added successfully!');
        loadMenuItems();
    } else {
        alert('Error: ' + result.message);
    }
}

async function toggleItemAvailability(id) {
    const result = await apiCall('../api/menu.php?action=toggle-availability', {
        method: 'PUT',
        body: JSON.stringify({ id })
    });
    
    if (result.success) {
        loadMenuItems();
    } else {
        alert('Error: ' + result.message);
    }
}

async function deleteMenuItem(id) {
    if (!confirm('Are you sure you want to delete this menu item?')) return;
    
    const result = await apiCall('../api/menu.php?action=item', {
        method: 'DELETE',
        body: JSON.stringify({ id })
    });
    
    if (result.success) {
        alert('Menu item deleted successfully!');
        loadMenuItems();
    } else {
        alert('Error: ' + result.message);
    }
}

// ==================== INVENTORY MANAGEMENT ====================

let inventoryItems = [];

async function loadInventoryItems() {
    const result = await apiCall('../api/inventory.php?action=items');
    if (result.success) {
        inventoryItems = result.data;
        renderInventoryItems();
        updateInventoryStats();
    }
}

function renderInventoryItems() {
    const container = document.getElementById('inventoryItemsList');
    if (!container) return;
    
    container.innerHTML = inventoryItems.map(item => {
        const stockClass = item.stock_status === 'low' ? 'low' : 
                          item.stock_status === 'medium' ? 'medium' : 'good';
        
        return `
        <tr>
            <td>
                <div class="stock-status">
                    <div class="stock-indicator ${stockClass}"></div>
                    <strong>${item.item_name}</strong>
                </div>
            </td>
            <td>${item.category}</td>
            <td>${item.current_stock} ${item.unit}</td>
            <td>${item.minimum_stock} ${item.unit}</td>
            <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
            <td>${item.supplier || '-'}</td>
            <td>
                <span class="badge ${stockClass === 'low' ? 'badge-danger' : 
                                     stockClass === 'medium' ? 'badge-warning' : 
                                     'badge-success'}">
                    ${stockClass.toUpperCase()}
                </span>
            </td>
            <td class="action-buttons">
                <button class="btn btn-success btn-sm" onclick="recordTransaction(${item.id}, 'restock')">
                    Restock
                </button>
                <button class="btn btn-warning btn-sm" onclick="recordTransaction(${item.id}, 'usage')">
                    Usage
                </button>
                <button class="btn btn-primary btn-sm" onclick="editInventoryItem(${item.id})">
                    Edit
                </button>
            </td>
        </tr>
        `;
    }).join('');
}

async function updateInventoryStats() {
    const result = await apiCall('../api/inventory.php?action=stats');
    if (result.success) {
        const stats = result.data;
        document.getElementById('totalItemsCount').textContent = stats.total_items || 0;
        document.getElementById('lowStockCount').textContent = stats.low_stock_count || 0;
        document.getElementById('totalInventoryValue').textContent = 
            '₱' + parseFloat(stats.total_value || 0).toLocaleString();
    }
}

async function recordTransaction(itemId, type) {
    const quantity = prompt(`Enter quantity to ${type}:`);
    if (!quantity || isNaN(quantity) || quantity <= 0) {
        alert('Please enter a valid quantity');
        return;
    }
    
    const notes = prompt('Enter notes (optional):') || '';
    
    const result = await apiCall('../api/inventory.php?action=transaction', {
        method: 'POST',
        body: JSON.stringify({
            item_id: itemId,
            transaction_type: type,
            quantity: parseFloat(quantity),
            notes
        })
    });
    
    if (result.success) {
        alert(`Transaction recorded! New stock: ${result.new_stock}`);
        loadInventoryItems();
    } else {
        alert('Error: ' + result.message);
    }
}

async function addInventoryItem() {
    const itemName = prompt('Enter item name:');
    if (!itemName) return;
    
    const category = prompt('Enter category:');
    const unit = prompt('Enter unit (kg, liters, pieces, etc):');
    const currentStock = prompt('Enter current stock:');
    const minimumStock = prompt('Enter minimum stock:');
    const unitPrice = prompt('Enter unit price:');
    const supplier = prompt('Enter supplier (optional):');
    
    const result = await apiCall('../api/inventory.php?action=item', {
        method: 'POST',
        body: JSON.stringify({
            item_name: itemName,
            category,
            unit,
            current_stock: parseFloat(currentStock),
            minimum_stock: parseFloat(minimumStock),
            unit_price: parseFloat(unitPrice),
            supplier
        })
    });
    
    if (result.success) {
        alert('Inventory item added successfully!');
        loadInventoryItems();
    } else {
        alert('Error: ' + result.message);
    }
}

async function showLowStockItems() {
    const result = await apiCall('../api/inventory.php?action=low-stock');
    if (result.success) {
        if (result.data.length === 0) {
            alert('No low stock items! Everything is well stocked.');
            return;
        }
        
        const items = result.data.map(item => 
            `${item.item_name}: ${item.current_stock}/${item.minimum_stock} ${item.unit} (Shortage: ${item.shortage})`
        ).join('\n');
        
        alert('LOW STOCK ITEMS:\n\n' + items);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load data when menu tab is active
    if (document.getElementById('tab-menu')) {
        loadMenuCategories();
        loadMenuItems();
    }
    
    // Load data when inventory tab is active
    if (document.getElementById('tab-inventory')) {
        loadInventoryItems();
    }
});

// Export functions for global access
window.menuManagement = {
    loadCategories: loadMenuCategories,
    loadItems: loadMenuItems,
    addItem: addMenuItem,
    toggleAvailability: toggleItemAvailability,
    deleteItem: deleteMenuItem
};

window.inventoryManagement = {
    loadItems: loadInventoryItems,
    addItem: addInventoryItem,
    recordTransaction,
    showLowStock: showLowStockItems
};
