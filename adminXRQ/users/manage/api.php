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


$user_id = $_REQUEST['q'];
$action = $_REQUEST['action'];



if($action == 'banUser'){
    $MySQLi->query("UPDATE `users` SET `step` = 'banned' WHERE `id` = '{$user_id}' LIMIT 1");
    echo json_encode(['success' => true]);
}



if($action == 'unbanUser'){
    $MySQLi->query("UPDATE `users` SET `step` = '' WHERE `id` = '{$user_id}' LIMIT 1");
    echo json_encode(['success' => true]);
}



if($action == 'changeUserScore'){
    $newScore = $_REQUEST['newScore'];
    $MySQLi->query("UPDATE `users` SET `score` = '{$newScore}' WHERE `id` = '{$user_id}' LIMIT 1");
    echo json_encode(['success' => true]);
}



if($action == 'sendMessageToUser'){
    $text = $_REQUEST['text'];
    LampStack('sendMessage',[
        'chat_id' => $user_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ]);
    echo json_encode(['success' => true]);
}



$MySQLi->close();