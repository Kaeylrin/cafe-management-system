<?php
/**
 * Authentication API
 * Handles login for all user types with security features
 */

session_start();
require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    sendJSON(['success' => false, 'message' => 'Email and password are required'], 400);
}

$email = sanitizeInput($input['email']);
$password = $input['password']; // Don't sanitize password, will be hashed
$userType = isset($input['userType']) ? sanitizeInput($input['userType']) : 'auto'; // auto, admin, or user

// Validate email format
if (!isValidEmail($email)) {
    sendJSON(['success' => false, 'message' => 'Invalid email format'], 400);
}

try {
    $db = getDB();
    $ip_address = getUserIP();
    
    // Check if account is locked due to too many failed attempts
    $stmt = $db->prepare("
        SELECT COUNT(*) as failed_count
        FROM login_attempts
        WHERE email = ?
        AND ip_address = ?
        AND success = FALSE
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$email, $ip_address, LOGIN_ATTEMPT_WINDOW]);
    $result = $stmt->fetch();
    
    if ($result['failed_count'] >= MAX_LOGIN_ATTEMPTS) {
        // Log failed login attempt
        logAudit('unknown', 0, $email, 'Account locked - too many failed attempts', 'failed_login');
        
        sendJSON([
            'success' => false,
            'message' => 'Account temporarily locked due to multiple failed login attempts. Please try again in 30 minutes.'
        ], 429);
    }
    
    // Determine which tables to check based on userType
    $tablesToCheck = [];
    
    if ($userType === 'admin') {
        // Admin login portal - check super_admins and admins only
        $tablesToCheck = [
            ['table' => 'super_admins', 'type' => 'super_admin'],
            ['table' => 'admins', 'type' => 'admin']
        ];
    } elseif ($userType === 'user') {
        // User login portal - check employees and customers only
        $tablesToCheck = [
            ['table' => 'employees', 'type' => 'employee'],
            ['table' => 'customers', 'type' => 'customer']
        ];
    } else {
        // Auto-detect (backward compatibility) - check all tables
        $tablesToCheck = [
            ['table' => 'super_admins', 'type' => 'super_admin'],
            ['table' => 'admins', 'type' => 'admin'],
            ['table' => 'employees', 'type' => 'employee'],
            ['table' => 'customers', 'type' => 'customer']
        ];
    }
    
    $user = null;
    $foundInTable = null;
    
    // Search for user in appropriate tables
    foreach ($tablesToCheck as $table) {
        $stmt = $db->prepare("
            SELECT id, username, email, password_hash, full_name, is_active
            FROM {$table['table']}
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        if ($result) {
            $user = $result;
            $user['user_type'] = $table['type'];
            $foundInTable = $table['table'];
            break;
        }
    }
    
    // Record login attempt
    $stmt = $db->prepare("
        INSERT INTO login_attempts (email, ip_address, success)
        VALUES (?, ?, ?)
    ");
    
    if (!$user) {
        // User not found
        $stmt->execute([$email, $ip_address, 0]);
        
        logAudit('unknown', 0, $email, 'Failed login - user not found', 'failed_login');
        
        sendJSON([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        $stmt->execute([$email, $ip_address, 0]);
        
        logAudit($user['user_type'], $user['id'], $user['username'], 
                 'Failed login - account disabled', 'failed_login');
        
        sendJSON([
            'success' => false,
            'message' => 'Account is disabled. Please contact support.'
        ], 403);
    }
    
    // Verify password using password_verify (secure against timing attacks)
    if (!password_verify($password, $user['password_hash'])) {
        // Wrong password
        $stmt->execute([$email, $ip_address, 0]);
        
        logAudit($user['user_type'], $user['id'], $user['username'], 
                 'Failed login - wrong password', 'failed_login');
        
        sendJSON([
            'success' => false,
            'message' => 'Invalid email or password'
        ], 401);
    }
    
    // Successful login
    $stmt->execute([$email, $ip_address, 1]);
    
    // Update last login time
    $stmt = $db->prepare("
        UPDATE {$foundInTable}
        SET last_login = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    
    // Create session
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $ip_address;
    
    // Log successful login
    logAudit($user['user_type'], $user['id'], $user['username'], 
             'Successful login', 'login');
    
    // Determine redirect URL
    $redirectUrl = '';
    switch ($user['user_type']) {
        case 'super_admin':
        case 'admin':
            $redirectUrl = '../admin/dashboard.php';
            break;
        case 'employee':
            $redirectUrl = '../employee/dashboard.php';
            break;
        case 'customer':
            $redirectUrl = '../customer/landing.php';
            break;
    }
    
    sendJSON([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user_type' => $user['user_type'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'redirect_url' => $redirectUrl
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ], 500);
}
?>
