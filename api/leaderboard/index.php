<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (api/leaderboard): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (api/leaderboard): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
} 


$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$stmt = $MySQLi->prepare('SELECT `id`,`score` FROM `users` WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$get_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$get_user){
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

// try file-cache first (short TTL)
$cacheKey = 'leaderboard_v1';
$cached = null;
if (function_exists('agecoin_cache_get')) {
    $cached = agecoin_cache_get($cacheKey);
}
if ($cached && is_array($cached)) {
    $users_list = $cached['users_list'];
    $cached_counts = $cached['counts'];
} else {
    $q = $MySQLi->prepare('SELECT `id`, `username`, `score` FROM users ORDER BY score DESC LIMIT 200');
    $q->execute();
    $users_list = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
    if (function_exists('agecoin_cache_set')) {
        agecoin_cache_set($cacheKey, ['users_list' => $users_list, 'counts' => count($users_list)], 25);
    }
}

// advise proxies & clients to cache this short-lived response
header('Cache-Control: public, max-age=20, s-maxage=60');

// compute rank efficiently using user's score (avoid correlated subquery)
$scoreForRank = (int)($get_user['score'] ?? 0);
$stmt = $MySQLi->prepare('SELECT COUNT(1) AS higher FROM users WHERE score > ?');
$stmt->bind_param('i', $scoreForRank);
$stmt->execute();
$user_rank = (int)($stmt->get_result()->fetch_assoc()['higher'] ?? 0) + 1;
$stmt->close(); 


$list = array();
$list['me']['position'] = (int) $user_rank;
$list['me']['score'] = $get_user['score'];


$c = 0;
foreach($users_list as $item){
    if ($c == 200) break;
    $list['board'][$c]["position"] = $c + 1;
    $list['board'][$c]["score"] = (int) $item['score'];
    $list['board'][$c]["telegram_id"] = $item['id'];
    $list['board'][$c]["username"] = $item['username'];
    $c++;
}
$list['count'] = $c;

$MySQLi->close();

echo json_encode($list);



// echo '{
//     "me": {
//         "position": 2,
//         "score": 3075
//     },
//     "board": [
//         {
//             "position": 0,
//             "score": 2846134,
//             "telegram_id": 6597594922,
//             "username": "crptanec"
//         },
//         {
//             "position": 1,
//             "score": 2035452,
//             "telegram_id": 6806722811,
//             "username": "DictatorImperium"
//         },
//         {
//             "position": 2,
//             "score": 1983350,
//             "telegram_id": 305094295,
//             "username": "ladesov"
//         },
//         {
//             "position": 3,
//             "score": 1788298,
//             "telegram_id": 1855193262,
//             "username": "MamelekatSup"
//         }
//     ],
//     "count": 4
// }';