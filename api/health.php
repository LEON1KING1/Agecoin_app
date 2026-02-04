<?php
include '../bot/config.php';
include '../bot/functions.php';

header('Content-Type: application/json; charset=utf-8');
$ok = ['ok' => true, 'service' => 'AUR-GAME', 'version' => (file_exists(__DIR__ . '/../VERSION') ? trim(file_get_contents(__DIR__ . '/../VERSION')) : 'dev')];

// quick DB ping
$mysqli = @new mysqli('localhost', $DB['username'], $DB['password'], $DB['dbname']);
if ($mysqli->connect_error) {
    http_response_code(503);
    $ok['ok'] = false;
    $ok['db'] = 'down';
    echo json_encode($ok, JSON_UNESCAPED_UNICODE);
    exit;
}
$mysqli->close();

// basic rate-limit probe protection
if (function_exists('agecoin_rate_limited') && agecoin_rate_limited('health_' . ($_SERVER['REMOTE_ADDR'] ?? 'na'), 30, 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'rate limit']);
    exit;
}

echo json_encode($ok, JSON_UNESCAPED_UNICODE);
