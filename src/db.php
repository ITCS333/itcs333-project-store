<?php
declare(strict_types=1);

/**
 * PROVIDED — do not modify.
 *
 * PDO connector for the Campus Marketplace project. Honors DB_DRIVER:
 *   sqlite (default, used by CI/grading) | mysql | pgsql (student dev)
 *
 * Write your module SQL in the PORTABLE subset (see README "Portable SQL"):
 *   lowercase snake_case identifiers, no ENUM / ON DUPLICATE KEY / backticks,
 *   ISO-8601 datetimes, INTEGER 0/1 booleans, and always prepared statements.
 * Use db_insert_and_get_id() instead of lastInsertId() (PgSQL differs).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $driver = getenv('DB_DRIVER') ?: 'sqlite';

    switch ($driver) {
        case 'mysql':
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $name = getenv('DB_NAME') ?: 'project';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            break;

        case 'pgsql':
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $name = getenv('DB_NAME') ?: 'project';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASS') ?: '';
            $pdo = new PDO("pgsql:host={$host};dbname={$name}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            break;

        case 'sqlite':
        default:
            $path = getenv('DB_PATH') ?: sys_get_temp_dir() . '/itcs333_project.sqlite';
            $fresh = !file_exists($path);
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            if ($fresh) {
                db_bootstrap($pdo);
            }
            break;
    }

    return $pdo;
}

/** Load schema + seed (used for sqlite bootstrap and by tests for all drivers). */
function db_bootstrap(PDO $pdo): void
{
    $root = dirname(__DIR__);
    // Disable FK enforcement while dropping/recreating (referenced tables may
    // be dropped in an order that would otherwise violate constraints).
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec(file_get_contents($root . '/schema.sql'));
    $pdo->exec(file_get_contents($root . '/seed.sql'));
    $pdo->exec('PRAGMA foreign_keys = ON');
}

/**
 * Insert helper that returns the new row id across drivers.
 * For PgSQL you should instead use `INSERT ... RETURNING id` directly.
 */
function db_insert_and_get_id(PDO $pdo, string $table, array $data): int
{
    $cols = array_keys($data);
    $place = array_fill(0, count($cols), '?');
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', $place)
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}
