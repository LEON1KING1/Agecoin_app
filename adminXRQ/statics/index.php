<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (admin statics): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo 'Database connection error';
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (admin statics): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo 'Internal server error';
    exit;
} 



$res = $MySQLi->query("SELECT `id` FROM `users`");
$totalPlayers = $res ? $res->num_rows : 0; 


$start_timestamp = time() - (1 * 24 * 60 * 60);
$end_timestamp = time();
$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `joinDate` BETWEEN $start_timestamp AND $end_timestamp");
$dailyPlayers = $res ? $res->num_rows : 0; 


$start_timestamp = time() - (7 * 24 * 60 * 60);
$end_timestamp = time();
$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `joinDate` BETWEEN $start_timestamp AND $end_timestamp");
$weeklyPlayers = $res ? $res->num_rows : 0; 


$start_timestamp = time() - (30 * 24 * 60 * 60);
$end_timestamp = time();
$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `joinDate` BETWEEN $start_timestamp AND $end_timestamp");
$monthlyPlayers = $res ? $res->num_rows : 0; 


$start_timestamp = time() - (24 * 60 * 60);
$end_timestamp = time();
$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `dailyRewardDate` BETWEEN $start_timestamp AND $end_timestamp");
$onlinePlayers = $res ? $res->num_rows : 0; 


$res = $MySQLi->query("SELECT SUM(`score`) AS sum FROM `users`");
$row = $res ? $res->fetch_assoc() : null;
$totalBalance = isset($row['sum']) ? (int)$row['sum'] : 0; 


$res = $MySQLi->query("SELECT SUM(`fernsReward`) AS sum FROM `users`");
$row = $res ? $res->fetch_assoc() : null;
$totalFernsReward = isset($row['sum']) ? (int)$row['sum'] : 0; 


$res = $MySQLi->query("SELECT SUM(`tasksReward`) AS sum FROM `users`");
$row = $res ? $res->fetch_assoc() : null;
$totalTasksReward = isset($row['sum']) ? (int)$row['sum'] : 0; 


$res = $MySQLi->query("SELECT SUM(`walletReward`) AS sum FROM `users`");
$row = $res ? $res->fetch_assoc() : null;
$totalWalletReward = isset($row['sum']) ? (int)$row['sum'] : 0; 


$res = $MySQLi->query("SELECT SUM(`dailyReward`) AS sum FROM `users`");
$row = $res ? $res->fetch_assoc() : null;
$totalDailyReward = isset($row['sum']) ? (int)$row['sum'] : 0; 


$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `wallet` IS NOT NULL");
$totalWalletConnected = $res ? $res->num_rows : 0; 


$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `isPremium` = 1");
$premiumPlayers = $res ? $res->num_rows : 0; 


$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `step` = 'banned'");
$bannedPlayers = $res ? $res->num_rows : 0; 


$res = $MySQLi->query("SELECT `id` FROM `users` WHERE `inviterID` IS NOT NULL");
$invitedPlayers = $res ? $res->num_rows : 0; 


$MySQLi->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Page</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'CustomFont';
            src: url('./CustomFont.woff2') format('woff2');
        }
        body {
            font-family: 'CustomFont', sans-serif;
            background-color: #FFFFFF;
            color: #000000;
        }
        .stat-item {
            border: 1px solid #000000;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-8 text-center">Application Statistics</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Users</h2>
                <p class="text-3xl font-bold"><?= number_format($totalPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Premium Users</h2>
                <p class="text-3xl font-bold"><?= number_format($premiumPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Invited Users</h2>
                <p class="text-3xl font-bold"><?= number_format($invitedPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Banned Users</h2>
                <p class="text-3xl font-bold"><?= number_format($bannedPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Today's Users</h2>
                <p class="text-3xl font-bold"><?= number_format($dailyPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">This Week Users</h2>
                <p class="text-3xl font-bold"><?= number_format($weeklyPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">This Month Users</h2>
                <p class="text-3xl font-bold"><?= number_format($monthlyPlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Online Players</h2>
                <p class="text-3xl font-bold"><?= number_format($onlinePlayers); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Wallet Connected Users</h2>
                <p class="text-3xl font-bold"><?= number_format($totalWalletConnected); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Balances</h2>
                <p class="text-3xl font-bold"><?= number_format($totalBalance); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Ferns Reward</h2>
                <p class="text-3xl font-bold"><?= number_format($totalFernsReward); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Tasks Reward</h2>
                <p class="text-3xl font-bold"><?= number_format($totalTasksReward); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Wallet Reward</h2>
                <p class="text-3xl font-bold"><?= number_format($totalWalletReward); ?></p>
            </div>
            <div class="stat-item">
                <h2 class="text-lg font-semibold mb-2">Total Daily Reward</h2>
                <p class="text-3xl font-bold"><?= number_format($totalDailyReward); ?></p>
            </div>
            
        </div>
    </div>
</body>
</html>
