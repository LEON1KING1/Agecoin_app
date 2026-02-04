<?php
include ('./config.php');
include ('./functions.php');
ini_set('max_execution_time', 30);

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;


$getDB = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `sending` LIMIT 1"));
if(!$getDB){
    $MySQLi->close();
    die;
}

$offset = max(0, (int)($getDB['count'] ?? 0));
$batch = min(200, max(10, (int)($getDB['batch_size'] ?? 100))); // safe bounds

$stmt = $MySQLi->prepare('SELECT `id` FROM `users` LIMIT ? OFFSET ?');
$stmt->bind_param('ii', $batch, $offset);
$stmt->execute();
$getUsers = $stmt->get_result()->fetch_all(MYSQLI_NUM);
$stmt->close();

$plus = $offset + count($getUsers);
$upd = $MySQLi->prepare('UPDATE `sending` SET `count` = ? LIMIT 1');
$upd->bind_param('i', $plus);
$upd->execute();
$upd->close();

if(!in_array($getDB['type'], ['send','forward'], true)){
    // noop
} else {
    $from_chat = (int)($getDB['chat_id'] ?? 0);
    $msg_id = (int)($getDB['msg_id'] ?? 0);
    foreach($getUsers as $id){
        $to = (int)$id[0];
        if ($to <= 0) continue;
        if($getDB['type'] === 'send'){
            LampStack('copyMessage',[ 'chat_id' => $to, 'from_chat_id' => $from_chat, 'message_id' => $msg_id ]);
        } else {
            LampStack('ForwardMessage',[ 'chat_id' => $to, 'from_chat_id' => $from_chat, 'message_id' => $msg_id ]);
        }
        // small randomized backoff to reduce burst
        usleep(150000 + (int) (rand(0,50000)));
    }
}

$ToCheck = (int)$MySQLi->query("SELECT `id` FROM `users`")->num_rows;
if($plus >= $ToCheck){
    foreach($admins_user_id as $id){
        LampStack('sendmessage',['chat_id'=> (int)$id,'text'=> 'Send|Forward operation to all users successfully completed ✅',]);
        usleep(100000);
    }
    $del = $MySQLi->prepare("DELETE FROM `sending` WHERE `type` = 'send' OR `type` = 'forward'");
    $del->execute();
    $del->close();
}

$MySQLi->close();
die;