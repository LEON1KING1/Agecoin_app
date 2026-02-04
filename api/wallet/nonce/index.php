<?php

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}


$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$reference = $_REQUEST['reference'] ?? '';
if ($user_id <= 0 || !preg_match('/^[a-f0-9]{8,64}$/i', $reference)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    $MySQLi->close();
    die;
}

// simple rate-limit per user for OTP generation
$rlf = sys_get_temp_dir() . "/agecoin_wallet_otp_" . $user_id;
if (is_readable($rlf)){
    $t = (int)@file_get_contents($rlf);
    if ($t > time() - 30){
        http_response_code(429);
        echo json_encode(['ok' => false, 'message' => 'too many requests']);
        $MySQLi->close();
        die;
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
@file_put_contents($rlf, (string)time(), LOCK_EX);
$MySQLi->close();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => $randomCode]);