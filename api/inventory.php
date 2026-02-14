<?php
/**
 * Inventory Management API
 * Handles CRUD operations for inventory items and transactions
 */

session_start();
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require admin authentication
requireRole(['super_admin', 'admin']);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        case 'PUT':
            handlePut($db, $action);
            break;
        case 'DELETE':
            handleDelete($db, $action);
            break;
        default:
            sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    error_log("Inventory API error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Database error occurred'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'items':
            // Get all inventory items
            $stmt = $db->query("
                SELECT 
                    id, item_name, category, unit, 
                    current_stock, minimum_stock, maximum_stock, unit_price,
                    supplier, last_restocked, is_active, created_at, updated_at,
                    CASE 
                        WHEN current_stock < minimum_stock THEN 'low'
                        WHEN current_stock <= minimum_stock * 1.2 THEN 'medium'
                        ELSE 'good'
                    END as stock_status
                FROM inventory_items
                WHERE is_active = TRUE
                ORDER BY 
                    CASE 
                        WHEN current_stock < minimum_stock THEN 1
                        WHEN current_stock <= minimum_stock * 1.2 THEN 2
                        ELSE 3
                    END,
                    category, item_name
            ");
            $items = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $items]);
            break;
            
        case 'low-stock':
            // Get low stock items
            $stmt = $db->query("
                SELECT 
                    id, item_name, category, unit, 
                    current_stock, minimum_stock,
                    (minimum_stock - current_stock) as shortage,
                    supplier, last_restocked
                FROM inventory_items
                WHERE current_stock < minimum_stock
                AND is_active = TRUE
                ORDER BY shortage DESC
            ");
            $items = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $items]);
            break;
            
        case 'item':
            // Get single item
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'Item ID required'], 400);
            }
            
            $stmt = $db->prepare("
                SELECT * FROM inventory_items WHERE id = ?
            ");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            if ($item) {
                sendJSON(['success' => true, 'data' => $item]);
            } else {
                sendJSON(['success' => false, 'message' => 'Item not found'], 404);
            }
            break;
            
        case 'transactions':
            // Get transaction history
            $itemId = $_GET['item_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            $sql = "
                SELECT 
                    it.*, ii.item_name, ii.unit
                FROM inventory_transactions it
                JOIN inventory_items ii ON it.item_id = ii.id
            ";
            
            if ($itemId) {
                $sql .= " WHERE it.item_id = ?";
                $stmt = $db->prepare($sql . " ORDER BY it.transaction_date DESC LIMIT " . (int)$limit);
                $stmt->execute([$itemId]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY it.transaction_date DESC LIMIT " . (int)$limit);
                $stmt->execute();
            }
            
            $transactions = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $transactions]);
            break;
            
        case 'categories':
            // Get unique categories
            $stmt = $db->query("
                SELECT DISTINCT category
                FROM inventory_items
                WHERE is_active = TRUE
                ORDER BY category
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendJSON(['success' => true, 'data' => $categories]);
            break;
            
        case 'stats':
            // Get inventory statistics
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN current_stock < minimum_stock THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(current_stock * unit_price) as total_value,
                    COUNT(DISTINCT category) as categories_count
                FROM inventory_items
                WHERE is_active = TRUE
            ");
            $stats = $stmt->fetch();
            sendJSON(['success' => true, 'data' => $stats]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePost($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'item':
            // Create new inventory item
            $itemName = $input['item_name'] ?? '';
            $category = $input['category'] ?? '';
            $unit = $input['unit'] ?? '';
            $currentStock = $input['current_stock'] ?? 0;
            $minimumStock = $input['minimum_stock'] ?? 0;
            $maximumStock = $input['maximum_stock'] ?? null;
            $unitPrice = $input['unit_price'] ?? 0;
            $supplier = $input['supplier'] ?? '';
            
            if (empty($itemName) || empty($category) || empty($unit)) {
                sendJSON(['success' => false, 'message' => 'Item name, category, and unit are required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO inventory_items 
                (item_name, category, unit, current_stock, minimum_stock, maximum_stock, unit_price, supplier)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $itemName, $category, $unit, $currentStock, 
                $minimumStock, $maximumStock, $unitPrice, $supplier
            ]);
            
            $itemId = $db->lastInsertId();
            
            // Log initial stock if any
            if ($currentStock > 0) {
                $stmt = $db->prepare("
                    INSERT INTO inventory_transactions 
                    (item_id, transaction_type, quantity, previous_stock, new_stock, notes, performed_by, user_type)
                    VALUES (?, 'restock', ?, 0, ?, 'Initial stock', ?, ?)
                ");
                $stmt->execute([
                    $itemId, $currentStock, $currentStock,
                    $_SESSION['user_id'], $_SESSION['user_type']
                ]);
            }
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Created inventory item: $itemName", 'create', 'inventory_items', $itemId);
            
            sendJSON(['success' => true, 'message' => 'Inventory item created', 'id' => $itemId]);
            break;
            
        case 'transaction':
            // Record inventory transaction (restock, usage, etc.)
            $itemId = $input['item_id'] ?? null;
            $transactionType = $input['transaction_type'] ?? '';
            $quantity = $input['quantity'] ?? 0;
            $notes = $input['notes'] ?? '';
            
            if (!$itemId || !$transactionType || $quantity <= 0) {
                sendJSON(['success' => false, 'message' => 'Item ID, transaction type, and valid quantity are required'], 400);
            }
            
            // Validate transaction type
            $validTypes = ['restock', 'usage', 'adjustment', 'waste'];
            if (!in_array($transactionType, $validTypes)) {
                sendJSON(['success' => false, 'message' => 'Invalid transaction type'], 400);
            }
            
            // Get current stock
            $stmt = $db->prepare("SELECT current_stock, item_name FROM inventory_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                sendJSON(['success' => false, 'message' => 'Item not found'], 404);
            }
            
            $previousStock = $item['current_stock'];
            
            // Calculate new stock
            if (in_array($transactionType, ['restock', 'adjustment'])) {
                $newStock = $previousStock + $quantity;
            } else { // usage or waste
                $newStock = max(0, $previousStock - $quantity);
            }
            
            // Update inventory
            $stmt = $db->prepare("
                UPDATE inventory_items
                SET current_stock = ?,
                    last_restocked = IF(? = 'restock', NOW(), last_restocked),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$newStock, $transactionType, $itemId]);
            
            // Log transaction
            $stmt = $db->prepare("
                INSERT INTO inventory_transactions 
                (item_id, transaction_type, quantity, previous_stock, new_stock, notes, performed_by, user_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $itemId, $transactionType, $quantity, $previousStock, 
                $newStock, $notes, $_SESSION['user_id'], $_SESSION['user_type']
            ]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Recorded $transactionType transaction for: " . $item['item_name'], 'update', 'inventory_items', $itemId);
            
            sendJSON([
                'success' => true, 
                'message' => 'Transaction recorded',
                'new_stock' => $newStock
            ]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'item':
            // Update inventory item
            $id = $input['id'] ?? null;
            $itemName = $input['item_name'] ?? '';
            $category = $input['category'] ?? '';
            $unit = $input['unit'] ?? '';
            $minimumStock = $input['minimum_stock'] ?? 0;
            $maximumStock = $input['maximum_stock'] ?? null;
            $unitPrice = $input['unit_price'] ?? 0;
            $supplier = $input['supplier'] ?? '';
            $isActive = $input['is_active'] ?? true;
            
            if (!$id || empty($itemName) || empty($category) || empty($unit)) {
                sendJSON(['success' => false, 'message' => 'ID, item name, category, and unit are required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE inventory_items
                SET item_name = ?, category = ?, unit = ?,
                    minimum_stock = ?, maximum_stock = ?, unit_price = ?,
                    supplier = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $itemName, $category, $unit, $minimumStock, 
                $maximumStock, $unitPrice, $supplier, $isActive ? 1 : 0, $id
            ]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Updated inventory item: $itemName", 'update', 'inventory_items', $id);
            
            sendJSON(['success' => true, 'message' => 'Inventory item updated']);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handleDelete($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'item':
            // Soft delete inventory item (set is_active to false)
            $id = $input['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'ID required'], 400);
            }
            
            // Get item name for audit
            $stmt = $db->prepare("SELECT item_name FROM inventory_items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            $stmt = $db->prepare("UPDATE inventory_items SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$id]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Deleted inventory item: " . ($item['item_name'] ?? 'Unknown'), 'delete', 'inventory_items', $id);
            
            sendJSON(['success' => true, 'message' => 'Inventory item deleted']);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}
?>
