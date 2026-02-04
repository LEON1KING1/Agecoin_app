<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (api/rewards): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (api/rewards): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
}

$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    $MySQLi->close();
    die;
}

$stmt = $MySQLi->prepare('SELECT `score`,`isPremium`,`walletReward`,`age`,`streak`,`fernsReward`,`dailyReward`,`tasksReward` FROM `users` WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$get_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$get_user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    $MySQLi->close();
    die;
}

$premium = ((int)($get_user['isPremium'] ?? 0) === 1) ? 2500 : 0;
$wallet = ((int)($get_user['walletReward'] ?? 0) !== 0) ? 15000 : 0;
$age = $age_rewards[(string)($get_user['age'] ?? '')] ?? 0;

$streak = (int)($get_user['streak'] ?? 0);
$rewards = [1=>800,2=>900,3=>1000,4=>1100,5=>1200,6=>1300,7=>1400];
$streak_reward = $rewards[$streak] ?? 1400;

$out = [
    'total' => (int)($get_user['score'] ?? 0),
    'age' => $age,
    'premium' => $premium,
    'frens' => (int)($get_user['fernsReward'] ?? 0),
    'boost' => 0,
    'connect' => $wallet,
    'daily' => (int)($get_user['dailyReward'] ?? 0),
    'streak' => $streak_reward,
    'tasks' => (int)($get_user['tasksReward'] ?? 0),
];
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE);

$MySQLi->close();



// echo '{
//     "total": 3573,
//     "age": 3573,
//     "premium": 0,
//     "frens": 0,
//     "boost": 0,
//     "connect": 0,
//     "daily": 0,
//     "streak": 200,
//     "tasks": 0
// }';