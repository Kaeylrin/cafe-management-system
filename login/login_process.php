<?php

/**
 * login_process.php
 * Cafe Nowa – Customer Login Handler
 *
 * Place at: cafe_nowa/login/login_process.php
 * The login form action should point here:
 *   action="../login/login_process.php"
 */

session_start();

// ─── DB CONFIG ───────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'cafenowa_db');
// ─────────────────────────────────────────────────────────────────────────────

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$redirect = '../customer/landing.php'; // where to go after login
$loginPage = 'login.html';

// Basic check
if (empty($email) || empty($password)) {
    header("Location: {$loginPage}?error=" . urlencode('Please enter your email and password.'));
    exit;
}

// ── DB connection ─────────────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header("Location: {$loginPage}?error=" . urlencode('Server error. Please try again.'));
    exit;
}
$conn->set_charset('utf8mb4');

// ── Look up customer by email ─────────────────────────────────────────────────
$stmt = $conn->prepare('SELECT id, username, email, password_hash, full_name, phone, is_active FROM customers WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// ── Verify password ───────────────────────────────────────────────────────────
if (!$customer || !password_verify($password, $customer['password_hash'])) {
    // Log failed attempt
    $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $uname     = $customer['username'] ?? $email;
    $action    = 'Failed login - ' . ($customer ? 'wrong password' : 'user not found');
    $uid       = $customer['id'] ?? 0;
    $audit = $conn->prepare(
        "INSERT INTO audit_trail (user_type, user_id, username, action, action_type, ip_address, user_agent)
         VALUES ('customer', ?, ?, ?, 'failed_login', ?, ?)"
    );
    $audit->bind_param('issss', $uid, $uname, $action, $ip, $ua);
    $audit->execute();
    $audit->close();

    $conn->close();
    header("Location: {$loginPage}?error=" . urlencode('Invalid email or password.'));
    exit;
}

// ── Check account is active ───────────────────────────────────────────────────
if (!$customer['is_active']) {
    $conn->close();
    header("Location: {$loginPage}?error=" . urlencode('Your account has been deactivated. Please contact support.'));
    exit;
}

// ── All good – start session ──────────────────────────────────────────────────
// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Store customer info in session
$_SESSION['customer_logged_in'] = true;
$_SESSION['customer_id']        = $customer['id'];
$_SESSION['customer_username']  = $customer['username'];
$_SESSION['customer_email']     = $customer['email'];
$_SESSION['customer_name']      = $customer['full_name'];
$_SESSION['customer_phone']     = $customer['phone'];

// Initialise this customer's own cart if it doesn't exist yet
// Cart is keyed by customer ID so each customer has a completely separate cart
$cartKey = 'cart_customer_' . $customer['id'];
if (!isset($_SESSION[$cartKey])) {
    $_SESSION[$cartKey] = [];
}

// ── Update last_login timestamp ───────────────────────────────────────────────
$upd = $conn->prepare('UPDATE customers SET last_login = NOW() WHERE id = ?');
$upd->bind_param('i', $customer['id']);
$upd->execute();
$upd->close();

// ── Log successful login to audit trail ───────────────────────────────────────
$ip     = $_SERVER['REMOTE_ADDR'] ?? null;
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? null;
$action = 'Successful login';
$audit  = $conn->prepare(
    "INSERT INTO audit_trail (user_type, user_id, username, action, action_type, ip_address, user_agent)
     VALUES ('customer', ?, ?, ?, 'login', ?, ?)"
);
$audit->bind_param('issss', $customer['id'], $customer['username'], $action, $ip, $ua);
$audit->execute();
$audit->close();

$conn->close();

// ── Redirect to landing page ──────────────────────────────────────────────────
header("Location: {$redirect}");
exit;
