<?php


/*
Free SVG Codes : https://svgicons.sparkk.fr
*/


header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

date_default_timezone_set('Asia/Tehran');
// Enable safe error handling: log errors, don't display in production, allow display in development
$__agecoin_log_path = __DIR__ . '/../storage/logs/php-error.log';
@mkdir(dirname($__agecoin_log_path), 0750, true);
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', is_writable(dirname($__agecoin_log_path)) ? $__agecoin_log_path : 'syslog');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

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

// NOTE: remove any hard-coded secrets from repository history and use environment variables instead.