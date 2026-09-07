<?php
declare(strict_types=1);

/**
 * ITCS333 Project starter — m2_catalog handler (module under construction).
 * Implement the endpoints in contracts/ for your module.
 * The routing context is in $GLOBALS['__route'] ('area', 'rest', 'uri').
 * Use db() from src/db.php and the helpers in src/http.php.
 */

$rest = $GLOBALS['__route']['rest'] ?? '';

// TODO: implement your module's endpoints here (see contracts/).

send_error(501, 'NOT_IMPLEMENTED', ['module' => 'm2_catalog', 'rest' => $rest]);
