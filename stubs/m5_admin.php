<?php
declare(strict_types=1);

/**
 * PROVIDED STUB — m5_admin reference implementation (contract-compliant).
 * Requires an authenticated ADMIN user (M1 with is_admin=1).
 */

$rest = $GLOBALS['__route']['rest'] ?? '';
$method = http_method();
$pdo = db();

function m5_require_admin(PDO $pdo): int
{
    if (empty($_SESSION['user_id'])) send_error(401, 'AUTH_REQUIRED');
    $uid = (int) $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    if (!$row || (int) $row['is_admin'] !== 1) send_error(403, 'FORBIDDEN');
    return $uid;
}

function m5_product_row(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'category_id' => (int) $r['category_id'],
        'name' => $r['name'],
        'price' => (float) $r['price'],
        'stock' => (int) $r['stock'],
    ];
}

function m5_validate_product(array $b): array
{
    $fields = [];
    if (trim((string) ($b['name'] ?? '')) === '') $fields['name'] = 'required';
    if (!isset($b['price']) || !is_numeric($b['price']) || (float) $b['price'] < 0) $fields['price'] = 'must be >= 0';
    if (isset($b['stock']) && (!is_numeric($b['stock']) || (int) $b['stock'] < 0)) $fields['stock'] = 'must be >= 0';
    if (empty($b['category_id']) || !is_numeric($b['category_id'])) $fields['category_id'] = 'required';
    return $fields;
}

switch (true) {
    case $method === 'POST' && $rest === '/products': {
        m5_require_admin($pdo);
        $b = json_body();
        $fields = m5_validate_product($b);
        if ($fields) send_error(422, 'VALIDATION', ['fields' => $fields]);
        $id = db_insert_and_get_id($pdo, 'products', [
            'category_id' => (int) $b['category_id'],
            'name' => trim((string) $b['name']),
            'price' => (float) $b['price'],
            'stock' => isset($b['stock']) ? (int) $b['stock'] : 0,
        ]);
        send_json(201, m5_product_row(['id' => $id, 'category_id' => (int) $b['category_id'], 'name' => trim((string) $b['name']), 'price' => (float) $b['price'], 'stock' => isset($b['stock']) ? (int) $b['stock'] : 0]));
    }

    case $method === 'PUT' && preg_match('#^/products/(\d+)$#', $rest, $mm): {
        m5_require_admin($pdo);
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int) $mm[1]]);
        if (!$stmt->fetch()) send_error(404, 'NOT_FOUND');
        $b = json_body();
        $fields = m5_validate_product($b);
        if ($fields) send_error(422, 'VALIDATION', ['fields' => $fields]);
        $pdo->prepare('UPDATE products SET category_id = ?, name = ?, price = ?, stock = ? WHERE id = ?')
            ->execute([(int) $b['category_id'], trim((string) $b['name']), (float) $b['price'], (int) ($b['stock'] ?? 0), (int) $mm[1]]);
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int) $mm[1]]);
        send_json(200, m5_product_row($stmt->fetch()));
    }

    case $method === 'DELETE' && preg_match('#^/products/(\d+)$#', $rest, $mm): {
        m5_require_admin($pdo);
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int) $mm[1]]);
        if ($stmt->rowCount() === 0) send_error(404, 'NOT_FOUND');
        send_json(200, ['deleted' => true]);
    }

    case $method === 'GET' && $rest === '/reports/sales': {
        m5_require_admin($pdo);
        $rows = $pdo->query('SELECT order_id, user_id, total, created_at FROM v_sales ORDER BY order_id')->fetchAll();
        $data = array_map(fn($r) => [
            'order_id' => (int) $r['order_id'],
            'user_id' => (int) $r['user_id'],
            'total' => (float) $r['total'],
            'created_at' => $r['created_at'],
        ], $rows);
        $revenue = round(array_sum(array_column($data, 'total')), 2);
        send_json(200, ['data' => $data, 'revenue' => $revenue]);
    }

    default:
        send_error(404, 'NOT_FOUND');
}
