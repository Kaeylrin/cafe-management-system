<?php
// 1. FORCE ERROR REPORTING (To catch hidden errors)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
// Verify config exists
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
}

header('Content-Type: application/json');

// 2. SAFETY HELPERS (Prevents crash if config is missing)
if (!function_exists('sendJSON')) {
    function sendJSON($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) { return htmlspecialchars(strip_tags(trim($data))); }
}
// Placeholder logAudit to prevent crash
if (!function_exists('logAudit')) {
    function logAudit($a, $b, $c, $d=null, $e=null, $f=null, $g=null) { return true; }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Database connection fallback
    if (function_exists('getDB')) {
        $db = getDB();
    } else {
        sendJSON(['success' => false, 'message' => 'Database connection function missing'], 500);
    }
    
    switch ($method) {
        case 'POST': 
            if (isset($_GET['action']) && $_GET['action'] === 'update_status') {
                handleUpdateOrderStatus($db);
            } else {
                handleCreateOrder($db);
            }
            break;
            
        case 'GET': 
            handleListOrders($db);
            break;
            
        case 'PUT': 
            handleAssignOrder($db);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Orders error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

// --- FUNCTIONS ---

function handleCreateOrder($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['customer_name']) || !isset($input['items']) || empty($input['items'])) {
        sendJSON(['success' => false, 'message' => 'Customer name and items are required'], 400);
    }
    
    $customerName = sanitizeInput($input['customer_name']);
    $orderType = $input['order_type'] ?? 'dine-in';
    $tableNumber = $input['table_number'] ?? null;
    $items = $input['items'];
    
    $db->beginTransaction();
    
    try {
        $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += $item['unit_price'] * $item['quantity'];
        }
        
        $stmt = $db->prepare("INSERT INTO orders (order_number, customer_name, total_amount, status, order_type, table_number, created_at) VALUES (?, ?, ?, 'pending', ?, ?, NOW())");
        $stmt->execute([$orderNumber, $customerName, $totalAmount, $orderType, $tableNumber]);
        $orderId = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $subtotal = $item['unit_price'] * $item['quantity'];
            $stmt->execute([$orderId, $item['menu_item_id'] ?? null, $item['name'], $item['unit_price'], $item['quantity'], $subtotal]);
        }
        
        $db->commit();
        sendJSON(['success' => true, 'message' => 'Order created', 'order_id' => $orderId]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function handleListOrders($db) {
    $status = $_GET['status'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // SAFE QUERY: Removed JOIN to 'users' table to prevent crashes
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    
    if ($status && $status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT $limit";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch items manually
    foreach ($orders as &$order) {
        $stmtItems = $db->prepare("SELECT quantity, item_name FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order['id']]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        foreach ($items as $i) $summary[] = $i['quantity'] . "x " . $i['item_name'];
        $order['items_summary'] = implode(", ", $summary);
        $order['items'] = $items;
    }
    
    sendJSON(['success' => true, 'data' => $orders]);
}

function handleUpdateOrderStatus($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$orderId || !$status) sendJSON(['success' => false, 'message' => 'Missing data'], 400);
    
    $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    sendJSON(['success' => true]);
}

function handleAssignOrder($db) {
    // Placeholder to prevent crash
    sendJSON(['success' => true]);
}
?>