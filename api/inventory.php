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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// For GET requests (read operations), allow employees, admin, and super_admin
// For POST/PUT/DELETE (write operations), require admin or super_admin
if ($method === 'GET') {
    requireRole(['employee', 'admin', 'super_admin']);
} else {
    requireRole(['super_admin', 'admin']);
}

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
    // Default action is 'items' if no action specified
    if (empty($action)) {
        $action = 'items';
    }
    
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
            
        case 'categories':
            // Get distinct categories
            $stmt = $db->query("
                SELECT DISTINCT category 
                FROM inventory_items 
                WHERE is_active = TRUE
                ORDER BY category
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            sendJSON(['success' => true, 'data' => $categories]);
            break;
            
        case 'transactions':
            // Get inventory transactions
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;
            
            $sql = "
                SELECT 
                    it.id, it.item_id, it.transaction_type, 
                    it.quantity, it.unit_price, it.total_amount,
                    it.notes, it.created_at, it.created_by,
                    ii.item_name, ii.unit,
                    u.full_name as created_by_name
                FROM inventory_transactions it
                JOIN inventory_items ii ON it.item_id = ii.id
                LEFT JOIN users u ON it.created_by = u.id
            ";
            
            if ($itemId) {
                $sql .= " WHERE it.item_id = ?";
                $stmt = $db->prepare($sql . " ORDER BY it.created_at DESC LIMIT " . $limit);
                $stmt->execute([$itemId]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY it.created_at DESC LIMIT " . $limit);
                $stmt->execute();
            }
            
            $transactions = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $transactions]);
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
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePost($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'item':
            // Create new inventory item
            if (!isset($input['item_name']) || !isset($input['category']) || !isset($input['unit'])) {
                sendJSON(['success' => false, 'message' => 'Item name, category, and unit are required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO inventory_items 
                (item_name, category, unit, current_stock, minimum_stock, maximum_stock, 
                 unit_price, supplier, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                sanitizeInput($input['item_name']),
                sanitizeInput($input['category']),
                sanitizeInput($input['unit']),
                $input['current_stock'] ?? 0,
                $input['minimum_stock'] ?? 0,
                $input['maximum_stock'] ?? null,
                $input['unit_price'] ?? 0,
                sanitizeInput($input['supplier'] ?? null)
            ]);
            
            $itemId = $db->lastInsertId();
            
            // Log audit
            logAudit('inventory_item', $itemId, 'create', null, json_encode($input), 
                     $_SESSION['user_id'] ?? null, $_SESSION['user_type'] ?? 'admin');
            
            sendJSON(['success' => true, 'message' => 'Inventory item created successfully', 'id' => $itemId]);
            break;
            
        case 'restock':
            // Restock an item
            if (!isset($input['item_id']) || !isset($input['quantity'])) {
                sendJSON(['success' => false, 'message' => 'Item ID and quantity are required'], 400);
            }
            
            $db->beginTransaction();
            
            try {
                // Get current item
                $stmt = $db->prepare("SELECT * FROM inventory_items WHERE id = ?");
                $stmt->execute([$input['item_id']]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    $db->rollBack();
                    sendJSON(['success' => false, 'message' => 'Item not found'], 404);
                }
                
                $newStock = $item['current_stock'] + $input['quantity'];
                
                // Update stock
                $stmt = $db->prepare("
                    UPDATE inventory_items 
                    SET current_stock = ?, last_restocked = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newStock, $input['item_id']]);
                
                // Record transaction
                $unitPrice = $input['unit_price'] ?? $item['unit_price'];
                $totalAmount = $unitPrice * $input['quantity'];
                
                $stmt = $db->prepare("
                    INSERT INTO inventory_transactions 
                    (item_id, transaction_type, quantity, unit_price, total_amount, notes, created_by)
                    VALUES (?, 'restock', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['item_id'],
                    $input['quantity'],
                    $unitPrice,
                    $totalAmount,
                    sanitizeInput($input['notes'] ?? 'Restock'),
                    $_SESSION['user_id'] ?? null
                ]);
                
                $db->commit();
                
                // Log audit
                logAudit('inventory_restock', $input['item_id'], 'restock', 
                         $item['current_stock'], $newStock, 
                         $_SESSION['user_id'] ?? null, $_SESSION['user_type'] ?? 'admin');
                
                sendJSON(['success' => true, 'message' => 'Item restocked successfully', 'new_stock' => $newStock]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'use':
            // Use/consume inventory
            if (!isset($input['item_id']) || !isset($input['quantity'])) {
                sendJSON(['success' => false, 'message' => 'Item ID and quantity are required'], 400);
            }
            
            $db->beginTransaction();
            
            try {
                // Get current item
                $stmt = $db->prepare("SELECT * FROM inventory_items WHERE id = ?");
                $stmt->execute([$input['item_id']]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    $db->rollBack();
                    sendJSON(['success' => false, 'message' => 'Item not found'], 404);
                }
                
                if ($item['current_stock'] < $input['quantity']) {
                    $db->rollBack();
                    sendJSON(['success' => false, 'message' => 'Insufficient stock'], 400);
                }
                
                $newStock = $item['current_stock'] - $input['quantity'];
                
                // Update stock
                $stmt = $db->prepare("UPDATE inventory_items SET current_stock = ? WHERE id = ?");
                $stmt->execute([$newStock, $input['item_id']]);
                
                // Record transaction
                $unitPrice = $item['unit_price'];
                $totalAmount = $unitPrice * $input['quantity'];
                
                $stmt = $db->prepare("
                    INSERT INTO inventory_transactions 
                    (item_id, transaction_type, quantity, unit_price, total_amount, notes, created_by)
                    VALUES (?, 'use', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['item_id'],
                    $input['quantity'],
                    $unitPrice,
                    $totalAmount,
                    sanitizeInput($input['notes'] ?? 'Stock used'),
                    $_SESSION['user_id'] ?? null
                ]);
                
                $db->commit();
                
                // Log audit
                logAudit('inventory_use', $input['item_id'], 'use', 
                         $item['current_stock'], $newStock, 
                         $_SESSION['user_id'] ?? null, $_SESSION['user_type'] ?? 'admin');
                
                sendJSON(['success' => true, 'message' => 'Stock updated successfully', 'new_stock' => $newStock]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'item') {
        // Update inventory item
        if (!isset($input['id'])) {
            sendJSON(['success' => false, 'message' => 'Item ID is required'], 400);
        }
        
        // Get old values for audit
        $stmt = $db->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $stmt->execute([$input['id']]);
        $oldItem = $stmt->fetch();
        
        $stmt = $db->prepare("
            UPDATE inventory_items 
            SET item_name = ?, category = ?, unit = ?, 
                current_stock = ?, minimum_stock = ?, maximum_stock = ?,
                unit_price = ?, supplier = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            sanitizeInput($input['item_name']),
            sanitizeInput($input['category']),
            sanitizeInput($input['unit']),
            $input['current_stock'],
            $input['minimum_stock'],
            $input['maximum_stock'] ?? null,
            $input['unit_price'] ?? 0,
            sanitizeInput($input['supplier'] ?? null),
            $input['id']
        ]);
        
        // Log audit
        logAudit('inventory_item', $input['id'], 'update', 
                 json_encode($oldItem), json_encode($input), 
                 $_SESSION['user_id'] ?? null, $_SESSION['user_type'] ?? 'admin');
        
        sendJSON(['success' => true, 'message' => 'Inventory item updated successfully']);
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handleDelete($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'item') {
        // Soft delete inventory item
        if (!isset($input['id'])) {
            sendJSON(['success' => false, 'message' => 'Item ID is required'], 400);
        }
        
        $stmt = $db->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        // Log audit
        logAudit('inventory_item', $input['id'], 'delete', null, null, 
                 $_SESSION['user_id'] ?? null, $_SESSION['user_type'] ?? 'admin');
        
        sendJSON(['success' => true, 'message' => 'Inventory item deleted successfully']);
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}
