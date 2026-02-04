<!DOCTYPE html>
<?php
include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) {
    error_log('MySQL connection error (admin users manage): ' . $MySQLi->connect_error);
    http_response_code(500);
    echo '<p class="text-center text-red-600">Database connection error.</p>';
    exit;
}
function ToDie($MySQLi){
    error_log('MySQL error (admin users manage): ' . $MySQLi->error);
    $MySQLi->close();
    http_response_code(500);
    echo '<p class="text-center text-red-600">Internal server error.</p>';
    exit;
} 

$q = isset($_REQUEST['q']) ? (int)$_REQUEST['q'] : 0;

// safe lookup
$stmt = $MySQLi->prepare('SELECT * FROM `users` WHERE `id` = ? LIMIT 1');
$stmt->bind_param('i', $q);
$stmt->execute();
$get_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$get_user) {
    echo '<div class="container mx-auto p-8">\n<p class="text-center text-red-600">User not found.</p></div>';
    $MySQLi->close();
    exit;
}

$Name = ($get_user['firstName'] ?? '') . ' ' . ($get_user['lastName'] ?? '');
$UserID = $get_user['id'];
$Username = $get_user['username']?:'-';
$isBanned = 'No';
if (($get_user['step'] ?? '') == 'banned') $isBanned = 'Yes';
$isPremium = 'No';
if ($get_user['isPremium'] == 1) $isPremium = 'Yes';
$Score = number_format($get_user['score']);
$Referrals = $get_user['referrals'];
$Language = $get_user['language'];
$Age = $get_user['age'];
$Wallet = $get_user['wallet']?:'-';
$Streak = $get_user['streak'];
$fernsReward = $get_user['fernsReward']?:0;
$tasksReward = $get_user['tasksReward']?:0;
$walletReward = $get_user['walletReward']?:0;
$dailyReward = $get_user['dailyReward']?:0;
$joinDate = date('Y-m-d H:i:s', $get_user['joinDate']);
$userInGameURL = $web_app . '#tgWebAppData='.$get_user['tdata'].'&tgWebAppVersion=7.2&tgWebAppPlatform=weba&tgWebAppThemeParams=%7B%22bg_color%22%3A%22%23212121%22%2C%22text_color%22%3A%22%23ffffff%22%2C%22hint_color%22%3A%22%23aaaaaa%22%2C%22link_color%22%3A%22%238774e1%22%2C%22button_color%22%3A%22%238774e1%22%2C%22button_text_color%22%3A%22%23ffffff%22%2C%22secondary_bg_color%22%3A%22%230f0f0f%22%2C%22header_bg_color%22%3A%22%23212121%22%2C%22accent_text_color%22%3A%22%238774e1%22%2C%22section_bg_color%22%3A%22%23212121%22%2C%22section_header_text_color%22%3A%22%23aaaaaa%22%2C%22subtitle_text_color%22%3A%22%23aaaaaa%22%2C%22destructive_text_color%22%3A%22%23e53935%22%7D';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @font-face {
            font-family: 'CustomFont';
            src: url('./CustomFont.woff2') format('woff2');
        }
        body {
            font-family: 'CustomFont', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold mb-8 text-center">User Management</h1>
        
        <!-- User Info Section -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">User Information</h2>
            <div id="userInfo" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- User information will be populated here dynamically -->
                <p class="text-gray-700"><strong>Name: </strong><?=$Name;?></p>
                <p class="text-gray-700"><strong>UserID: </strong><?=$UserID;?></p>
                <p class="text-gray-700"><strong>Username: </strong><?=$Username;?></p>
                <p class="text-gray-700"><strong>isBanned: </strong><?=$isBanned;?></p>
                <p class="text-gray-700"><strong>Language: </strong><?=$Language;?></p>
                <p class="text-gray-700"><strong>isPremium: </strong><?=$isPremium;?></p>
                <p class="text-gray-700"><strong>Score: </strong><?=$Score;?></p>
                <p class="text-gray-700"><strong>Age: </strong><?=$Age . ' Years';?></p>
                <p class="text-gray-700"><strong>Wallet: </strong><?=$Wallet;?></p>
                <p class="text-gray-700"><strong>streak: </strong><?=$Streak;?></p>
                <p class="text-gray-700"><strong>ferns Reward: </strong><?=$fernsReward;?></p>
                <p class="text-gray-700"><strong>tasks Reward: </strong><?=$tasksReward;?></p>
                <p class="text-gray-700"><strong>wallet Reward: </strong><?=$walletReward;?></p>
                <p class="text-gray-700"><strong>daily Reward: </strong><?=$dailyReward;?></p>
                <p class="text-gray-700"><strong>Referrals: </strong><?=$Referrals;?></p>
                <p class="text-gray-700"><strong>joinDate: </strong><?=$joinDate;?></p>
                <!-- Add more user info as needed -->
            </div>
        </div>


<!-- Actions Section -->
<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors" onclick="banUser()">Ban User</button>
        <button class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors" onclick="unbanUser()">Unban User</button>
        <button class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors" onclick="changeUserScore()">Change User Score</button>
        <button class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors" onclick="sendMessageToUser()">Send Message to User</button>
        <button class="bg-gray-700 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50 transition-colors" onclick="openUserProfileInGame()">Open User In-Game Profile</button>
        <a href="../index.php" class="text-gray-900 hover:text-white border border-gray-800 hover:bg-gray-900 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2 dark:border-gray-600 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800">Back To Search</a>

        <!-- Add more action buttons as needed -->
    </div>
</div>

</div>


<script>


//          close keyboard when clicked on empty area           //
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(event) {
        var isInputOrTextarea = event.target.tagName.toLowerCase() === 'input' || event.target.tagName.toLowerCase() === 'textarea';
        
        if (!isInputOrTextarea) {
            var activeElement = document.activeElement;
            if (activeElement && (activeElement.tagName.toLowerCase() === 'input' || activeElement.tagName.toLowerCase() === 'textarea')) {
                activeElement.blur();
            }
        }
    });

    /* Admin token helper (page-local):
       - prompt to set token (stores in sessionStorage)
       - automatically attaches X-Admin-Token to admin POSTs when present
       - DOES NOT embed secret in source control
    */
    const adminBanner = document.createElement('div');
    adminBanner.id = 'agecoin-admin-token-banner';
    adminBanner.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:9999;';
    document.body.appendChild(adminBanner);

    function updateAdminTokenBanner(){
        const token = sessionStorage.getItem('agecoin_admin_token');
        adminBanner.innerHTML = '';
        const btn = document.createElement('button');
        btn.className = 'bg-yellow-500 text-black px-3 py-2 rounded-md shadow-md';
        btn.onclick = () => setAdminTokenPrompt();
        btn.textContent = token ? 'Admin token (set) — change' : 'Set admin token (required)';
        adminBanner.appendChild(btn);
        if (token){
            const clr = document.createElement('button');
            clr.className = 'ml-2 bg-red-500 text-white px-2 py-2 rounded-md';
            clr.textContent = 'Clear';
            clr.onclick = () => { sessionStorage.removeItem('agecoin_admin_token'); updateAdminTokenBanner(); };
            adminBanner.appendChild(clr);
        }
    }

    function setAdminTokenPrompt(){
        const v = prompt('Enter AGECOIN_ADMIN_TOKEN (will be stored in this browser session only)');
        if (v && v.trim()) {
            sessionStorage.setItem('agecoin_admin_token', v.trim());
        }
        updateAdminTokenBanner();
    }

    function getAdminHeaders(){
        const t = sessionStorage.getItem('agecoin_admin_token');
        return t ? { 'X-Admin-Token': t } : {};
    }

    updateAdminTokenBanner();
});



    function banUser() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you really want to ban this user?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, ban user!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const userId = new URLSearchParams(window.location.search).get('q');
                fetch('./api.php', {
                    method: 'POST',
                    headers: Object.assign({'Content-Type': 'application/x-www-form-urlencoded'}, getAdminHeaders()),
                    body: `q=${userId}&action=banUser`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Banned!',
                            'The user has been banned.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            'There was a problem banning the user.',
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Error!',
                        'There was a problem banning the user.',
                        'error'
                    );
                });
            }
        });
    }


    function unbanUser() {
        Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to unban this user?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, unban user!',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const userId = new URLSearchParams(window.location.search).get('q');
            fetch('./api.php', {
                method: 'POST',
                headers: Object.assign({'Content-Type': 'application/x-www-form-urlencoded'}, getAdminHeaders()),
                body: `q=${userId}&action=unbanUser`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'UnBanned!',
                        'The user has been unbanned.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        'There was a problem unbanning the user.',
                        'error'
                    );
                }
            })
            .catch(error => {
                Swal.fire(
                    'Error!',
                    'There was a problem unbanning the user.',
                    'error'
                );
            });
        }
    });
    }


    function changeUserScore() {
        Swal.fire({
            title: 'Enter new score',
            input: 'number',
            inputAttributes: {
                min: 0,
                autocomplete: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Submit',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const newScore = result.value;
                const userId = new URLSearchParams(window.location.search).get('q');
                fetch('./api.php', {
                    method: 'POST',
                    headers: Object.assign({'Content-Type': 'application/x-www-form-urlencoded'}, getAdminHeaders()),
                    body: `q=${userId}&action=changeUserScore&newScore=${newScore}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Success!',
                            'The user\'s score has been changed.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            'There was a problem changing the user\'s score.',
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Error!',
                        'There was a problem changing the user\'s score.',
                        'error'
                    );
                });
            }
        });
    }


    function sendMessageToUser() {
        Swal.fire({
            title: 'Send Message To User',
            text: 'You can use HTML formats !',
            input: 'textarea',
            inputAttributes: {
                autocapitalize: 'off',
                rows: 1,
                autocomplete: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Send',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: (message) => {
                const userId = new URLSearchParams(window.location.search).get('q');
                return fetch('./api.php', {
                    method: 'POST',
                    headers: Object.assign({'Content-Type': 'application/x-www-form-urlencoded'}, getAdminHeaders()),
                    body: `q=${userId}&action=sendMessageToUser&text=${encodeURIComponent(message)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire(
                    'Message Sent!',
                    'Your message has been sent to the user.',
                    'success'
                ).then(() => {
                    location.reload();
                });
            }
        });
    }


    function openUserProfileInGame(){
        var url = '<?php echo $userInGameURL; ?>';
        window.location.href = url;
    }




    </script>
</body>
</html>