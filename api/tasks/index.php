<?php

// safer tasks list endpoint — validates input and uses prepared statements
include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) http_response_code(500) && die;

$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$reference = $_REQUEST['reference'] ?? '';
if ($user_id <= 0 || !preg_match('/^[a-f0-9]{8,64}$/i', $reference)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    $MySQLi->close();
    die;
}

// lookup user safely
$stmt = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('is', $user_id, $reference);
$stmt->execute();
$res = $stmt->get_result();
$get_user = $res->fetch_assoc();
$stmt->close();

if (!$get_user){
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$stmt = $MySQLi->prepare('SELECT `task_name` FROM `user_tasks` WHERE `user_id` = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$get_user_tasks = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$task_names = array_column($get_user_tasks, 'task_name');





$tasks = [];

$tasks[0]['id'] = 1;
$tasks[0]['slug'] = "invite-frens";
$tasks[0]['reward'] = 20000;
$tasks[0]['complete'] = false;

$tasks[1]['id'] = 2;
$tasks[1]['slug'] = "follow-age-x";
$tasks[1]['reward'] = 1000;
$tasks[1]['complete'] = false;

$tasks[2]['id'] = 3;
$tasks[2]['slug'] = "add-time-telegram";
$tasks[2]['reward'] = 2500;
$tasks[2]['complete'] = false;

$tasks[3]['id'] = 4;
$tasks[3]['slug'] = "good-age";
$tasks[3]['reward'] = 50;
$tasks[3]['complete'] = false;

$tasks[4]['id'] = 5;
$tasks[4]['slug'] = "subscribe-age-telegram";
$tasks[4]['reward'] = 50;
$tasks[4]['complete'] = false;





//          check tasks complete            //
$c = 0;
foreach($tasks as $item){
    if(in_array($item['slug'], $task_names)){
        $tasks[$c]['complete'] = true;
    }
    $c++;
}

$MySQLi->close();

echo json_encode($tasks);









// echo '[
//     {
//         "id": 3,
//         "slug": "invite-frens",
//         "reward": 20000,
//         "complete": false
//     },
//     {
//         "id": 2,
//         "slug": "follow-age-x",
//         "reward": 1000,
//         "complete": false
//     },
//     {
//         "id": 4,
//         "slug": "add-time-telegram",
//         "reward": 2500,
//         "complete": false
//     },
//     {
//         "id": 12,
//         "slug": "good-age",
//         "reward": 50,
//         "complete": false
//     },
//     {
//         "id": 16,
//         "slug": "subscribe-age-telegram",
//         "reward": 50,
//         "complete": false
//     }
// ]';