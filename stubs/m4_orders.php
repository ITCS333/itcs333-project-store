<?php
declare(strict_types=1);

/**
 * PROVIDED STUB — m4_orders reference implementation (contract-compliant).
 * Requires auth (M1). Checkout reads the user's cart (M3), decrements stock.
 */

$rest = $GLOBALS['__route']['rest'] ?? '';
$method = http_method();
$pdo = db();

function m4_require_user(): int
{
    if (empty($_SESSION['user_id'])) send_error(401, 'AUTH_REQUIRED');
    return (int) $_SESSION['user_id'];
}

function m4_now(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function m4_order_with_items(PDO $pdo, array $order): array
{
    $stmt = $pdo->prepare('SELECT product_id, qty, unit_price FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([(int) $order['id']]);
    $items = array_map(fn($i) => [
        'product_id' => (int) $i['product_id'],
        'qty' => (int) $i['qty'],
        'unit_price' => (float) $i['unit_price'],
    ], $stmt->fetchAll());
    return [
        'id' => (int) $order['id'],
        'user_id' => (int) $order['user_id'],
        'total' => (float) $order['total'],
        'created_at' => $order['created_at'],
        'items' => $items,
    ];
}

switch (true) {
    case $method === 'POST' && $rest === '': {
        $uid = m4_require_user();
        $cart = $pdo->prepare(
            'SELECT c.product_id, c.qty, p.price, p.stock
             FROM cart_items c JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ? ORDER BY c.id'
        );
        $cart->execute([$uid]);
        $lines = $cart->fetchAll();
        if (!$lines) send_error(400, 'CART_EMPTY');

        foreach ($lines as $l) {
            if ((int) $l['qty'] > (int) $l['stock']) {
                send_error(409, 'INSUFFICIENT_STOCK', ['product_id' => (int) $l['product_id']]);
            }
        }

        $total = 0.0;
        foreach ($lines as $l) $total += (float) $l['price'] * (int) $l['qty'];
        $total = round($total, 2);

        $orderId = db_insert_and_get_id($pdo, 'orders', ['user_id' => $uid, 'total' => $total, 'created_at' => m4_now()]);
        $insItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, unit_price) VALUES (?, ?, ?, ?)');
        $decStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        foreach ($lines as $l) {
            $insItem->execute([$orderId, (int) $l['product_id'], (int) $l['qty'], (float) $l['price']]);
            $decStock->execute([(int) $l['qty'], (int) $l['product_id']]);
        }
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$uid]);

        $order = ['id' => $orderId, 'user_id' => $uid, 'total' => $total, 'created_at' => m4_now()];
        send_json(201, m4_order_with_items($pdo, $order));
    }

    case $method === 'GET' && $rest === '': {
        $uid = m4_require_user();
        $stmt = $pdo->prepare('SELECT id, total, created_at FROM orders WHERE user_id = ? ORDER BY id');
        $stmt->execute([$uid]);
        $rows = array_map(fn($o) => [
            'id' => (int) $o['id'],
            'total' => (float) $o['total'],
            'created_at' => $o['created_at'],
        ], $stmt->fetchAll());
        send_json(200, ['data' => $rows]);
    }

    case $method === 'GET' && preg_match('#^/(\d+)$#', $rest, $mm): {
        $uid = m4_require_user();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([(int) $mm[1]]);
        $order = $stmt->fetch();
        if (!$order) send_error(404, 'NOT_FOUND');
        $isAdmin = false;
        if (!empty($_SESSION['user_id'])) {
            $a = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
            $a->execute([$uid]);
            $isAdmin = (int) ($a->fetch()['is_admin'] ?? 0) === 1;
        }
        if ((int) $order['user_id'] !== $uid && !$isAdmin) send_error(403, 'FORBIDDEN');
        send_json(200, m4_order_with_items($pdo, $order));
    }

    default:
        send_error(404, 'NOT_FOUND');
}
