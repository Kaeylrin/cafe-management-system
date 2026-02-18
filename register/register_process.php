<?php

/**
 * register_process.php
 * Cafe Nowa – Customer Registration Handler
 *
 * Place this file at:  cafe_nowa/register/register_process.php
 * The HTML form is at: cafe_nowa/register/register.html
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ─── DB CONFIG ───────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // change if needed
define('DB_PASS', '');               // change if needed
define('DB_NAME', 'cafenowa_db');
// ─────────────────────────────────────────────────────────────────────────────

function json_response(bool $success, string $message, array $extra = []): void
{
  echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
  exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(false, 'Method not allowed.');
}

// ── Collect & sanitize inputs ─────────────────────────────────────────────────
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? '');
$phone     = trim($_POST['phone']     ?? '');
$password  = $_POST['password']       ?? '';

// ── Server-side validation ────────────────────────────────────────────────────
$errors = [];

if (empty($full_name)) {
  $errors[] = 'Full name is required.';
}

if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
  $errors[] = 'Username must be 3–50 characters (letters, numbers, underscores).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'A valid email address is required.';
}

if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
  $errors[] = 'Phone number must be exactly 11 digits.';
}

if (strlen($password) < 8) {
  $errors[] = 'Password must be at least 8 characters.';
}

if (!empty($errors)) {
  json_response(false, implode(' ', $errors));
}

// ── DB connection ─────────────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
  json_response(false, 'Database connection failed. Please try again later.');
}
$conn->set_charset('utf8mb4');

// ── Check for duplicate email / username ──────────────────────────────────────
$stmt = $conn->prepare('SELECT id FROM customers WHERE email = ? OR username = ? LIMIT 1');
$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  // Find out which one is taken for a better message
  $stmt->close();

  $stmtE = $conn->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
  $stmtE->bind_param('s', $email);
  $stmtE->execute();
  $stmtE->store_result();
  $emailTaken = $stmtE->num_rows > 0;
  $stmtE->close();

  $stmtU = $conn->prepare('SELECT id FROM customers WHERE username = ? LIMIT 1');
  $stmtU->bind_param('s', $username);
  $stmtU->execute();
  $stmtU->store_result();
  $userTaken = $stmtU->num_rows > 0;
  $stmtU->close();

  $msg = '';
  if ($emailTaken && $userTaken) $msg = 'Both that email and username are already registered.';
  elseif ($emailTaken) $msg = 'That email address is already registered.';
  else $msg = 'That username is already taken.';

  $conn->close();
  json_response(false, $msg);
}
$stmt->close();

// ── Hash password & insert ────────────────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT);
$phoneVal = $phone !== '' ? $phone : null;

$insert = $conn->prepare(
  'INSERT INTO customers (username, email, password_hash, full_name, phone)
     VALUES (?, ?, ?, ?, ?)'
);
$insert->bind_param('sssss', $username, $email, $hash, $full_name, $phoneVal);

if (!$insert->execute()) {
  $insert->close();
  $conn->close();
  json_response(false, 'Could not create account. Please try again.');
}

$newId = $conn->insert_id;
$insert->close();

// ── Log to audit trail ────────────────────────────────────────────────────────
$ip        = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$action    = 'New customer registration';
$actionType = 'create';

$audit = $conn->prepare(
  'INSERT INTO audit_trail (user_type, user_id, username, action, action_type, target_table, target_id, ip_address, user_agent)
     VALUES (\'customer\', ?, ?, ?, ?, \'customers\', ?, ?, ?)'
);
$audit->bind_param('issssss', $newId, $username, $action, $actionType, $newId, $ip, $userAgent);
$audit->execute();
$audit->close();

$conn->close();

json_response(true, 'Account created successfully!', ['user_id' => $newId]);
