<?php

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (api/wallet/nonce): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (api/wallet/nonce): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
}


$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$reference = $_REQUEST['reference'] ?? '';
if ($user_id <= 0 || !preg_match('/^[a-f0-9]{8,64}$/i', $reference)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    $MySQLi->close();
    exit;
}

// simple rate-limit per user for OTP generation
$rlf = sys_get_temp_dir() . "/agecoin_wallet_otp_" . $user_id;
if (is_readable($rlf)){
    $raw = file_get_contents($rlf);
    $t = $raw ? (int)$raw : 0;
    if ($t > time() - 30){
        http_response_code(429);
        echo json_encode(['ok' => false, 'message' => 'too many requests']);
        $MySQLi->close();
        exit;
    }
}

$stmt = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('is', $user_id, $reference);
$stmt->execute();
$res = $stmt->get_result();
$get_user = $res->fetch_assoc();
$stmt->close();

if(!$get_user){
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    $MySQLi->close();
    die;
}

$randomCode = generateRandomCode();
$stmt = $MySQLi->prepare('UPDATE `users` SET `walletOTP` = ? WHERE `id` = ? LIMIT 1');
$stmt->bind_param('si', $randomCode, $user_id);
$stmt->execute();
$stmt->close();
if (file_put_contents($rlf, (string)time(), LOCK_EX) === false) {
    error_log('Failed to write wallet OTP lock: ' . $rlf);
}
$MySQLi->close();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => $randomCode]);