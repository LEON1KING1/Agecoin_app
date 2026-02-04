<?php


/*
Free SVG Codes : https://svgicons.sparkk.fr
*/


header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Security headers (safe sensible defaults; override per-endpoint if needed)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Referrer-Policy: no-referrer-when-downgrade");
header('Permissions-Policy: geolocation=(), microphone=()');
// Minimal CSP to prevent inline script injection where possible — keep permissive for webapp integration
header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline'; img-src 'self' data: https:; frame-ancestors 'self' https://telegram.org;");

date_default_timezone_set('Asia/Tehran');
// Enable safe error handling: log errors, don't display in production
$__agecoin_log_path = __DIR__ . '/../storage/logs/php-error.log';
@mkdir(dirname($__agecoin_log_path), 0750, true);
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', is_writable(dirname($__agecoin_log_path)) ? $__agecoin_log_path : 'syslog');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Allow AJAX requests from the configured web_app domain (if used by web UI)
if (!empty($web_app)) {
    $u = parse_url($web_app);
    if (!empty($u['scheme']) && !empty($u['host'])) {
        $origin = $u['scheme'] . '://' . $u['host'];
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
} 

// Load sensitive configuration from environment variables. Do NOT commit secrets to git.
$apiKey = getenv('AGECOIN_BOT_APIKEY') ?: '';
$botUsername = getenv('AGECOIN_BOT_USERNAME') ?: 'tgpo4bot';
$web_app = getenv('AGECOIN_WEB_APP') ?: 'https://cryptocoder.xyz';

$age_rewards = array(
    "1" => 1024,
    "2" => 2118,
    "3" => 2720,
    "4" => 3085,
    "5" => 4025,
    "6" => 6012,
    "7" => 8055,
    "8" => 1010,
    "9" => 13500,
    "10" => 16800,
    "11" => 20000,
);

$ref_percentage = (int) (getenv('AGECOIN_REF_PERCENT') ?: 35);

$DB = [
    'dbname'   => getenv('MYSQL_DBNAME') ?: '',
    'username' => getenv('MYSQL_USER') ?: '',
    'password' => getenv('MYSQL_PASS') ?: '',
];

// admins can be provided as comma-separated env var (e.g. "123,456")
$admins_user_id = array_filter(array_map('intval', explode(',', getenv('AGECOIN_ADMINS') ?: '')));

// In production enforce presence of critical config
if (getenv('REQUIRE_CONFIG') === '1') {
    if (empty($apiKey) || empty($DB['password']) || empty($DB['username']) || empty($DB['dbname'])) {
        error_log('Missing required environment configuration');
        http_response_code(500);
        die('Server misconfiguration');
    }
}

// Create runtime dirs (logs + cache) with safe permissions
$__agecoin_storage = __DIR__ . '/../storage';
@mkdir($__agecoin_storage, 0750, true);
$__agecoin_log_dir = $__agecoin_storage . '/logs';
@mkdir($__agecoin_log_dir, 0750, true);
$__agecoin_cache_dir = $__agecoin_storage . '/cache';
@mkdir($__agecoin_cache_dir, 0750, true);

// Helper: send strict security headers for HTTP requests (no-op in CLI)
function agecoin_send_security_headers(): void {
    if (php_sapi_name() === 'cli' || headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: interest-cohort=()');
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    header('X-XSS-Protection: 0');
}
// send headers automatically for web requests
agecoin_send_security_headers();

// NOTE: remove any hard-coded secrets from repository history and use environment variables instead.