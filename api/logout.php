<?php
/**
 * Logout API
 * Handles secure logout for all user types
 */

session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    if (isLoggedIn()) {
        // Log logout action
        logAudit(
            $_SESSION['user_type'],
            $_SESSION['user_id'],
            $_SESSION['username'],
            'User logged out',
            'logout'
        );
        
        // Destroy session
        session_unset();
        session_destroy();
        
        sendJSON([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => 'Not logged in'
        ], 400);
    }
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    sendJSON([
        'success' => false,
        'message' => 'An error occurred'
    ], 500);
}
?>
