<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (admin users api): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database connection failed']);
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (admin users api): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal server error']);
    exit;
} 

$q = $_REQUEST['q'] ?? '';
// limit input length
$q = mb_substr((string)$q, 0, 64);

// if numeric -> lookup by id, otherwise perform a safe LIKE search
if (ctype_digit($q)) {
    $id = (int)$q;
    $stmt = $MySQLi->prepare('SELECT * FROM `users` WHERE `id` = ? LIMIT 30');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $get_all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $like = '%' . $q . '%';
    $stmt = $MySQLi->prepare('SELECT * FROM `users` WHERE `firstName` LIKE ? OR `lastName` LIKE ? OR `username` LIKE ? LIMIT 30');
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $get_all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$MySQLi->close();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($get_all, JSON_UNESCAPED_UNICODE);