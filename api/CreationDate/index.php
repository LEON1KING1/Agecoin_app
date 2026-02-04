<?php

$ages = json_decode(file_get_contents('ages.json'), true);

$ids = array_keys($ages);
sort($ids, SORT_NUMERIC);

$nids = array_map('intval', $ids);

$minId = $nids[0];
$maxId = end($nids);

function calculateDate($id) {
    global $ages, $nids, $ids, $minId, $maxId;

    if ($id < $minId) {
        return [-1, date('m/Y', $ages[$ids[0]] / 1000)];
    } elseif ($id > $maxId) {
        return [1, date('m/Y', $ages[$ids[count($ids) - 1]] / 1000)];
    } else {
        $lid = $nids[0];
        for ($i = 0; $i < count($ids); $i++) {
            if ($id <= $nids[$i]) {
                $uid = $nids[$i];
                $lage = $ages[$lid];
                $uage = $ages[$uid];

                $idratio = ($id - $lid) / ($uid - $lid);
                $midDate = floor($idratio * ($uage - $lage) + $lage);
                return [0, date('m/Y', $midDate / 1000)];
            } else {
                $lid = $nids[$i];
            }
        }
    }
}

function getAge($id) {
    $d = calculateDate($id);
    return [
        $d[1]
        
    ];
}

$id = isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'invalid input']);
    die;
}
$g = getAge($id);
$yearPart = isset($g[0]) ? explode('/', $g[0])[1] ?? null : null;
if (!$yearPart || !is_numeric($yearPart)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'cannot calculate age']);
    die;
}
echo (int)date('Y') - (int)$yearPart; 