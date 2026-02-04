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


$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$reference = $_REQUEST['reference'] ?? '';
if ($user_id <= 0 || !preg_match('/^[a-f0-9]{8,64}$/i', $reference)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    $MySQLi->close();
    die;
}

$stmt = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('is', $user_id, $reference);
$stmt->execute();
$res = $stmt->get_result();
$get_user = $res->fetch_assoc();
$stmt->close();

if(!$get_user){
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$stmt = $MySQLi->prepare('SELECT `id`, `username`, `age`, `isPremium` FROM `users` WHERE `inviterID` = ? LIMIT 500');
$stmt->bind_param('i', $get_user['id']);
$stmt->execute();
$get_referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if(!$get_referrals){
    echo json_encode(['frens' => [], 'count' => 0]);
    $MySQLi->close();
    die;
}


$referrals = array();

$c = 0;
foreach($get_referrals as $item){
    if ($c == 500) break;
    $score = $age_rewards[$item['age']];
    if($item['is_premium']) $score += 2500;
    $reward = $score * ($ref_percentage / 100);
    $referrals['frens'][$c]["reward"] = $reward;
    $referrals['frens'][$c]["telegram_id"] = (int) $item['id'];
    $referrals['frens'][$c]["username"] = $item['username'];
    $referrals['frens'][$c]["avatar"] = "";
    $c++;
}
$referrals['count'] = count($get_referrals);

$MySQLi->close();

echo json_encode($referrals);


// echo '{
//     "frens": [
//         {
//             "telegram_id": 123456789,
//             "reward": 154,
//             "username": "XXXXX",
//             "avatar": ""
//         }
//     ],
//     "count": 1
// }';