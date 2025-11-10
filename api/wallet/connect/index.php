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


$update = json_decode(file_get_contents('php://input'));
file_put_contents('a.txt', json_encode($update));

$payload = $update->proof->payload;

$get_user = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `walletOTP` = '{$payload}' LIMIT 1"));

if(!$get_user){
    http_response_code(300);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$wallet = explode(':', $update->wallet->address)[1];

$MySQLi->query("UPDATE `users` SET `wallet` = '{$wallet}' WHERE `walletOTP` = '{$payload}' LIMIT 1");

if($get_user['walletReward'] == 0){
    $MySQLi->query("UPDATE `users` SET `score` = `score` + 15000, `walletReward` = 15000 WHERE `walletOTP` = '{$payload}' LIMIT 1");
}

$MySQLi->close();

echo '{"connected":false,"error_code":"already connected"}';