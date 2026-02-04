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
$msg = $chat_id = $from_id = $first_name = $last_name = $username = $is_premium = $language_code = $chat_type = $message_id = $reply_message_id = null;
if (isset($update->message) && is_object($update->message)) {
    $msg = isset($update->message->text) ? (string)$update->message->text : '';
    $chat_id = isset($update->message->chat->id) ? (int)$update->message->chat->id : null;
    $from_id = isset($update->message->from->id) ? (int)$update->message->from->id : null;
    $first_name = $update->message->from->first_name ?? null;
    $last_name = $update->message->from->last_name ?? null;
    $username = $update->message->from->username ?? null;
    $is_premium = !empty($update->message->from->is_premium) ? 1 : 0;
    $language_code = $update->message->from->language_code ?? 'en';
    $chat_type = $update->message->chat->type ?? null;
    $message_id = isset($update->message->message_id) ? (int)$update->message->message_id : null;
    $reply_message_id = isset($update->message->reply_to_message->message_id) ? (int)$update->message->reply_to_message->message_id : null;
} 


if($chat_type !== 'private'){
$MySQLi->close();
die;
}



if(explode(' ', $msg)[0] === '/start' and is_numeric(explode(' ', $msg)[1]) and !isset(explode(' ', $msg)[2])){
    $inviter_id = (int) explode(' ', $msg)[1];

    if($inviter_id === $from_id){
        LampStack('sendMessage',[
            'chat_id' => $from_id,
            'text' => '<b>You cannot invite yourself !</b>',
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id,
        ]);
        $MySQLi->close();
        die;
    }

    // prepared lookup for inviter
    $s = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? LIMIT 1');
    $s->bind_param('i', $inviter_id);
    $s->execute();
    $inv = $s->get_result()->fetch_assoc();
    $s->close();

    if(!$inv){
        LampStack('sendMessage',[
            'chat_id' => $from_id,
            'text' => '<b>User not found !</b>',
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id,
        ]);
        $MySQLi->close();
        die;
    }

    $s = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? LIMIT 1');
    $s->bind_param('i', $from_id);
    $s->execute();
    $exists = $s->get_result()->fetch_assoc();
    $s->close();

    if($exists){
        LampStack('sendMessage',[
            'chat_id' => $from_id,
            'text' => '<b>You were already a member of the bot and you cannot be invited to the bot by anyone !</b>',
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $message_id,
        ]);
        $MySQLi->close();
        die;
    }

    // basic anti-abuse: prevent mass automated invites (per inviter)
    if (function_exists('agecoin_rate_limited')) {
        if (agecoin_rate_limited('invite_' . $inviter_id, 30, 3600)) {
            LampStack('sendMessage',['chat_id' => $from_id, 'text' => '<b>Invite rate limit</b>', 'parse_mode' => 'HTML']);
            $MySQLi->close();
            exit;
        }
    } else {
        // fallback to file-based limiter
        $rlf = sys_get_temp_dir() . "/agecoin_invite_" . $inviter_id;
        $now = time();
        $invites = [];
        if (is_readable($rlf)) {
            $raw = file_get_contents($rlf);
            $invites = $raw ? json_decode($raw, true) : [];
            if (!is_array($invites)) $invites = [];
            $invites = array_filter($invites, function($t) use($now){ return ($t > $now - 3600); });
        }
        if (count($invites) > 30) {
            LampStack('sendMessage',['chat_id' => $from_id, 'text' => '<b>Invite rate limit</b>', 'parse_mode' => 'HTML']);
            $MySQLi->close();
            exit;
        }

        $invites[] = $now;
        if (file_put_contents($rlf, json_encode($invites), LOCK_EX) === false) {
            error_log('Failed to write invite-rate file: ' . $rlf);
        }
    }

    $time = time();
    $age = GetAge($from_id);
    $score = $age_rewards[$age] ?? 0;
    $is_premium_int = $is_premium ? 1 : 0;

    // use prepared statements for insert + inviter update in a transaction
    $MySQLi->begin_transaction();
    $ins = $MySQLi->prepare('INSERT INTO `users` (`id`,`firstName`,`lastName`,`username`,`language`,`joinDate`,`isPremium`,`age`,`score`,`inviterID`) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $ins->bind_param('isssiiiiii', $from_id, $first_name, $last_name, $username, $language_code, $time, $is_premium_int, $age, $score, $inviter_id);
    $ins->execute();
    $ins->close();

    $inviterRewards = (int) floor($score * ($ref_percentage / 100));
    $upd = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `fernsReward` = `fernsReward` + ?, `referrals` = `referrals` + 1 WHERE `id` = ? LIMIT 1');
    $upd->bind_param('iii', $inviterRewards, $inviterRewards, $inviter_id);
    $upd->execute();
    $upd->close();

    $MySQLi->commit();

    $invited_name = htmlspecialchars($first_name ?: '');
    LampStack('sendMessage',[
        'chat_id' => $inviter_id,
        'text' => "congratulations 🌱\n<b>{$invited_name}</b> joined the bot by your link",
        'parse_mode' => 'HTML',
    ]);

    LampStack('sendPhoto',[
        'chat_id' => $from_id,
        'photo' => new CURLFILE('home.jpg'),
        'caption' => '...'
    ]);

    $MySQLi->close();
    die;
}




$UserDataBase = null;
$s = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? LIMIT 1');
$s->bind_param('i', $from_id);
$s->execute();
$UserDataBase = $s->get_result()->fetch_assoc();
$s->close();
if(!$UserDataBase){
    $time = time();
    $age = GetAge($from_id);
    $score = $age_rewards[$age] ?? 0;
    $is_premium_int = $is_premium ? 1 : 0;
    $ins = $MySQLi->prepare('INSERT INTO `users` (`id`,`firstName`,`lastName`,`username`,`language`,`joinDate`,`isPremium`,`age`,`score`) VALUES (?,?,?,?,?,?,?,?,?)');
    $ins->bind_param('isssiiiii', $from_id, $first_name, $last_name, $username, $language_code, $time, $is_premium_int, $age, $score);
    $ins->execute();
    $ins->close();
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
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = NULL WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $from_id);
$stmt->execute();
$stmt->close();
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
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = NULL WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $from_id);
$stmt->execute();
$stmt->close();
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
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
$stepVal = 'SendToAll';
$stmt->bind_param('si', $stepVal, $from_id);
$stmt->execute();
$stmt->close();
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

if(isset($update->message) and ($UserDataBase['step'] ?? '') === 'SendToAll'){
    $stmt = $MySQLi->prepare("UPDATE `users` SET `step` = NULL WHERE `id` = ? LIMIT 1");
    $stmt->bind_param('i', $from_id);
    $stmt->execute();
    $stmt->close();

    // clear pending sending jobs and create new safely
    $MySQLi->query("DELETE FROM `sending` WHERE `type` = 'send' OR `type` = 'forward'");
    $ins = $MySQLi->prepare('INSERT INTO `sending` (`type`,`chat_id`,`msg_id`,`count`) VALUES (?,?,?,?)');
    $type = 'send';
    $zero = 0;
    $ins->bind_param('siii', $type, $from_id, $message_id, $zero);
    $ins->execute();
    $ins->close();

    LampStack('sendMessage',[
        'chat_id' => $from_id,
        'text' => '<b>Public sending operation has started.✅</b>\n\n<u>Please send|forward  any message until the end of the operation❗️</u>',
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id,
        'reply_markup' => $panel_menu
    ]);
    $MySQLi->close();
    die;
} 


//			Forward Message To All			//
if($msg === 'Forward Message'){
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
$stepVal = 'ForToAll';
$stmt->bind_param('si', $stepVal, $from_id);
$stmt->execute();
$stmt->close();
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

if(isset($update->message) and ($UserDataBase['step'] ?? '') === 'ForToAll'){
    $stmt = $MySQLi->prepare("UPDATE `users` SET `step` = NULL WHERE `id` = ? LIMIT 1");
    $stmt->bind_param('i', $from_id);
    $stmt->execute();
    $stmt->close();

    $MySQLi->query("DELETE FROM `sending` WHERE `type` = 'send' OR `type` = 'forward'");
    $ins = $MySQLi->prepare('INSERT INTO `sending` (`type`,`chat_id`,`msg_id`,`count`) VALUES (?,?,?,?)');
    $type = 'forward';
    $zero = 0;
    $ins->bind_param('siii', $type, $from_id, $message_id, $zero);
    $ins->execute();
    $ins->close();

    LampStack('sendMessage',[
        'chat_id' => $from_id,
        'text' => '<b>Public forwarding operation has started.✅</b>\n\n<u>Please send|forward  any message until the end of the operation❗️</u>',
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id,
        'reply_markup' => $panel_menu
    ]);
    $MySQLi->close();
    die;
}


//			Turn On Maintenance			//
if($msg === 'Turn On Maintenance'){
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
$stepVal = 'GetMaintenanceTime';
$stmt->bind_param('si', $stepVal, $from_id);
$stmt->execute();
$stmt->close();
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
$stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
$empty = '';
$stmt->bind_param('si', $empty, $from_id);
$stmt->execute();
$stmt->close();
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