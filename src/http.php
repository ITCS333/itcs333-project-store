<?php
declare(strict_types=1);

/**
 * PROVIDED — do not modify.
 * Small JSON HTTP helpers shared by all modules.
 */

/** Send a JSON response and stop. */
function send_json(int $status, $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/** Send a JSON error envelope and stop. */
function send_error(int $status, string $code, array $extra = []): void
{
    send_json($status, array_merge(['error' => $code], $extra));
}

/** Read and decode the JSON request body (returns []). */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

/** The current request method. */
function http_method(): string
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/** A positive-int query/path id or null. */
function param_id(?string $raw): ?int
{
    if ($raw === null || $raw === '' || !is_numeric($raw)) {
        return null;
    }
    $id = (int) $raw;
    return $id > 0 ? $id : null;
}
