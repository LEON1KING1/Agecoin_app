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

$task = $_REQUEST['task'];
$user_id = $_REQUEST['user_id'];
$reference = $_REQUEST['reference'];

$get_user = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' AND `hash` = '{$reference}' LIMIT 1"));

if(!$get_user){
    http_response_code(300);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$now = time();

switch($task){
    case 'good-age':
        $task_name = 'good-age';
        $reward = 50;
        $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
        $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
        $isOK = true;
    break;

    case 'follow-age-x':
        $task_name = 'follow-age-x';
        $reward = 1000;
        $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
        $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
        $isOK = true;
    break;

    case 'invite-frens':
        $task_name = 'invite-frens';
        $reward = 20000;
        $get_referrals = mysqli_fetch_all(mysqli_query($MySQLi, "SELECT `id` FROM `users` WHERE `inviter_id` = '{$user_id}' LIMIT 10"));
        if(count($get_referrals) >= 5){
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
    break;

    case 'add-time-telegram':
        $task_name = 'add-time-telegram';
        $reward = 2500;
        $name = json_decode(file_get_contents('https://api.telegram.org/bot'.$apiKey.'/getchat?chat_id='.$user_id), true)['result']['first_name'];
        if (strpos($name, '⏳') !== false) {
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
    break;

    case 'subscribe-age-telegram':
        $task_name = 'subscribe-age-telegram';
        $reward = 50;
        $result = json_decode(file_get_contents('https://api.telegram.org/bot'.$apiKey.'/getChatMember?chat_id=-1001478594200&user_id='.$user_id));
        if($result->ok and in_array($result->result->status, ['member', 'administrator'])){
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
    break;

    default:
        $isOK = false;
}



$MySQLi->close();


if($isOK) echo '{"success":true}';
else echo '{"success":false}';