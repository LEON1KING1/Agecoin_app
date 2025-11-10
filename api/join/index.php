<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}

function validate_telegram_hash($telegram_data, $bot_token, $received_hash) {
    $data = [
        'auth_date' => $telegram_data['auth_date'],
        'query_id' => $telegram_data['query_id'],
        'user' => $telegram_data['user'],
    ];
    $data_check_string = '';
    ksort($data);
    foreach ($data as $key => $value) {
        $data_check_string .= "$key=$value\n";
    }
    $data_check_string = rtrim($data_check_string, "\n");
    $secret_key = hash_hmac('sha256', $bot_token, 'WebAppData', true);
    $computed_hash = hash_hmac('sha256', $data_check_string, $secret_key);
    return $computed_hash == $received_hash;
}

$headers = file_get_contents('php://input');

parse_str($headers, $telegram_data);
$user_id = json_decode($telegram_data['user'], true)['id'];
$hash = $telegram_data['hash'];

file_put_contents('tdata.txt', urlencode($headers));

if (!validate_telegram_hash($telegram_data, $apiKey, $hash)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'invalid request'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$getUser = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' LIMIT 1"));

if($getUser['step'] == 'banned'){
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'invalid request'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}


if($getUser['dailyRewardDate'] + (24 * 60 * 60) <= time()){
    $streak = $getUser['streak'] + 1;
    switch($streak){
        case 1:
            $streak_reward = 800;
        break;
        case 2:
            $streak_reward = 900;
        break;
        case 3:
            $streak_reward = 1000;
        break;
        case 4:
            $streak_reward = 1100;
        break;
        case 5:
            $streak_reward = 1200;
        break;
        case 6:
            $streak_reward = 1300;
        break;
        case 7:
            $streak_reward = 1400;
        break;
        default:
            $streak_reward = 1400;
    }
    $now = time();
    $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$streak_reward}', `dailyReward` = `dailyReward` + '{$streak_reward}', `dailyRewardDate` = '{$now}', `streak` = '{$streak}' WHERE `id` = '{$user_id}' LIMIT 1");
    $getUser = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' LIMIT 1"));
}

$is_premium = 'false';
if ($getUser['isPremium'] == 1) $is_premium = 'true';

$tdata = urlencode($headers);

$date = new DateTime();
$last_seen = $date->format('Y-m-d\TH:i:s.u\Z');

$MySQLi->query("UPDATE `users` SET `hash` = '{$hash}', `tdata` = '{$tdata}', `lastSeenDate` = '{$last_seen}' WHERE `id` = '{$user_id}' LIMIT 1");

$MySQLi->close();

echo '{
    "telegram_id": '.$getUser['id'].',
    "username": "'.$getUser['username'].'",
    "age": '.$getUser['age'].',
    "is_premium": '.$is_premium.',
    "balance": '.$getUser['score'].',
    "reference": "'.$hash.'",
    "avatar": "",
    "top_group": 5,
    "top_percent": 25,
    "wallet": "'.$getUser['wallet'].'",
    "streak": '.$getUser['streak'].',
    "last_seen": "'.$getUser['last_seen'].'"
}';