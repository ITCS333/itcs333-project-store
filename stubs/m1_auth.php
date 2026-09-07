<?php
declare(strict_types=1);

/**
 * PROVIDED STUB — m1_auth reference implementation (contract-compliant).
 * Used by the autograder and by teammates developing against your module.
 */

$rest = $GLOBALS['__route']['rest'] ?? '';
$method = http_method();
$pdo = db();

/** Find a user row by username. */
function m1_find_user(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function m1_public_user(array $u): array
{
    return ['id' => (int) $u['id'], 'username' => $u['username'], 'is_admin' => (int) $u['is_admin']];
}

switch (true) {
    case $method === 'POST' && $rest === '/register': {
        $b = json_body();
        $username = trim((string) ($b['username'] ?? ''));
        $password = (string) ($b['password'] ?? '');
        $isAdmin = !empty($b['is_admin']) ? 1 : 0;
        $fields = [];
        if ($username === '') $fields['username'] = 'required';
        if ($password === '') $fields['password'] = 'required';
        if ($fields) send_error(422, 'VALIDATION', ['fields' => $fields]);
        if (m1_find_user($pdo, $username)) send_error(409, 'USERNAME_TAKEN');
        $id = db_insert_and_get_id($pdo, 'users', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_admin' => $isAdmin,
        ]);
        send_json(201, ['id' => $id, 'username' => $username, 'is_admin' => $isAdmin]);
    }

    case $method === 'POST' && $rest === '/login': {
        $b = json_body();
        $u = isset($b['username']) ? m1_find_user($pdo, (string) $b['username']) : null;
        if (!$u || !password_verify((string) ($b['password'] ?? ''), $u['password_hash'])) {
            send_error(401, 'INVALID_CREDENTIALS');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['username'] = $u['username'];
        send_json(200, m1_public_user($u) + ['token' => session_id()]);
    }

    case $method === 'POST' && $rest === '/logout': {
        $_SESSION = [];
        session_destroy();
        send_json(200, ['ok' => true]);
    }

    case $method === 'GET' && $rest === '/me': {
        if (empty($_SESSION['user_id'])) send_error(401, 'AUTH_REQUIRED');
        $stmt = $pdo->prepare('SELECT id, username, is_admin FROM users WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $u = $stmt->fetch();
        if (!$u) send_error(401, 'AUTH_REQUIRED');
        send_json(200, m1_public_user($u));
    }

    default:
        send_error(404, 'NOT_FOUND');
}
