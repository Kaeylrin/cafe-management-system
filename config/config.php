<?php
/**
 * Database Configuration for Cafe Nowa
 * IMPORTANT: Keep this file secure and never commit to public repositories
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'cafenowa_db');
define('DB_USER', 'root'); // CHANGE THIS IN PRODUCTION
define('DB_PASS', ''); // CHANGE THIS IN PRODUCTION
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12); // Higher = more secure but slower

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_NAME', 'CAFENOWA_SESSION');

// Security limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes in seconds
define('LOCKOUT_DURATION', 1800); // 30 minutes in seconds

// Application settings
define('SITE_URL', 'http://localhost/cafenowa');
define('ENABLE_DEBUG', true); // Set to FALSE in production

/**
 * Database Connection Class with PDO
 * Implements prepared statements for SQL injection prevention
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
                PDO::ATTR_PERSISTENT => false, // Don't use persistent connections
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (ENABLE_DEBUG) {
                die("Database Connection Failed: " . $e->getMessage());
            } else {
                die("Database Connection Failed. Please contact support.");
            }
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Helper function to sanitize input (additional layer)
 * Note: Still use prepared statements as primary defense
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Helper function to validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Helper function to get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Helper function to get user agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Helper function to log audit trail
 */
function logAudit($user_type, $user_id, $username, $action, $action_type, $target_table = null, $target_id = null, $details = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_trail 
            (user_type, user_id, username, action, action_type, target_table, target_id, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_type,
            $user_id,
            $username,
            $action,
            $action_type,
            $target_table,
            $target_id,
            getUserIP(),
            getUserAgent(),
            $details
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to send JSON response
 */
function sendJSON($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Helper function to require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        sendJSON(['success' => false, 'message' => 'Authentication required'], 401);
    }
}

/**
 * Helper function to check user role
 */
function requireRole($allowed_roles) {
    requireLogin();
    
    if (!in_array($_SESSION['user_type'], $allowed_roles)) {
        sendJSON(['success' => false, 'message' => 'Insufficient permissions'], 403);
    }
}

?>
