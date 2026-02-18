<?php
session_start();

$isLoggedIn  = isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true;
$customerId  = $isLoggedIn ? (int)$_SESSION['customer_id'] : 0;
$customerName = $isLoggedIn ? htmlspecialchars($_SESSION['customer_name'], ENT_QUOTES) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Café Nowa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #FFCE99 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            padding: 40px 20px;
        }

        .header h1 {
            font-size: 48px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 20px;
            opacity: 0.9;
        }

        .menu-controls {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #562F00;
        }

        .category-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 10px 20px;
            border: 2px solid #562F00;
            background: white;
            color: #562F00;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .category-btn:hover {
            background: #562F00;
            color: white;
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: #562F00;
            color: white;
        }

        .cart-btn {
            background: #00C851;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .cart-btn:hover {
            background: #00a040;
            transform: scale(1.05);
        }

        .cart-count {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .menu-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            cursor: pointer;
        }

        .menu-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .menu-item.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .menu-item.unavailable:hover {
            transform: none;
        }

        .item-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: linear-gradient(135deg, #562F00 0%, #FFCE99 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-info {
            padding: 20px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .item-name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }

        .item-price {
            font-size: 24px;
            font-weight: 700;
            color: #FFCE99;
        }

        .item-category {
            display: inline-block;
            padding: 4px 12px;
            background: #f0f0f0;
            border-radius: 12px;
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }

        .item-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            min-height: 40px;
        }

        .item-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .item-status.available {
            background: #e0f7e0;
            color: #00C851;
        }

        .item-status.unavailable {
            background: #ffe0e0;
            color: #ff4444;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: #FFCE99;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            background: #5568d3;
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
            color: white;
            font-size: 20px;
        }

        .no-items {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            color: #666;
            font-size: 18px;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #333;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            z-index: 9999;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Cart Modal */
        .cart-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .cart-modal.active {
            display: flex;
        }

        .cart-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .cart-header h2 {
            color: #333;
        }

        .close-cart {
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            color: #666;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-item-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .cart-item-price {
            color: #667eea;
            font-weight: 600;
        }

        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .qty-display {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }

        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .cart-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #00C851;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
        }

        .checkout-btn:hover {
            background: #00a040;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 32px;
            }

            .menu-controls {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Café Nowa Menu</h1>
            <p>Fresh coffee, made with love</p>
        </div>

        <div class="menu-controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search menu items...">
            </div>
            <div class="category-filters" id="categoryFilters">
                <button class="category-btn active" data-category="all">All</button>
            </div>
            <button class="cart-btn" id="viewCartBtn">
                <span>🛒</span>
                <span>Cart</span>
                <span class="cart-count" id="cartCount">0</span>
            </button>
        </div>

        <div class="menu-grid" id="menuGrid">
            <div class="loading">Loading menu...</div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-content">
            <div class="cart-header">
                <h2>Your Cart</h2>
                <button class="close-cart" id="closeCart">&times;</button>
            </div>
            <div id="cartItems">
                <div class="empty-cart">Your cart is empty</div>
            </div>
            <div class="cart-footer" id="cartFooter" style="display: none;">
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">&#8369;0.00</span>
                </div>
                <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <!-- PHP injects the customer ID so each account has its own cart key -->
    <script>
        // ─── Per-customer cart key ─────────────────────────────────────────────
        // Cart is stored as  cafeCart_<customerId>  in localStorage.
        // Guest (not logged in) uses  cafeCart_guest  — cleared on login.
        const CUSTOMER_ID = <?php echo $customerId > 0 ? $customerId : '"guest"'; ?>;
        const CART_KEY = 'cafeCart_' + CUSTOMER_ID;

        let menuItems = [];
        let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
        let currentCategory = 'all';

        // ─── Init ──────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            loadMenu();
            updateCartCount();

            document.getElementById('searchInput').addEventListener('input', filterMenu);
            document.getElementById('viewCartBtn').addEventListener('click', openCart);
            document.getElementById('closeCart').addEventListener('click', closeCart);
            document.getElementById('checkoutBtn').addEventListener('click', checkout);
        });

        // ─── Load menu from API ────────────────────────────────────────────────
        async function loadMenu() {
            try {
                const response = await fetch('../api/menu.php');
                const data = await response.json();

                if (data.success && data.data) {
                    menuItems = data.data;
                    populateCategories();
                    renderMenu(menuItems);
                } else {
                    document.getElementById('menuGrid').innerHTML =
                        '<div class="no-items">No menu items available at the moment.</div>';
                }
            } catch (error) {
                console.error('Error loading menu:', error);
                document.getElementById('menuGrid').innerHTML =
                    '<div class="no-items">Error loading menu. Please refresh the page.</div>';
            }
        }

        // ─── Category buttons ──────────────────────────────────────────────────
        function populateCategories() {
            const categories = ['all', ...new Set(menuItems.map(item => item.category_name))];
            document.getElementById('categoryFilters').innerHTML = categories.map(cat => `
                <button class="category-btn ${cat === 'all' ? 'active' : ''}"
                        data-category="${cat}"
                        onclick="filterByCategory('${cat}')">
                    ${cat.charAt(0).toUpperCase() + cat.slice(1)}
                </button>
            `).join('');
        }

        function filterByCategory(category) {
            currentCategory = category;
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.category === category);
            });
            filterMenu();
        }

        function filterMenu() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            let filtered = menuItems;

            if (currentCategory !== 'all') {
                filtered = filtered.filter(item =>
                    item.category_name.toLowerCase() === currentCategory.toLowerCase()
                );
            }
            if (searchTerm) {
                filtered = filtered.filter(item =>
                    item.name.toLowerCase().includes(searchTerm) ||
                    (item.description && item.description.toLowerCase().includes(searchTerm))
                );
            }
            renderMenu(filtered);
        }

        // ─── Render menu grid ──────────────────────────────────────────────────
        function renderMenu(items) {
            const grid = document.getElementById('menuGrid');
            if (items.length === 0) {
                grid.innerHTML = '<div class="no-items">No items found matching your search.</div>';
                return;
            }
            grid.innerHTML = items.map(item => `
                <div class="menu-item ${!item.is_available ? 'unavailable' : ''}">
                    <div class="item-image">
                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : '&#9749;'}
                    </div>
                    <div class="item-info">
                        <div class="item-category">${item.category_name}</div>
                        <div class="item-header">
                            <div class="item-name">${item.name}</div>
                            <div class="item-price">&#8369;${parseFloat(item.price).toFixed(2)}</div>
                        </div>
                        <div class="item-description">${item.description || 'Delicious coffee drink'}</div>
                        <div class="item-status ${item.is_available ? 'available' : 'unavailable'}">
                            ${item.is_available ? '&#10003; Available' : '&#10007; Unavailable'}
                        </div>
                        <button class="add-to-cart-btn"
                                onclick="addToCart(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price})"
                                ${!item.is_available ? 'disabled' : ''}>
                            Add to Cart
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // ─── Cart operations ───────────────────────────────────────────────────
        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    quantity: 1
                });
            }
            saveCart();
            updateCartCount();
            showToast(`${name} added to cart!`);
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
            updateCartCount();
            renderCart();
        }

        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(id);
                } else {
                    saveCart();
                    renderCart();
                }
            }
        }

        // KEY FIX: save to the per-customer key, not a shared one
        function saveCart() {
            localStorage.setItem(CART_KEY, JSON.stringify(cart));
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = count;
        }

        // ─── Cart modal ────────────────────────────────────────────────────────
        function openCart() {
            renderCart();
            document.getElementById('cartModal').classList.add('active');
        }

        function closeCart() {
            document.getElementById('cartModal').classList.remove('active');
        }

        function renderCart() {
            const cartItemsDiv = document.getElementById('cartItems');
            const cartFooter = document.getElementById('cartFooter');

            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<div class="empty-cart">Your cart is empty</div>';
                cartFooter.style.display = 'none';
                return;
            }

            cartFooter.style.display = 'block';
            cartItemsDiv.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <div class="cart-item-price">&#8369;${parseFloat(item.price).toFixed(2)} each</div>
                    </div>
                    <div class="cart-item-actions">
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                        <span class="qty-display">${item.quantity}</span>
                        <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                        <button class="remove-btn" onclick="removeFromCart(${item.id})">Remove</button>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('cartTotal').textContent = '&#8369;' + total.toFixed(2);
        }

        // ─── Toast helper (replaces alert for add-to-cart) ────────────────────
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        }

        // ─── Checkout ──────────────────────────────────────────────────────────
        async function checkout() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            const customerName = prompt('Enter your name:');
            if (!customerName) return;

            const customerPhone = prompt('Enter your phone number:');
            const orderType = confirm('Dine-in? (OK = Dine-in, Cancel = Takeout)') ? 'dine-in' : 'takeout';
            const tableNumber = orderType === 'dine-in' ? prompt('Enter table number:') : null;

            const orderData = {
                customer_name: customerName,
                customer_phone: customerPhone,
                order_type: orderType,
                table_number: tableNumber,
                items: cart.map(item => ({
                    menu_item_id: item.id,
                    name: item.name,
                    unit_price: item.price,
                    quantity: item.quantity
                }))
            };

            try {
                const response = await fetch('../api/orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                const data = await response.json();

                if (data.success) {
                    alert(`Order placed successfully! Order #${data.order_number}`);
                    cart = [];
                    saveCart();
                    updateCartCount();
                    closeCart();
                } else {
                    alert('Error placing order: ' + data.message);
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('Error placing order. Please try again.');
            }
        }
    </script>
</body>

</html>