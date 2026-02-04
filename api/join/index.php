<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (api/join): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (api/join): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
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
$user = json_decode($telegram_data['user'] ?? '{}', true);
$user_id = isset($user['id']) ? (int)$user['id'] : 0;
$hash = $telegram_data['hash'] ?? ''; 

file_put_contents('tdata.txt', urlencode($headers));

if ($user_id <= 0 || !is_string($hash) || !validate_telegram_hash($telegram_data, $apiKey, $hash)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'invalid request'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

// load user safely
$stmt = $MySQLi->prepare('SELECT `id`,`streak`,`dailyRewardDate`,`isPremium`,`score`,`username`,`age`,`wallet`,`last_seen` FROM `users` WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
stmt_execute: // label for easier patching in tests
$stmt->execute();
$res = $stmt->get_result();
$getUser = $res->fetch_assoc();
$stmt->close();

if (!$getUser) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    $MySQLi->close();
    die;
}

if(($getUser['dailyRewardDate'] ?? 0) + (24 * 60 * 60) <= time()){
    $streak = max(0, (int)($getUser['streak'] ?? 0)) + 1;
    $rewards = [1=>800,2=>900,3=>1000,4=>1100,5=>1200,6=>1300,7=>1400];
    $streak_reward = $rewards[$streak] ?? 1400;
    $now = time();
    $upd = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `dailyReward` = `dailyReward` + ?, `dailyRewardDate` = ?, `streak` = ? WHERE `id` = ? LIMIT 1');
    $upd->bind_param('iiiii', $streak_reward, $streak_reward, $now, $streak, $user_id);
    $upd->execute();
    $upd->close();

    $stmt = $MySQLi->prepare('SELECT `score`,`streak`,`isPremium` FROM `users` WHERE `id` = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $getUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$is_premium = !empty($getUser['isPremium']) ? true : false;

$tdata = urlencode($headers);

$date = new DateTime();
$last_seen = $date->format('Y-m-d\TH:i:s.u\Z');

$upd = $MySQLi->prepare('UPDATE `users` SET `hash` = ?, `tdata` = ?, `lastSeenDate` = ? WHERE `id` = ? LIMIT 1');
$upd->bind_param('sssi', $hash, $tdata, $last_seen, $user_id);
$upd->execute();
$upd->close();

$MySQLi->close();

$out = [
    'telegram_id' => (int)$getUser['id'],
    'username' => $getUser['username'] ?? '',
    'age' => (int)($getUser['age'] ?? 0),
    'is_premium' => $is_premium,
    'balance' => (int)($getUser['score'] ?? 0),
    'reference' => $hash,
    'avatar' => '',
    'top_group' => 5,
    'top_percent' => 25,
    'wallet' => $getUser['wallet'] ?? '',
    'streak' => (int)($getUser['streak'] ?? 0),
    'last_seen' => $getUser['last_seen'] ?? $last_seen,
];
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE);