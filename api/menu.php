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
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePost($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Create new category
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $displayOrder = $input['display_order'] ?? 0;
            
            if (empty($name)) {
                sendJSON(['success' => false, 'message' => 'Category name is required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO menu_categories (name, description, display_order)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $description, $displayOrder]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Created menu category: $name", 'create', 'menu_categories', $db->lastInsertId());
            
            sendJSON(['success' => true, 'message' => 'Category created', 'id' => $db->lastInsertId()]);
            break;
            
        case 'item':
            // Create new menu item
            $categoryId = $input['category_id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = $input['price'] ?? 0;
            $imageUrl = $input['image_url'] ?? '';
            $isAvailable = $input['is_available'] ?? true;
            $isFeatured = $input['is_featured'] ?? false;
            $displayOrder = $input['display_order'] ?? 0;
            
            if (empty($name) || !$categoryId || $price <= 0) {
                sendJSON(['success' => false, 'message' => 'Name, category, and valid price are required'], 400);
            }
            
            $stmt = $db->prepare("
                INSERT INTO menu_items 
                (category_id, name, description, price, image_url, is_available, is_featured, display_order, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoryId, $name, $description, $price, $imageUrl,
                $isAvailable ? 1 : 0, $isFeatured ? 1 : 0, $displayOrder,
                $_SESSION['user_id']
            ]);
            
            $itemId = $db->lastInsertId();
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Created menu item: $name", 'create', 'menu_items', $itemId);
            
            sendJSON(['success' => true, 'message' => 'Menu item created', 'id' => $itemId]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Update category
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $displayOrder = $input['display_order'] ?? 0;
            $isActive = $input['is_active'] ?? true;
            
            if (!$id || empty($name)) {
                sendJSON(['success' => false, 'message' => 'ID and name are required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE menu_categories
                SET name = ?, description = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $displayOrder, $isActive ? 1 : 0, $id]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Updated menu category: $name", 'update', 'menu_categories', $id);
            
            sendJSON(['success' => true, 'message' => 'Category updated']);
            break;
            
        case 'item':
            // Update menu item
            $id = $input['id'] ?? null;
            $categoryId = $input['category_id'] ?? null;
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = $input['price'] ?? 0;
            $imageUrl = $input['image_url'] ?? '';
            $isAvailable = $input['is_available'] ?? true;
            $isFeatured = $input['is_featured'] ?? false;
            $displayOrder = $input['display_order'] ?? 0;
            
            if (!$id || empty($name) || !$categoryId || $price <= 0) {
                sendJSON(['success' => false, 'message' => 'ID, name, category, and valid price are required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE menu_items
                SET category_id = ?, name = ?, description = ?, price = ?,
                    image_url = ?, is_available = ?, is_featured = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categoryId, $name, $description, $price, $imageUrl,
                $isAvailable ? 1 : 0, $isFeatured ? 1 : 0, $displayOrder, $id
            ]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Updated menu item: $name", 'update', 'menu_items', $id);
            
            sendJSON(['success' => true, 'message' => 'Menu item updated']);
            break;
            
        case 'toggle-availability':
            // Toggle item availability
            $id = $input['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'ID required'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE menu_items
                SET is_available = NOT is_available
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Toggled availability for menu item ID: $id", 'update', 'menu_items', $id);
            
            sendJSON(['success' => true, 'message' => 'Availability toggled']);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}

function handleDelete($db, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'category':
            // Delete category (will cascade delete items)
            $id = $input['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'ID required'], 400);
            }
            
            // Get category name for audit
            $stmt = $db->prepare("SELECT name FROM menu_categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();
            
            $stmt = $db->prepare("DELETE FROM menu_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Deleted menu category: " . ($category['name'] ?? 'Unknown'), 'delete', 'menu_categories', $id);
            
            sendJSON(['success' => true, 'message' => 'Category deleted']);
            break;
            
        case 'item':
            // Delete menu item
            $id = $input['id'] ?? null;
            if (!$id) {
                sendJSON(['success' => false, 'message' => 'ID required'], 400);
            }
            
            // Get item name for audit
            $stmt = $db->prepare("SELECT name FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            
            logAudit($_SESSION['user_type'], $_SESSION['user_id'], $_SESSION['username'],
                    "Deleted menu item: " . ($item['name'] ?? 'Unknown'), 'delete', 'menu_items', $id);
            
            sendJSON(['success' => true, 'message' => 'Menu item deleted']);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
    }
}
?>
