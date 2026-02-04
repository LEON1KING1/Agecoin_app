<?php

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) http_response_code(500) && die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}

// require JSON
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    http_response_code(415);
    echo json_encode(['ok' => false, 'message' => 'expected application/json']);
    ToDie($MySQLi);
}

$raw = file_get_contents('php://input');
$update = json_decode($raw);
if (!is_object($update)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid json']);
    ToDie($MySQLi);
}

// optional HMAC protection for webhooks (set WEBHOOK_SECRET in env to enable)
$webhook_secret = getenv('WEBHOOK_SECRET') ?: '';
if ($webhook_secret) {
    $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if (!hash_equals(hash_hmac('sha256', $raw, $webhook_secret), $sig)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'invalid signature']);
        ToDie($MySQLi);
    }
}

$payload = $update->proof->payload ?? '';
if (!is_string($payload) || !preg_match('/^[A-Za-z0-9\-]{8,128}$/', $payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid payload']);
    ToDie($MySQLi);
}

// lookup user safely
$stmt = $MySQLi->prepare('SELECT `id`,`walletReward` FROM `users` WHERE `walletOTP` = ? LIMIT 1');
$stmt->bind_param('s', $payload);
$stmt->execute();
$res = $stmt->get_result();
$get_user = $res->fetch_assoc();
$stmt->close();

if (!$get_user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    ToDie($MySQLi);
}

// validate wallet address format (expected like: chain:ADDRESS)
$address = $update->wallet->address ?? '';
if (!is_string($address) || !preg_match('/^[a-z0-9_\-]+:[A-Za-z0-9]{8,128}$/i', $address)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid wallet address']);
    ToDie($MySQLi);
}
$wallet = explode(':', $address, 2)[1];

// perform atomic update: set wallet, clear OTP, record connect time
$updateStmt = $MySQLi->prepare('UPDATE `users` SET `wallet` = ?, `walletOTP` = NULL, `walletConnectedAt` = UNIX_TIMESTAMP() WHERE `walletOTP` = ? LIMIT 1');
$updateStmt->bind_param('ss', $wallet, $payload);
$updateStmt->execute();
$updateStmt->close();

// award one-time wallet reward if not already given
if ((int)$get_user['walletReward'] === 0) {
    $award = 15000;
    $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `walletReward` = ? WHERE `id` = ? LIMIT 1');
    $awardStmt->bind_param('iii', $award, $award, $get_user['id']);
    $awardStmt->execute();
    $awardStmt->close();
}

$MySQLi->close();

http_response_code(200);
echo json_encode(['ok' => true, 'connected' => true]);