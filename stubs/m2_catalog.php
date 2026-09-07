<?php
declare(strict_types=1);

/**
 * PROVIDED STUB — m2_catalog reference implementation (contract-compliant).
 */

$rest = $GLOBALS['__route']['rest'] ?? '';
$method = http_method();
$pdo = db();

function m2_product_row(array $r): array
{
    return [
        'id' => (int) $r['id'],
        'category_id' => (int) $r['category_id'],
        'name' => $r['name'],
        'price' => (float) $r['price'],
        'stock' => (int) $r['stock'],
    ];
}

switch (true) {
    case $method === 'GET' && $rest === '/products': {
        $sql = 'SELECT * FROM products WHERE 1=1';
        $args = [];
        if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
            $sql .= ' AND category_id = ?';
            $args[] = (int) $_GET['category_id'];
        }
        if (!empty($_GET['q'])) {
            $sql .= ' AND name LIKE ?';
            $args[] = '%' . $_GET['q'] . '%';
        }
        $sql .= ' ORDER BY id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $rows = array_map('m2_product_row', $stmt->fetchAll());
        send_json(200, ['data' => $rows]);
    }

    case $method === 'GET' && preg_match('#^/products/(\d+)$#', $rest, $mm): {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([(int) $mm[1]]);
        $row = $stmt->fetch();
        if (!$row) send_error(404, 'NOT_FOUND');
        send_json(200, m2_product_row($row));
    }

    case $method === 'GET' && $rest === '/categories': {
        $rows = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
        send_json(200, ['data' => array_map(fn($r) => ['id' => (int) $r['id'], 'name' => $r['name']], $rows)]);
    }

    default:
        send_error(404, 'NOT_FOUND');
}
