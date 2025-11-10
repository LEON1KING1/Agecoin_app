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
$reference = $_REQUEST['reference'];
$get_user = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' AND `hash` = '{$reference}' LIMIT 1"));

if(!$get_user){
    http_response_code(300);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$get_referrals = mysqli_fetch_all(mysqli_query($MySQLi, "SELECT `id`, `username`, `age`, `isPremium` FROM `users` WHERE `inviterID` = '{$get_user['id']}'"), MYSQLI_ASSOC);

if(!$get_referrals){
    echo '{"frens":[],"count":0}';
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