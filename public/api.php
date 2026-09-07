<?php
declare(strict_types=1);

/**
 * PROVIDED — do not modify.
 * Front controller: routes /api/<area>/... to the active module handler.
 *
 * Which implementation serves each module is controlled by tests/use_stubs.sh
 * (writes tests/.active_modules with lines "module=real" or "module=stub").
 *   - real module:  modules/<module>/handler.php
 *   - stub module:  stubs/<module>.php
 */

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/http.php';

session_start();

/** Read tests/.active_modules (module => real|stub). Default: all real. */
function project_active_modules(): array
{
    $file = __DIR__ . '/../tests/.active_modules';
    $out = [];
    if (is_file($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, '=') !== false) {
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $out[$k] = $v;
            }
        }
    }
    return $out;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (!preg_match('#^/api/([a-z_]+)(/.*)?$#', $uri, $m)) {
    // Non-API: let the built-in server serve static files from public/.
    return false;
}

$area = $m[1];
$rest = $m[2] ?? '';

$map = [
    'auth'    => 'm1_auth',
    'catalog' => 'm2_catalog',
    'cart'    => 'm3_cart',
    'orders'  => 'm4_orders',
    'admin'   => 'm5_admin',
];

if (!isset($map[$area])) {
    send_error(404, 'NOT_FOUND');
}
$module = $map[$area];

$active = project_active_modules();
$mode = $active[$module] ?? 'real';
if ($mode === 'missing') {
    send_error(503, 'MODULE_UNAVAILABLE', ['module' => $module]);
}
$handler = $mode === 'stub'
    ? __DIR__ . '/../stubs/' . $module . '.php'
    : __DIR__ . '/../modules/' . $module . '/handler.php';

if (!file_exists($handler)) {
    send_error(503, 'MODULE_UNAVAILABLE', ['module' => $module]);
}

// Expose routing context to the handler.
$GLOBALS['__route'] = ['area' => $area, 'rest' => $rest, 'uri' => $uri, 'module' => $module];
require $handler;
exit;
