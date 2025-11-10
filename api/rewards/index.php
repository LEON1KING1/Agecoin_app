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

$user_id = $_REQUEST['user_id'];

$get_user = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' LIMIT 1"));

if(!$get_user){
    http_response_code(300);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}


if($get_user['isPremium'] == 1) $premium = 2500;
else $premium = 0;


if($get_user['walletReward'] != 0) $wallet = 15000;
else $wallet = 0;


$age = $age_rewards[$get_user['age']]?:0;


switch($get_user['streak']){
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


echo '{"total": '.$get_user['score'].',"age": '.$age.',"premium": '.$premium.',"frens": '.$get_user['fernsReward'].',"boost": 0,"connect": '.$wallet.',"daily": '.$get_user['dailyReward'].',"streak": '.$streak_reward.',"tasks": '.$get_user['tasksReward'].'}';


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