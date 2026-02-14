<?php
/**
 * Audit Trail API
 * Provides read-only access to audit logs
 * Only accessible by Super Admins and Admins
 */

session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

// Require admin or super_admin role
requireRole(['super_admin', 'admin']);

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $db = getDB();
    
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(10, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;
    
    $userType = isset($_GET['user_type']) ? sanitizeInput($_GET['user_type']) : '';
    $actionType = isset($_GET['action_type']) ? sanitizeInput($_GET['action_type']) : '';
    $dateFrom = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if ($userType) {
        $whereConditions[] = "user_type = ?";
        $params[] = $userType;
    }
    
    if ($actionType) {
        $whereConditions[] = "action_type = ?";
        $params[] = $actionType;
    }
    
    if ($dateFrom) {
        $whereConditions[] = "DATE(timestamp) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "DATE(timestamp) <= ?";
        $params[] = $dateTo;
    }
    
    if ($searchTerm) {
        $whereConditions[] = "(username LIKE ? OR action LIKE ? OR ip_address LIKE ?)";
        $searchParam = "%{$searchTerm}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM audit_trail {$whereClause}";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    
    // Get paginated results
    $query = "
        SELECT 
            id,
            user_type,
            user_id,
            username,
            action,
            action_type,
            target_table,
            target_id,
            ip_address,
            user_agent,
            details,
            timestamp
        FROM audit_trail
        {$whereClause}
        ORDER BY timestamp DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Log the view action
    logAudit(
        $_SESSION['user_type'],
        $_SESSION['user_id'],
        $_SESSION['username'],
        'Viewed audit trail',
        'view',
        'audit_trail'
    );
    
    sendJSON([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_records' => $totalRecords,
                'total_pages' => ceil($totalRecords / $limit),
                'records_per_page' => $limit
            ],
            'filters' => [
                'user_type' => $userType,
                'action_type' => $actionType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $searchTerm
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Audit trail error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'An error occurred while fetching audit logs'
    ], 500);
}
?>
