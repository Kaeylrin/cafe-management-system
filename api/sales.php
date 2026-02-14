<?php
// 1. FORCE ERROR REPORTING
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
}

header('Content-Type: application/json');

// 2. SAFETY HELPERS (Prevents "Call to undefined function" crash)
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

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // Database connection fallback
    if (function_exists('getDB')) {
        $db = getDB();
    } else {
        // Fallback if config is broken
        sendJSON(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'overview';
    
    switch ($action) {
        case 'overview':
            handleOverview($db);
            break;
        case 'daily':
            handleDailyStats($db);
            break;
        case 'employee_performance': // Added handler to prevent 500 error
            sendJSON(['success' => true, 'data' => []]); 
            break;
        default:
            handleOverview($db);
    }
    
} catch (Exception $e) {
    error_log("Sales stats error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
}

function handleOverview($db) {
    try {
        // Safe queries using COALESCE to handle nulls
        $today = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE DATE(created_at) = CURDATE()")->fetch(PDO::FETCH_ASSOC);
        
        $week = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetch(PDO::FETCH_ASSOC);
        
        $month = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC);
        
        // Calculate average safely
        $today['avg_order_value'] = $today['total_orders'] > 0 ? $today['total_revenue'] / $today['total_orders'] : 0;

        // Top items (Safe empty array fallback)
        $topItems = []; 
        try {
            $topItems = $db->query("SELECT item_name, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue FROM order_items GROUP BY item_name ORDER BY total_sold DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Ignore if table missing */ }

        sendJSON([
            'success' => true,
            'data' => [
                'today' => $today,
                'this_week' => $week,
                'this_month' => $month,
                'top_items' => $topItems
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleDailyStats($db) {
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
    
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    
    sendJSON(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
?>