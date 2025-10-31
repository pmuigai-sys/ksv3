<?php
declare(strict_types=1);

// Application configuration constants
define('KVS_APP_NAME', 'Kabarak Blockchain Voting System');
define('KVS_DB_PATH', __DIR__ . '/../../storage/database.sqlite');
define('KVS_BLOCK_DIFFICULTY', 3);
define('KVS_SESSION_NAME', 'kvs_session');
define('KVS_CSRF_TOKEN_KEY', 'kvs_csrf_token');

// Ensure storage directory exists
$storageDir = dirname(KVS_DB_PATH);
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(KVS_SESSION_NAME);
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
    ]);
}

/**
 * Generate or retrieve the CSRF token for the current session.
 */
function kvs_csrf_token(): string
{
    if (empty($_SESSION[KVS_CSRF_TOKEN_KEY])) {
        $_SESSION[KVS_CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[KVS_CSRF_TOKEN_KEY];
}

/**
 * Validate the CSRF token from a request.
 */
function kvs_validate_csrf(?string $token): bool
{
    return hash_equals($_SESSION[KVS_CSRF_TOKEN_KEY] ?? '', (string) $token);
}

