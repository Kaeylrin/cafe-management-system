<?php

/**
 * session_check.php
 * Cafe Nowa – Customer Session Guard
 *
 * Include this at the very top of every customer-only page
 * (before any HTML output) like this:
 *
 *   <?php require_once '../includes/session_check.php'; ?>
 *
 * Place at: cafe_nowa/includes/session_check.php
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// If not logged in, redirect to login page
if (empty($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
  header('Location: ../login/login.html');
  exit;
}

/**
 * Helper: get the cart for the currently logged-in customer.
 * Each customer's cart is stored under a unique session key
 * so no customer can ever see another customer's cart.
 *
 * @return array  Reference to the customer's cart array
 */
function &getCart(): array
{
  $cartKey = 'cart_customer_' . $_SESSION['customer_id'];
  if (!isset($_SESSION[$cartKey])) {
    $_SESSION[$cartKey] = [];
  }
  return $_SESSION[$cartKey];
}

/**
 * Helper: get currently logged-in customer's info as an array
 * @return array
 */
function getCurrentCustomer(): array
{
  return [
    'id'       => $_SESSION['customer_id'],
    'username' => $_SESSION['customer_username'],
    'email'    => $_SESSION['customer_email'],
    'name'     => $_SESSION['customer_name'],
    'phone'    => $_SESSION['customer_phone'] ?? '',
  ];
}
