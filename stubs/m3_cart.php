<?php
declare(strict_types=1);

/**
 * PROVIDED STUB — m3_cart reference implementation (contract-compliant).
 * Requires auth (M1). Cart is per-user.
 */

$rest = $GLOBALS['__route']['rest'] ?? '';
$method = http_method();
$pdo = db();

function m3_require_user(): int
{
    if (empty($_SESSION['user_id'])) send_error(401, 'AUTH_REQUIRED');
    return (int) $_SESSION['user_id'];
}

function m3_line(array $r): array
{
    $unit = (float) $r['price'];
    $qty = (int) $r['qty'];
    return [
        'id' => (int) $r['id'],
        'product_id' => (int) $r['product_id'],
        'qty' => $qty,
        'unit_price' => $unit,
        'subtotal' => round($unit * $qty, 2),
    ];
}

switch (true) {
    case $method === 'GET' && $rest === '': {
        $uid = m3_require_user();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.product_id, c.qty, p.price
             FROM cart_items c JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ? ORDER BY c.id'
        );
        $stmt->execute([$uid]);
        $lines = array_map('m3_line', $stmt->fetchAll());
        $total = round(array_sum(array_column($lines, 'subtotal')), 2);
        send_json(200, ['data' => $lines, 'total' => $total]);
    }

    case $method === 'POST' && $rest === '': {
        $uid = m3_require_user();
        $b = json_body();
        $pid = param_id(isset($b['product_id']) ? (string) $b['product_id'] : null);
        $qty = isset($b['qty']) ? (int) $b['qty'] : 0;
        if (!$pid) send_error(422, 'VALIDATION', ['fields' => ['product_id' => 'required']]);
        if ($qty < 1) send_error(422, 'VALIDATION', ['fields' => ['qty' => 'must be >= 1']]);
        $p = $pdo->prepare('SELECT id, price FROM products WHERE id = ?');
        $p->execute([$pid]);
        $prod = $p->fetch();
        if (!$prod) send_error(404, 'PRODUCT_NOT_FOUND');
        $id = db_insert_and_get_id($pdo, 'cart_items', ['user_id' => $uid, 'product_id' => $pid, 'qty' => $qty]);
        send_json(201, m3_line(['id' => $id, 'product_id' => $pid, 'qty' => $qty, 'price' => $prod['price']]));
    }

    case $method === 'PATCH' && preg_match('#^/(\d+)$#', $rest, $mm): {
        $uid = m3_require_user();
        $id = (int) $mm[1];
        $b = json_body();
        $qty = isset($b['qty']) ? (int) $b['qty'] : 0;
        if ($qty < 1) send_error(422, 'VALIDATION', ['fields' => ['qty' => 'must be >= 1']]);
        $stmt = $pdo->prepare('SELECT c.id, c.product_id, p.price FROM cart_items c JOIN products p ON p.id=c.product_id WHERE c.id = ? AND c.user_id = ?');
        $stmt->execute([$id, $uid]);
        $row = $stmt->fetch();
        if (!$row) send_error(404, 'NOT_FOUND');
        $pdo->prepare('UPDATE cart_items SET qty = ? WHERE id = ?')->execute([$qty, $id]);
        send_json(200, m3_line(['id' => $id, 'product_id' => $row['product_id'], 'qty' => $qty, 'price' => $row['price']]));
    }

    case $method === 'DELETE' && preg_match('#^/(\d+)$#', $rest, $mm): {
        $uid = m3_require_user();
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
        $stmt->execute([(int) $mm[1], $uid]);
        if ($stmt->rowCount() === 0) send_error(404, 'NOT_FOUND');
        send_json(200, ['deleted' => true]);
    }

    default:
        send_error(404, 'NOT_FOUND');
}
