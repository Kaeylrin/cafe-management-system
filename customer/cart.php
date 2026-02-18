<?php

/**
 * cart.php
 * Cafe Nowa – Per-Customer Cart Handler (AJAX endpoint)
 *
 * Place at: cafe_nowa/customer/cart.php
 *
 * All cart operations are tied to the logged-in customer's session,
 * so each customer has a completely private, isolated cart.
 *
 * Accepts POST with JSON body or form fields:
 *   action  : 'add' | 'remove' | 'update' | 'clear' | 'get'
 *   item_id : menu item ID
 *   name    : item name
 *   price   : unit price
 *   qty     : quantity (for add/update)
 */

require_once '../includes/session_check.php';

header('Content-Type: application/json');

// Parse input (supports both JSON body and form POST)
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input) {
  $input = $_POST;
}

$action  = trim($input['action']  ?? 'get');
$itemId  = intval($input['item_id'] ?? 0);
$name    = trim($input['name']    ?? '');
$price   = floatval($input['price'] ?? 0);
$qty     = max(1, intval($input['qty'] ?? 1));

// Get a reference to THIS customer's cart (never touches other customers' carts)
$cart = &getCart();

switch ($action) {

  // ── Add item (or increment qty if already in cart) ────────────────────────
  case 'add':
    if ($itemId <= 0 || $price <= 0 || $name === '') {
      echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
      exit;
    }
    if (isset($cart[$itemId])) {
      $cart[$itemId]['qty'] += $qty;
    } else {
      $cart[$itemId] = [
        'item_id' => $itemId,
        'name'    => $name,
        'price'   => $price,
        'qty'     => $qty,
      ];
    }
    break;

  // ── Remove item completely ────────────────────────────────────────────────
  case 'remove':
    unset($cart[$itemId]);
    break;

  // ── Update quantity (set to 0 or below = remove) ─────────────────────────
  case 'update':
    if ($qty <= 0) {
      unset($cart[$itemId]);
    } elseif (isset($cart[$itemId])) {
      $cart[$itemId]['qty'] = $qty;
    }
    break;

  // ── Clear entire cart ─────────────────────────────────────────────────────
  case 'clear':
    $cart = [];
    break;

  // ── Get cart (default) ────────────────────────────────────────────────────
  case 'get':
  default:
    // Just fall through to the response below
    break;
}

// ── Build response ────────────────────────────────────────────────────────────
$total     = 0;
$itemCount = 0;
$items     = [];

foreach ($cart as $entry) {
  $subtotal   = $entry['price'] * $entry['qty'];
  $total     += $subtotal;
  $itemCount += $entry['qty'];
  $items[]    = array_merge($entry, ['subtotal' => round($subtotal, 2)]);
}

echo json_encode([
  'success'    => true,
  'customer_id' => $_SESSION['customer_id'],  // for debugging
  'items'      => array_values($items),
  'item_count' => $itemCount,
  'total'      => round($total, 2),
]);
exit;
