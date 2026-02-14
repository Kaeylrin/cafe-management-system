<?php
/**
 * Menu Management API
 * Handles CRUD operations for menu items and categories
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

// Debug logging (remove in production)
if (ENABLE_DEBUG) {
    error_log("Menu API - Method: $method, Action: $action");
    error_log("Menu API - Session data: " . json_encode($_SESSION));
    error_log("Menu API - User type: " . ($_SESSION['user_type'] ?? 'NOT SET'));
}

// For GET requests (read operations), allow public access for menu items
// For POST/PUT/DELETE (write operations), require admin or super_admin
if ($method === 'GET') {
    // Allow public access to menu - no authentication needed for customers
    // This enables customer menu.php to work
} else {
    // Write operations require admin
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
    error_log("Menu API error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Database error occurred'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'categories':
            // Get all categories
            $stmt = $db->query("
                SELECT id, name, description, display_order, is_active
                FROM menu_categories
                ORDER BY display_order, name
            ");
            $categories = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $categories]);
            break;
            
        case 'items':
            // Get all menu items with category info
            $categoryId = $_GET['category_id'] ?? null;
            
            $sql = "
                SELECT 
                    mi.id, mi.category_id, mi.name, mi.description, 
                    mi.price, mi.image_url, mi.is_available, mi.is_featured,
                    mi.display_order, mi.created_at, mi.updated_at,
                    mc.name as category_name
                FROM menu_items mi
                JOIN menu_categories mc ON mi.category_id = mc.id
            ";
            
            if ($categoryId) {
                $sql .= " WHERE mi.category_id = ?";
                $stmt = $db->prepare($sql . " ORDER BY mi.display_order, mi.name");
                $stmt->execute([$categoryId]);
            } else {
                $stmt = $db->prepare($sql . " ORDER BY mc.display_order, mi.display_order, mi.name");
                $stmt->execute();
            }
            
            $items = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $items]);
            break;
            
        case 'item':
            // Get single menu item
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'Item ID required'], 400);
            }
            
            $stmt = $db->prepare("
                SELECT mi.*, mc.name as category_name
                FROM menu_items mi
                JOIN menu_categories mc ON mi.category_id = mc.id
                WHERE mi.id = ?
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
            // Default: return all items with categories
            $stmt = $db->query("
                SELECT 
                    mi.id, mi.category_id, mi.name, mi.description, 
                    mi.price, mi.image_url, mi.is_available, mi.is_featured,
                    mi.display_order,
                    mc.name as category_name
                FROM menu_items mi
                JOIN menu_categories mc ON mi.category_id = mc.id
                ORDER BY mc.display_order, mi.display_order, mi.name
            ");
            $items = $stmt->fetchAll();
            sendJSON(['success' => true, 'data' => $items]);
    }
}

function handlePost($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Create new category
            if (!isset($input['name'])) {
                sendJSON(['success' => false, 'message' => 'Category name is required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO menu_categories (name, description, display_order, is_active)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                sanitizeInput($input['name']),
                sanitizeInput($input['description'] ?? ''),
                $input['display_order'] ?? 0,
                isset($input['is_active']) ? (int)$input['is_active'] : 1
            ]);
            
            logAudit('menu_category', $db->lastInsertId(), 'create', null, json_encode($input), $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Category created successfully', 'id' => $db->lastInsertId()]);
            break;
            
        case 'item':
        default:
            // Create new menu item
            if (!isset($input['name']) || !isset($input['category_id']) || !isset($input['price'])) {
                sendJSON(['success' => false, 'message' => 'Name, category, and price are required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO menu_items 
                (category_id, name, description, price, image_url, is_available, is_featured, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['category_id'],
                sanitizeInput($input['name']),
                sanitizeInput($input['description'] ?? ''),
                $input['price'],
                sanitizeInput($input['image_url'] ?? ''),
                isset($input['is_available']) ? (int)$input['is_available'] : 1,
                isset($input['is_featured']) ? (int)$input['is_featured'] : 0,
                $input['display_order'] ?? 0
            ]);
            
            logAudit('menu_item', $db->lastInsertId(), 'create', null, json_encode($input), $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Menu item created successfully', 'id' => $db->lastInsertId()]);
    }
}

function handlePut($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Update category
            if (!isset($input['id'])) {
                sendJSON(['success' => false, 'message' => 'Category ID is required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE menu_categories 
                SET name = ?, description = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                sanitizeInput($input['name']),
                sanitizeInput($input['description'] ?? ''),
                $input['display_order'] ?? 0,
                isset($input['is_active']) ? (int)$input['is_active'] : 1,
                $input['id']
            ]);
            
            logAudit('menu_category', $input['id'], 'update', null, json_encode($input), $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Category updated successfully']);
            break;
            
        case 'item':
        default:
            // Update menu item
            if (!isset($input['id'])) {
                sendJSON(['success' => false, 'message' => 'Item ID is required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE menu_items 
                SET category_id = ?, name = ?, description = ?, price = ?, 
                    image_url = ?, is_available = ?, is_featured = ?, display_order = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['category_id'],
                sanitizeInput($input['name']),
                sanitizeInput($input['description'] ?? ''),
                $input['price'],
                sanitizeInput($input['image_url'] ?? ''),
                isset($input['is_available']) ? (int)$input['is_available'] : 1,
                isset($input['is_featured']) ? (int)$input['is_featured'] : 0,
                $input['display_order'] ?? 0,
                $input['id']
            ]);
            
            logAudit('menu_item', $input['id'], 'update', null, json_encode($input), $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Menu item updated successfully']);
    }
}

function handleDelete($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Delete category
            if (!isset($input['id'])) {
                sendJSON(['success' => false, 'message' => 'Category ID is required'], 400);
            }
            
            // Check if category has items
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?");
            $stmt->execute([$input['id']]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                sendJSON(['success' => false, 'message' => 'Cannot delete category with items. Please reassign or delete the items first.'], 400);
            }
            
            $stmt = $db->prepare("DELETE FROM menu_categories WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            logAudit('menu_category', $input['id'], 'delete', null, null, $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Category deleted successfully']);
            break;
            
        case 'item':
        default:
            // Delete menu item
            if (!isset($input['id'])) {
                sendJSON(['success' => false, 'message' => 'Item ID is required'], 400);
            }
            
            $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            logAudit('menu_item', $input['id'], 'delete', null, null, $_SESSION['user_id'] ?? null, 'admin');
            
            sendJSON(['success' => true, 'message' => 'Menu item deleted successfully']);
    }
}
