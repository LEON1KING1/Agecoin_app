<?php

// Secure task verification endpoint
// - prepared statements
// - input validation
// - duplicate-claim prevention
// - simple rate-limiting
// - transactional updates to avoid race conditions

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) http_response_code(500) && die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}

// simple file-based rate limiter (per user)
function rate_limited($key, $limit = 6, $window = 60){
    $f = sys_get_temp_dir() . "/agecoin_rl_" . preg_replace('/[^a-z0-9_\-]/i','', (string)$key);
    $now = time();
    $data = [];
    if (is_readable($f)) {
        $raw = file_get_contents($f);
        $data = $raw ? json_decode($raw, true) : [];
        if (!is_array($data)) $data = [];
        $data = array_filter($data, function($t) use($now, $window){ return ($t > $now - $window); });
    }
    if (count($data) >= $limit) return true;
    $data[] = $now;
    if (file_put_contents($f, json_encode($data), LOCK_EX) === false) {
        error_log('Failed to write rate-limit file: ' . $f);
    }
    return false;
}

$task = $_REQUEST['task'] ?? '';
$user_id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
$reference = $_REQUEST['reference'] ?? '';

// basic validation
$allowed = ['good-age','follow-age-x','invite-frens','add-time-telegram','subscribe-age-telegram'];
if (!in_array($task, $allowed, true) || $user_id <= 0 || !preg_match('/^[a-f0-9]{8,64}$/i', $reference)){
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    ToDie($MySQLi);
}

// rate-limit by user and IP
if (rate_limited('task_verify_user_' . $user_id, 8, 60) || rate_limited('task_verify_ip_' . ($_SERVER['REMOTE_ADDR'] ?? 'na'), 30, 60)){
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'rate limit']);
    ToDie($MySQLi);
}

// lookup user safely
$stmt = $MySQLi->prepare('SELECT `id`,`walletReward` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('is', $user_id, $reference);
$stmt->execute();
$res = $stmt->get_result();
$get_user = $res->fetch_assoc();
$stmt->close();

if (!$get_user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    ToDie($MySQLi);
}

// prevent duplicate claims
$checkStmt = $MySQLi->prepare('SELECT 1 FROM `user_tasks` WHERE `user_id` = ? AND `task_name` = ? LIMIT 1');
$checkStmt->bind_param('is', $user_id, $task);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
if ($checkRes->fetch_row()){
    // idempotent: already completed
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'already completed']);
    $checkStmt->close();
    ToDie($MySQLi);
}
$checkStmt->close();

$now = time();

// perform task-specific checks and a transactional award
$MySQLi->begin_transaction();
$awarded = false;
try {
    switch($task){
        case 'good-age':
            $reward = 50;
            $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
            $awardStmt->bind_param('iii', $reward, $reward, $user_id);
            $awardStmt->execute();
            $awardStmt->close();
            $insert = $MySQLi->prepare('INSERT INTO `user_tasks` (`user_id`,`task_name`,`check_time`) VALUES (?, ?, ?)');
            $insert->bind_param('isi', $user_id, $task, $now);
            $insert->execute();
            $insert->close();
            $awarded = true;
        break;

        case 'follow-age-x':
            $reward = 1000;
            $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
            $awardStmt->bind_param('iii', $reward, $reward, $user_id);
            $awardStmt->execute();
            $awardStmt->close();
            $insert = $MySQLi->prepare('INSERT INTO `user_tasks` (`user_id`,`task_name`,`check_time`) VALUES (?, ?, ?)');
            $insert->bind_param('isi', $user_id, $task, $now);
            $insert->execute();
            $insert->close();
            $awarded = true;
        break;

        case 'invite-frens':
            // only count referrals older than 24h to reduce fake-account gaming
            $minAge = $now - 86400;
            $refStmt = $MySQLi->prepare('SELECT COUNT(1) as c FROM `users` WHERE `inviterID` = ? AND `joinDate` <= ?');
            $refStmt->bind_param('ii', $user_id, $minAge);
            $refStmt->execute();
            $rres = $refStmt->get_result()->fetch_assoc();
            $refStmt->close();
            if ((int)$rres['c'] >= 5){
                $reward = 20000;
                $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
                $awardStmt->bind_param('iii', $reward, $reward, $user_id);
                $awardStmt->execute();
                $awardStmt->close();
                $insert = $MySQLi->prepare('INSERT INTO `user_tasks` (`user_id`,`task_name`,`check_time`) VALUES (?, ?, ?)');
                $insert->bind_param('isi', $user_id, $task, $now);
                $insert->execute();
                $insert->close();
                $awarded = true;
            }
        break;

        case 'add-time-telegram':
            $reward = 2500;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.telegram.org/bot' . rawurlencode($apiKey) . '/getChat?chat_id=' . rawurlencode($user_id),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $resp = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log('verify.getChat curl error: ' . curl_error($ch));
                curl_close($ch);
                $json = null;
            } else {
                curl_close($ch);
                $json = $resp ? json_decode($resp, true) : null;
                if (json_last_error() !== JSON_ERROR_NONE) $json = null;
            }
            $name = $json['result']['first_name'] ?? '';
            if (is_string($name) && strpos($name, '⏳') !== false){
                $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
                $awardStmt->bind_param('iii', $reward, $reward, $user_id);
                $awardStmt->execute();
                $awardStmt->close();
                $insert = $MySQLi->prepare('INSERT INTO `user_tasks` (`user_id`,`task_name`,`check_time`) VALUES (?, ?, ?)');
                $insert->bind_param('isi', $user_id, $task, $now);
                $insert->execute();
                $insert->close();
                $awarded = true;
            }
        break;

        case 'subscribe-age-telegram':
            $reward = 50;
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.telegram.org/bot' . rawurlencode($apiKey) . '/getChatMember?chat_id=-1001478594200&user_id=' . rawurlencode($user_id),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $resp = curl_exec($ch);
            if (curl_errno($ch)) {
                error_log('verify.getChatMember curl error: ' . curl_error($ch));
                curl_close($ch);
                $json = null;
            } else {
                curl_close($ch);
                $json = $resp ? json_decode($resp, true) : null;
                if (json_last_error() !== JSON_ERROR_NONE) $json = null;
            }
            if (!empty($json['ok']) && in_array($json['result']['status'] ?? '', ['member','administrator'], true)){
                $awardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
                $awardStmt->bind_param('iii', $reward, $reward, $user_id);
                $awardStmt->execute();
                $awardStmt->close();
                $insert = $MySQLi->prepare('INSERT INTO `user_tasks` (`user_id`,`task_name`,`check_time`) VALUES (?, ?, ?)');
                $insert->bind_param('isi', $user_id, $task, $now);
                $insert->execute();
                $insert->close();
                $awarded = true;
            }
        break;

        default:
            // nothing
    }

    if ($awarded) {
        $MySQLi->commit();
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        $MySQLi->rollback();
        http_response_code(200);
        echo json_encode(['success' => false]);
    }
} catch (Exception $e) {
    $MySQLi->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'server error']);
}

$MySQLi->close();