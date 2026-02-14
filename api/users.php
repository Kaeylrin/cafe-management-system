<?php
/**
 * User Management API
 * Handles CRUD operations for user accounts
 * Super Admin: Can create/manage admins, employees, customers
 * Admin: Can create/manage employees, customers
 */

session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Require admin or super_admin role
requireRole(['super_admin', 'admin']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();
    
    switch ($method) {
        case 'POST': // Create new user
            handleCreateUser($db);
            break;
            
        case 'GET': // List users
            handleListUsers($db);
            break;
            
        case 'PUT': // Update user
            handleUpdateUser($db);
            break;
            
        case 'DELETE': // Delete/deactivate user
            handleDeleteUser($db);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    error_log("User management error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}

/**
 * Create new user account
 */
function handleCreateUser($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['user_type', 'username', 'email', 'password', 'full_name'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            sendJSON(['success' => false, 'message' => "Field '{$field}' is required"], 400);
        }
    }
    
    $userType = sanitizeInput($input['user_type']);
    $username = sanitizeInput($input['username']);
    $email = sanitizeInput($input['email']);
    $password = $input['password'];
    $fullName = sanitizeInput($input['full_name']);
    $position = isset($input['position']) ? sanitizeInput($input['position']) : 'Barista';
    $phone = isset($input['phone']) ? sanitizeInput($input['phone']) : null;
    
    // Validate email
    if (!isValidEmail($email)) {
        sendJSON(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        sendJSON(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
    }
    
    // Check permissions
    if ($_SESSION['user_type'] === 'admin' && $userType === 'admin') {
        sendJSON(['success' => false, 'message' => 'Only Super Admins can create Admin accounts'], 403);
    }
    
    if ($_SESSION['user_type'] === 'admin' && $userType === 'super_admin') {
        sendJSON(['success' => false, 'message' => 'Only Super Admins can create Super Admin accounts'], 403);
    }
    
    // Determine target table
    $tableMap = [
        'super_admin' => 'super_admins',
        'admin' => 'admins',
        'employee' => 'employees',
        'customer' => 'customers'
    ];
    
    if (!isset($tableMap[$userType])) {
        sendJSON(['success' => false, 'message' => 'Invalid user type'], 400);
    }
    
    $table = $tableMap[$userType];
    
    // Check if username or email already exists
    $stmt = $db->prepare("SELECT id FROM {$table} WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'Username or email already exists'], 409);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
    
    // Prepare insert query based on user type
    if ($userType === 'employee') {
        $stmt = $db->prepare("
            INSERT INTO employees (username, email, password_hash, full_name, position, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $position, $_SESSION['user_id']]);
    } elseif ($userType === 'customer') {
        $stmt = $db->prepare("
            INSERT INTO customers (username, email, password_hash, full_name, phone)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $phone]);
    } elseif ($userType === 'admin') {
        $stmt = $db->prepare("
            INSERT INTO admins (username, email, password_hash, full_name, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $_SESSION['user_id']]);
    } elseif ($userType === 'super_admin') {
        $stmt = $db->prepare("
            INSERT INTO super_admins (username, email, password_hash, full_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName]);
    }
    
    $newUserId = $db->lastInsertId();
    
    // Log the creation
    logAudit(
        $_SESSION['user_type'],
        $_SESSION['user_id'],
        $_SESSION['username'],
        "Created new {$userType} account: {$username}",
        'create',
        $table,
        $newUserId
    );
    
    sendJSON([
        'success' => true,
        'message' => 'User created successfully',
        'data' => [
            'user_id' => $newUserId,
            'username' => $username
        ]
    ]);
}

/**
 * List users
 */
function handleListUsers($db) {
    $userType = isset($_GET['user_type']) ? sanitizeInput($_GET['user_type']) : 'all';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;
    
    // Super admins can see all users, admins can't see super_admins
    $allowedTypes = [];
    if ($_SESSION['user_type'] === 'super_admin') {
        $allowedTypes = ['super_admin', 'admin', 'employee', 'customer'];
    } else {
        $allowedTypes = ['admin', 'employee', 'customer'];
    }
    
    $users = [];
    $totalCount = 0;
    
    if ($userType === 'all') {
        // Get all user types
        foreach ($allowedTypes as $type) {
            $tableMap = [
                'super_admin' => 'super_admins',
                'admin' => 'admins',
                'employee' => 'employees',
                'customer' => 'customers'
            ];
            
            $table = $tableMap[$type];
            
            $stmt = $db->prepare("
                SELECT id, username, email, full_name, created_at, last_login, is_active
                FROM {$table}
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            foreach ($results as $user) {
                $user['user_type'] = $type;
                $users[] = $user;
            }
        }
        
        $totalCount = count($users);
        
        // Sort by created_at and paginate
        usort($users, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $users = array_slice($users, $offset, $limit);
        
    } else {
        // Get specific user type
        if (!in_array($userType, $allowedTypes)) {
            sendJSON(['success' => false, 'message' => 'Not authorized to view this user type'], 403);
        }
        
        $tableMap = [
            'super_admin' => 'super_admins',
            'admin' => 'admins',
            'employee' => 'employees',
            'customer' => 'customers'
        ];
        
        $table = $tableMap[$userType];
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM {$table}");
        $stmt->execute();
        $totalCount = $stmt->fetch()['total'];
        
        // Get paginated results
        $stmt = $db->prepare("
            SELECT id, username, email, full_name, created_at, last_login, is_active
            FROM {$table}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $results = $stmt->fetchAll();
        
        foreach ($results as $user) {
            $user['user_type'] = $userType;
            $users[] = $user;
        }
    }
    
    // Log the view action
    logAudit(
        $_SESSION['user_type'],
        $_SESSION['user_id'],
        $_SESSION['username'],
        "Viewed user list (type: {$userType})",
        'view'
    );
    
    sendJSON([
        'success' => true,
        'data' => [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_records' => $totalCount,
                'total_pages' => ceil($totalCount / $limit),
                'records_per_page' => $limit
            ]
        ]
    ]);
}

/**
 * Update user
 */
function handleUpdateUser($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['user_type'])) {
        sendJSON(['success' => false, 'message' => 'User ID and type required'], 400);
    }
    
    $userId = intval($input['user_id']);
    $userType = sanitizeInput($input['user_type']);
    
    // Check permissions
    if ($_SESSION['user_type'] === 'admin' && in_array($userType, ['admin', 'super_admin'])) {
        sendJSON(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
    
    $tableMap = [
        'super_admin' => 'super_admins',
        'admin' => 'admins',
        'employee' => 'employees',
        'customer' => 'customers'
    ];
    
    $table = $tableMap[$userType];
    
    // Build update query
    $updateFields = [];
    $params = [];
    
    if (isset($input['full_name'])) {
        $updateFields[] = "full_name = ?";
        $params[] = sanitizeInput($input['full_name']);
    }
    
    if (isset($input['email'])) {
        $email = sanitizeInput($input['email']);
        if (!isValidEmail($email)) {
            sendJSON(['success' => false, 'message' => 'Invalid email format'], 400);
        }
        $updateFields[] = "email = ?";
        $params[] = $email;
    }
    
    if (isset($input['is_active'])) {
        $updateFields[] = "is_active = ?";
        $params[] = $input['is_active'] ? 1 : 0;
    }
    
    if (isset($input['password']) && !empty($input['password'])) {
        if (strlen($input['password']) < 8) {
            sendJSON(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
        }
        $updateFields[] = "password_hash = ?";
        $params[] = password_hash($input['password'], PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
    }
    
    if (empty($updateFields)) {
        sendJSON(['success' => false, 'message' => 'No fields to update'], 400);
    }
    
    $params[] = $userId;
    
    $query = "UPDATE {$table} SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    // Log the update
    logAudit(
        $_SESSION['user_type'],
        $_SESSION['user_id'],
        $_SESSION['username'],
        "Updated {$userType} account (ID: {$userId})",
        'update',
        $table,
        $userId,
        json_encode($input)
    );
    
    sendJSON([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
}

/**
 * Delete/deactivate user
 */
function handleDeleteUser($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['user_type'])) {
        sendJSON(['success' => false, 'message' => 'User ID and type required'], 400);
    }
    
    $userId = intval($input['user_id']);
    $userType = sanitizeInput($input['user_type']);
    $permanentDelete = isset($input['permanent']) && $input['permanent'] === true;
    
    // Check permissions
    if ($_SESSION['user_type'] === 'admin' && in_array($userType, ['admin', 'super_admin'])) {
        sendJSON(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
    
    // Prevent deleting yourself
    if ($userId == $_SESSION['user_id'] && $userType == $_SESSION['user_type']) {
        sendJSON(['success' => false, 'message' => 'Cannot delete your own account'], 400);
    }
    
    $tableMap = [
        'super_admin' => 'super_admins',
        'admin' => 'admins',
        'employee' => 'employees',
        'customer' => 'customers'
    ];
    
    $table = $tableMap[$userType];
    
    if ($permanentDelete) {
        // Permanent deletion (use with caution)
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->execute([$userId]);
        $action = "Permanently deleted {$userType} account (ID: {$userId})";
    } else {
        // Soft delete (deactivate)
        $stmt = $db->prepare("UPDATE {$table} SET is_active = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        $action = "Deactivated {$userType} account (ID: {$userId})";
    }
    
    // Log the deletion
    logAudit(
        $_SESSION['user_type'],
        $_SESSION['user_id'],
        $_SESSION['username'],
        $action,
        'delete',
        $table,
        $userId
    );
    
    sendJSON([
        'success' => true,
        'message' => $permanentDelete ? 'User deleted permanently' : 'User deactivated successfully'
    ]);
}
?>
