<?php

include ('../bot/config.php');

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error){
echo 'Connection failed: ' . $MySQLi->connect_error;
$MySQLi->close();
die;
}


//          users            //
$query = "CREATE TABLE users (
id BIGINT(255) PRIMARY KEY,
step VARCHAR(255) DEFAULT NULL,
firstName VARCHAR(255) DEFAULT NULL,
lastName VARCHAR(255) DEFAULT NULL,
username VARCHAR(255) DEFAULT NULL,
age INT(64) DEFAULT 0,
isPremium INT(1) DEFAULT 0,
language VARCHAR(32) DEFAULT 'en',
score BIGINT DEFAULT 0,
fernsReward INT DEFAULT 0,
tasksReward INT DEFAULT 0,
walletReward INT DEFAULT 0,
dailyReward INT DEFAULT 0,
wallet VARCHAR(255) DEFAULT NULL,
walletOTP VARCHAR(255) DEFAULT NULL,
hash VARCHAR(255) DEFAULT NULL,
tdata VARCHAR(1028) DEFAULT NULL,
referrals INT DEFAULT 0,
inviterID BIGINT(255) DEFAULT NULL,
streak INT DEFAULT 0,
lastSeenDate VARCHAR(255) DEFAULT NULL,
dailyRewardDate VARCHAR(255) DEFAULT NULL,
joinDate BIGINT DEFAULT NULL
) default charset = utf8mb4";
if($MySQLi->query($query) === false)
echo $MySQLi->error.'<br>';

// add recommended indexes (idempotent)
$indexes = [
    'idx_users_joinDate' => "ALTER TABLE users ADD INDEX idx_users_joinDate (joinDate)",
    'idx_users_dailyRewardDate' => "ALTER TABLE users ADD INDEX idx_users_dailyRewardDate (dailyRewardDate)",
    'idx_users_inviterID' => "ALTER TABLE users ADD INDEX idx_users_inviterID (inviterID)",
    'idx_users_isPremium' => "ALTER TABLE users ADD INDEX idx_users_isPremium (isPremium)",
    'idx_users_score' => "ALTER TABLE users ADD INDEX idx_users_score (score)",
    'idx_users_hash' => "ALTER TABLE users ADD INDEX idx_users_hash (hash(64))",
    // additional indexes to improve lookup & search
    'idx_users_username' => "ALTER TABLE users ADD INDEX idx_users_username (username(64))",
    'idx_users_wallet' => "ALTER TABLE users ADD INDEX idx_users_wallet (wallet(64))",
];
foreach ($indexes as $name => $sql) {
    // create index only if it doesn't already exist
    $res = $MySQLi->query("SHOW INDEX FROM users WHERE Key_name = '" . $MySQLi->real_escape_string(substr($name, 4)) . "'");
    if (!$res || $res->num_rows === 0) {
        $MySQLi->query($sql);
    }
}


//          user_tasks            //
$query = "CREATE TABLE user_tasks (
id INT PRIMARY KEY AUTO_INCREMENT,
user_id BIGINT(255),
task_name VARCHAR(128),
check_time BIGINT,
FOREIGN KEY (user_id) REFERENCES users(id)
) DEFAULT CHARSET = utf8mb4";
if($MySQLi->query($query) === false)
echo $MySQLi->error.'<br>';


//          sending            //
$query = "CREATE TABLE `sending` (
`type` VARCHAR(255) PRIMARY KEY,
`chat_id` BIGINT(255) DEFAULT NULL,
`msg_id` BIGINT(255) DEFAULT NULL,
`count` BIGINT(225) DEFAULT NULL
) default charset = utf8mb4";
if($MySQLi->query($query) === false)
echo $MySQLi->error.'<br>';




echo 'done';
$MySQLi->close();
die;