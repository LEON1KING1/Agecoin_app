<?php
include './config.php';
include './functions.php';


$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;
function ToDie($MySQLi){
$MySQLi->close();
die;
}



$update = json_decode(file_get_contents('php://input'));
if(isset($update->message)) {
@$msg = $update->message->text;
@$chat_id = $update->message->chat->id;
@$from_id = $update->message->from->id;
@$first_name = $update->message->from->first_name;
@$last_name = $update->message->from->last_name?:null;
@$username = $update->message->from->username?:null;
@$is_premium = $update->message->from->is_premium;
@$language_code = $update->message->from->language_code?:'en';
@$chat_type = $update->message->chat->type;
@$message_id = $update->message->message_id;
@$reply_message_id = $update->message->reply_to_message->message_id?:null;
}


if($chat_type !== 'private'){
$MySQLi->close();
die;
}



if(explode(' ', $msg)[0] === '/start' and is_numeric(explode(' ', $msg)[1]) and !isset(explode(' ', $msg)[2])){
$inviter_id = explode(' ', $msg)[1];

if($inviter_id == $from_id){
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>You cannot invite yourself !</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
]);
$MySQLi->close();
die;
}

$InviterDataBase = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$inviter_id}' LIMIT 1"));
if(!$InviterDataBase){
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>User not found !</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
]);
$MySQLi->close();
die;
}

$UserDataBase = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$from_id}' LIMIT 1"));
if($UserDataBase){
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>You were already a member of the bot and you cannot be invited to the bot by anyone !</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
]);
$MySQLi->close();
die;
}


$time = time();
$age = GetAge($from_id);
$score = $age_rewards[$age];
if($is_premium) $score += 2500;
$MySQLi->query("INSERT INTO `users` (`id`, `firstName`, `lastName`, `username`, `language`, `joinDate`, `isPremium`, `age`, `score`, `inviterID`) VALUES ('{$from_id}', '{$first_name}', '{$last_name}', '{$username}', '{$language_code}', '{$time}', '{$is_premium}', '{$age}', '{$score}', '{$inviter_id}')");

$inviterRewards = $score * ($ref_percentage / 100);
$MySQLi->query("UPDATE `users` SET `score` = `score` + '{$inviterRewards}', `fernsReward` = `fernsReward` + '{$inviterRewards}', `referrals` = `referrals` + 1 WHERE `id` = '{$inviter_id}' LIMIT 1");

$invited_name = str_replace(['<', '>', '&'], ['&lt;', '&gt;', '&amp;'], $first_name);
LampStack('sendMessage',[
'chat_id' => $inviter_id,
'text' => "congratulations 🌱
<b>$invited_name</b> joined the bot by your link",
'parse_mode' => 'HTML',
]);

LampStack('sendPhoto',[
'chat_id' => $from_id,
'photo' => new CURLFILE('home.jpg'),
'caption' => '
Hey! Welcome to <b>AgeCoin</b>!

This is an airdrop on the <b>TON</b> chain
Get coins based on the <b>age of your Telegram account</b>
Follow <u>daily activities</u> to get more rewards

Got friends, relatives, co-workers?
Bring them all into the game.
More buddies, more coins.

[This Bot Is For Sell] 👇🏻

Developer : @codercyan
',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'inline_keyboard' => [
[['text' => 'Telegram Channel', 'url' => 'https://t.me/codercyan'], ['text' => 'Twitter', 'url' => 'https://t.me/codercyan']],
[['text' => 'Play Now', 'web_app' => ['url' => $web_app]]],
]
])
]);

$MySQLi->close();
die;
}




$UserDataBase = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$from_id}' LIMIT 1"));
if(!$UserDataBase){
$time = time();
$age = GetAge($from_id);
$score = $age_rewards[$age];
if($is_premium) $score += 2500;
$MySQLi->query("INSERT INTO `users` (`id`, `firstName`, `lastName`, `username`, `language`, `joinDate`, `isPremium`, `age`, `score`) VALUES ('{$from_id}', '{$first_name}', '{$last_name}', '{$username}', '{$language_code}', '{$time}', '{$is_premium}', '{$age}', '{$score}')");
}


if($UserDataBase['step'] == 'banned'){
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>You Are Banned From The Bot.</b>',
'parse_mode' => 'HTML',
'reply_markup'=>json_encode(['KeyboardRemove'=>[
],'remove_keyboard'=>true
])
]);
$MySQLi->close();
die;
}


if($msg === '/start'){
LampStack('sendPhoto',[
'chat_id' => $from_id,
'photo' => new CURLFILE('home.jpg'),
'caption' => '
Hey! Welcome to <b>AgeCoin</b>!

This is an airdrop on the <b>TON</b> chain
Get coins based on the <b>age of your Telegram account</b>
Follow <u>daily activities</u> to get more rewards

Got friends, relatives, co-workers?
Bring them all into the game.
More buddies, more coins.

[This Bot Is For Sell] 👇🏻

Developer : @codercyan
',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'inline_keyboard' => [
[['text' => 'Telegram Channel', 'url' => 'https://t.me/codercyan'], ['text' => 'Twitter', 'url' => 'https://t.me/codercyan']],
[['text' => 'Play Now', 'web_app' => ['url' => $web_app]]],
]
])
]);
$MySQLi->close();
die;
}

if($msg === 'Back To User Mode ↪️'){
$MySQLi->query("UPDATE `users` SET `step` = null WHERE `id` = '{$from_id}' LIMIT 1");
$message_id_temp = LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>...</b>',
'parse_mode' => 'HTML',
'reply_markup'=>json_encode(['KeyboardRemove'=>[
],'remove_keyboard'=>true
])
])->result->message_id;
LampStack('deleteMessage',[
'chat_id' => $from_id,
'message_id' => $message_id_temp,
]);
LampStack('sendPhoto',[
'chat_id' => $from_id,
'photo' => new CURLFILE('home.jpg'),
'caption' => '
Hey! Welcome to <b>AgeCoin</b>!

This is an airdrop on the <b>TON</b> chain
Get coins based on the <b>age of your Telegram account</b>
Follow <u>daily activities</u> to get more rewards

Got friends, relatives, co-workers?
Bring them all into the game.
More buddies, more coins.

[This Bot Is For Sell] 👇🏻

Developer : @codercyan
',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'inline_keyboard' => [
[['text' => 'Telegram Channel', 'url' => 'https://t.me/codercyan'], ['text' => 'Twitter', 'url' => 'https://t.me/codercyan']],
[['text' => 'Play Now', 'web_app' => ['url' => $web_app]]],
]
])
]);
$MySQLi->close();
die;
}







//          admin           //

if(!in_array($from_id, $admins_user_id)){
$MySQLi->close();
die;
}


$panel_menu = json_encode([
'resize_keyboard' => true,
'keyboard' => [
[['text' => 'Statistics', 'web_app' => ['url' => $web_app . '/adminXRQ/statics/']], ['text' => 'User Managment', 'web_app' => ['url' => $web_app . '/adminXRQ/users/']]],
[['text' => 'BackUP']],
[['text' => 'Send Message'],['text' => 'Forward Message']],
[['text' => 'Turn On Maintenance'],['text' => 'Turn Off Maintenance']],
[['text' => 'Back To User Mode ↪️']],
]
]);



//			admin panel			//
if($msg === '/admin' or $msg === '🔙'){
$MySQLi->query("UPDATE `users` SET `step` = null WHERE `id` = '{$from_id}' LIMIT 1");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>- welcome to admin menu :</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => $panel_menu
]);
$MySQLi->close();
die;
}


//			backup database			//
if($msg === 'BackUP'){

$sendMessage = LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '⏳',
'reply_to_message_id' => $message_id,
]);
dbBackup('localhost', $DB['username'], $DB['password'], $DB['dbname'], 'SQLbackUp');
$filesize = filesize('SQLbackUp.sql');
LampStack('deleteMessage',[
'chat_id' => $from_id,
'message_id' => $sendMessage->result->message_id,
]);
if(round($filesize / 1024 / 1024) > 19){
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>The size of the bot database is more than 20 MB and I cant send it to you

Please take a backup of the database manually through the host.</b>',
'reply_to_message_id' => $message_id,
]);
}else{
LampStack('sendDocument',[
'chat_id' => $from_id,
'document' => new curlFile('SQLbackUp.sql'),
'caption' => "<b>The bot database backup was created successfully ✅</b>",
'reply_to_message_id' => $message_id,
'parse_mode' => "HTML",
]);
}
unlink('SQLbackUp.sql');

$MySQLi->close();
die;
}


//			Send Message To All			//
if($msg === 'Send Message'){
$MySQLi->query("UPDATE `users` SET `step` = 'SendToAll' WHERE `id` = '{$from_id}' LIMIT 1");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Send a message to be sent to all users of the bot :</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'resize_keyboard' => true,
'keyboard' => [
[['text' => '🔙']],
]
])
]);
$MySQLi->close();
die;
}

if(isset($update->message) and $UserDataBase['step'] === 'SendToAll'){
$MySQLi->query("UPDATE `users` SET `step` = null WHERE `id` = '{$from_id}' LIMIT 1");
@$MySQLi->query("DELETE FROM `sending` WHERE `type` = 'send' OR `type` = 'forward'");
$MySQLi->query("INSERT INTO `sending` (`type`,`chat_id`,`msg_id`,`count`) VALUES ('send','{$from_id}','{$message_id}',0)");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Public sending operation has started.✅</b>

<u>Please send|forward  any message until the end of the operation❗️</u>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => $panel_menu
]);
$MySQLi->close();
die;
}


//			Forward Message To All			//
if($msg === 'Forward Message'){
$MySQLi->query("UPDATE `users` SET `step` = 'ForToAll' WHERE `id` = '{$from_id}' LIMIT 1");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Forward a message to be forward to all users of the bot :</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'resize_keyboard' => true,
'keyboard' => [
[['text' => '🔙']],
]
])
]);
$MySQLi->close();
die;
}

if(isset($update->message) and $UserDataBase['step'] === 'ForToAll'){
$MySQLi->query("UPDATE `users` SET `step` = null WHERE `id` = '{$from_id}' LIMIT 1");
@$MySQLi->query("DELETE FROM `sending` WHERE `type` = 'send' OR `type` = 'forward'");
$MySQLi->query("INSERT INTO `sending` (`type`,`chat_id`,`msg_id`,`count`) VALUES ('forward','{$from_id}','{$message_id}',0)");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Public forwarding operation has started.✅</b>

<u>Please send|forward  any message until the end of the operation❗️</u>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => $panel_menu
]);
$MySQLi->close();
die;
}


//			Turn On Maintenance			//
if($msg === 'Turn On Maintenance'){
$MySQLi->query("UPDATE `users` SET `step` = 'GetMaintenanceTime' WHERE `id` = '{$from_id}' LIMIT 1");
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Please give me a time to be on maintenance mode in minute :</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => json_encode([
'resize_keyboard' => true,
'keyboard' => [
[['text' => '🔙']],
]
])
]);
$MySQLi->close();
die;
}

if(is_numeric($msg) and $UserDataBase['step'] === 'GetMaintenanceTime'){
$MySQLi->query("UPDATE `users` SET `step` = '' WHERE `id` = '{$from_id}' LIMIT 1");
$time = round((microtime(true) * 1000) + ($msg * 60 * 1000));
file_put_contents('.maintenance.txt', $time);
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Maintenance mode activated ✅</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => $panel_menu
]);
$MySQLi->close();
die;
}


//			Turn Off Maintenance			//
if($msg === 'Turn Off Maintenance'){
unlink('.maintenance.txt');
LampStack('sendMessage',[
'chat_id' => $from_id,
'text' => '<b>Maintenance mode deactivated ✅</b>',
'parse_mode' => 'HTML',
'reply_to_message_id' => $message_id,
'reply_markup' => $panel_menu
]);
$MySQLi->close();
die;
}




























$MySQLi->close();
die;