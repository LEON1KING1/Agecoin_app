<?php
include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (admin users manage api): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (admin users manage api): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
} 


$user_id = isset($_REQUEST['q']) ? (int)$_REQUEST['q'] : 0;
$action = $_REQUEST['action'] ?? '';

// Admin token enforcement — fail-closed. Must set AGECOIN_ADMIN_TOKEN in the environment.
$adminToken = getenv('AGECOIN_ADMIN_TOKEN') ?: '';
if (empty($adminToken)) {
    error_log('Admin API rejected request: AGECOIN_ADMIN_TOKEN not configured');
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'admin token not configured']);
    $MySQLi->close();
    die;
}
$provided = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
if (!hash_equals($adminToken, $provided)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'unauthorized']);
    $MySQLi->close();
    die;
}

$allowed = ['banUser','unbanUser','changeUserScore','sendMessageToUser'];
if (!in_array($action, $allowed, true) || $user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'invalid request']);
    $MySQLi->close();
    die;
}

if($action === 'banUser'){
    $stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
    $step = 'banned';
    $stmt->bind_param('si', $step, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}

if($action === 'unbanUser'){
    $stmt = $MySQLi->prepare('UPDATE `users` SET `step` = ? WHERE `id` = ? LIMIT 1');
    $step = '';
    $stmt->bind_param('si', $step, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}

if($action === 'changeUserScore'){
    $newScore = isset($_REQUEST['newScore']) ? (int) $_REQUEST['newScore'] : null;
    if ($newScore === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'invalid score']);
        $MySQLi->close();
        die;
    }
    // bound the score to a sensible range
    $newScore = max(-1000000000, min(1000000000, $newScore));
    $stmt = $MySQLi->prepare('UPDATE `users` SET `score` = ? WHERE `id` = ? LIMIT 1');
    $stmt->bind_param('ii', $newScore, $user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
}

if($action === 'sendMessageToUser'){
    $text = substr((string)($_REQUEST['text'] ?? ''), 0, 4000);
    // avoid storing/forwarding HTML from admins without escaping
    $safe = $text; // LampStack will send raw; ensure admins are trusted or sanitize before send
    LampStack('sendMessage',[
        'chat_id' => $user_id,
        'text' => $safe,
        'parse_mode' => 'HTML',
    ]);
    echo json_encode(['success' => true]);
}



$MySQLi->close();