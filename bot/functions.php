<?php
/**
 * Safe IO / (de)serialization helpers
 * - prefer JSON
 * - bound file sizes
 * - disallow classes in unserialize
 */
function safe_file_get_contents(string $file, int $maxBytes = 1048576) {
    if (!is_readable($file)) return null;
    $size = @filesize($file);
    if ($size !== false && $size > $maxBytes) return null;
    $raw = file_get_contents($file);
    if ($raw === false) return null;
    if (strlen($raw) > $maxBytes) return null;
    return $raw;
}

function safe_json_decode(string $raw, bool $assoc = true) {
    $data = json_decode($raw, $assoc);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}

function safe_unserialize(string $raw) {
    // prefer JSON when available
    $maybe = safe_json_decode($raw, true);
    if (is_array($maybe) || is_object($maybe)) return $maybe;
    // run unserialize without using @ by converting warnings to exceptions
    $prev = set_error_handler(function($severity, $message) { throw new \ErrorException($message, 0, $severity); });
    try {
        $data = unserialize($raw, ['allowed_classes' => false]);
    } catch (\Throwable $e) {
        $data = null;
    }
    if ($prev !== null) set_error_handler($prev); else restore_error_handler();
    if ($data === false && $raw !== serialize(false)) return null;
    return $data;
}

function LampStack($method, $datas = []){
    global $apiKey;
    $url = 'https://api.telegram.org/bot' . $apiKey . '/' . $method;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $datas,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('LampStack curl error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return safe_json_decode($res, false);
}

function GetAge($user_id){
    global $web_app;
    $raw = safe_file_get_contents($web_app . '/api/CreationDate/index.php?user_id=' . $user_id, 32 * 1024);
    return (int) ($raw ?? 0);
}

// Simple file-backed cache (safe for short-lived caches)
function agecoin_cache_get(string $key) {
    $file = sys_get_temp_dir() . '/agecoin_cache_' . preg_replace('/[^a-z0-9_\-]/i','', $key);
    if (!is_readable($file)) return null;
    $raw = safe_file_get_contents($file);
    if ($raw === null) return null;
    $data = safe_unserialize($raw);
    if (!is_array($data) || !isset($data['exp'], $data['val'])) return null;
    if ($data['exp'] < time()) return null;
    return $data['val'];
}
function agecoin_cache_set(string $key, $val, int $ttl = 30): bool {
    $file = sys_get_temp_dir() . '/agecoin_cache_' . preg_replace('/[^a-z0-9_\-]/i','', $key);
    $data = ['exp' => time() + $ttl, 'val' => $val];
    return file_put_contents($file, serialize($data), LOCK_EX) !== false;
}

// Reusable rate limiter (token-bucket via file timestamps)
function agecoin_rate_limited(string $key, int $limit = 10, int $window = 60): bool {
    $f = sys_get_temp_dir() . '/agecoin_rl_' . preg_replace('/[^a-z0-9_\-]/i','', $key);
    $now = time();
    $data = [];
    if (is_readable($f)) {
        $raw = safe_file_get_contents($f, 64 * 1024);
        $data = $raw ? safe_json_decode($raw, true) : [];
        if (!is_array($data)) $data = [];
        $data = array_filter($data, function($t) use($now, $window){ return ($t > $now - $window); });
    }
    if (count($data) >= $limit) return true;
    $data[] = $now;
    if (file_put_contents($f, json_encode($data), LOCK_EX) === false) error_log('rate-limiter: failed to write ' . $f);
    return false;
}

function remove_json_comma($json_data){
    $json = '{';
    foreach ($json_data as $key => $value) {
        $json .= '"' . $key . '": ';
        if (is_array($value)) {
            $json .= json_encode($value) . ',';
        } else {
            $json .= $value . ',';
        }
    }
    $json = rtrim($json, ','); // Remove the last comma
    $json .= '}';
    return $json;
} 

function generateRandomCode($length = 32) {
    $randomBytes = random_bytes($length);
    
    $base64String = base64_encode($randomBytes);
    
    return rtrim($base64String, '=');
}

function dbBackup($host, $user, $pass, $dbname, $path) {
$link = mysqli_connect($host,$user,$pass, $dbname);
if (mysqli_connect_errno()){
echo "Failed to connect to MySQL: " . mysqli_connect_error();
exit;
}
mysqli_query($link, "SET NAMES 'utf8'");
$tables = array();
$result = mysqli_query($link, 'SHOW TABLES');
while($row = mysqli_fetch_row($result)) {
$tables[] = $row[0];
}
$return = '';
foreach($tables as $table) {
$result = mysqli_query($link, 'SELECT * FROM '.$table);
$num_fields = mysqli_num_fields($result);
$num_rows = mysqli_num_rows($result);
$return.= 'DROP TABLE IF EXISTS '.$table.';';
$row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE '.$table));
$return.= "\n\n".$row2[1].";\n\n";
$counter = 1;
for ($i = 0; $i < $num_fields; $i++) {
while($row = mysqli_fetch_row($result)) {   
if($counter == 1){
$return.= 'INSERT INTO '.$table.' VALUES(';
}else{
$return.= '(';
}
for($j=0; $j<$num_fields; $j++){
$row[$j] = addslashes($row[$j]);
$row[$j] = str_replace("\n","\\n",$row[$j]);
if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; 
}else{
$return.= '""';
}
if ($j<($num_fields-1)) { 
$return.= ',';
}
}
if($num_rows == $counter){
$return.= ");\n";
}else{
$return.= "),\n";
}
++$counter;
}
}
$return.="\n\n\n";
}
$fileName = $path . '.sql';
$handle = fopen($fileName,'w+');
fwrite($handle,$return);
if(fclose($handle)){
return true;
exit; 
}
}