<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$application_id = (int)$data['id'];
$status = (string)$data['status'];
$user_id = (int)$_SESSION['user_id'];

$map = [
    'pending' => 'Jauns',
    'approved' => 'Apstiprinats',
    'rejected' => 'Noraidits',
    'Jauns' => 'Jauns',
    'Apstiprinats' => 'Apstiprinats',
    'Rezervets' => 'Rezervets',
    'Noraidits' => 'Noraidits',
];

if (!isset($map[$status])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$dbStatus = $map[$status];

$vards_uzvards = isset($data['vards_uzvards']) ? trim((string)$data['vards_uzvards']) : '';
$epasts = isset($data['epasts']) ? trim((string)$data['epasts']) : '';
$telefons = isset($data['telefons']) ? trim((string)$data['telefons']) : '';
$komentars = isset($data['komentars']) ? trim((string)$data['komentars']) : '';

$ires_menesi = isset($data['ires_menesi']) && $data['ires_menesi'] !== '' ? (int)$data['ires_menesi'] : null;
$nav_zinams = isset($data['nav_zinams']) ? (int)$data['nav_zinams'] : 0;

$ires_sakuma_datums = null;
if (isset($data['ires_sakuma_datums']) && $data['ires_sakuma_datums'] !== '') {
    $ires_sakuma_datums = str_replace('T', ' ', substr(trim((string)$data['ires_sakuma_datums']), 0, 19));
}

$sakuma_datums = null;
if (isset($data['sakuma_datums']) && $data['sakuma_datums'] !== '') {
    $sakuma_datums = str_replace('T', ' ', substr(trim((string)$data['sakuma_datums']), 0, 19));
}

$beigu_datums = null;
if (isset($data['beigu_datums']) && $data['beigu_datums'] !== '') {
    $beigu_datums = str_replace('T', ' ', substr(trim((string)$data['beigu_datums']), 0, 19));
}

if ($sakuma_datums !== null && $beigu_datums !== null) {
    if (strtotime($beigu_datums) <= strtotime($sakuma_datums)) {
        echo json_encode(['success' => false, 'message' => 'Beigu datumam jābūt pēc sākuma datuma.']);
        exit();
    }
}

$appStmt = $savienojums->prepare("SELECT sludinajuma_id, sludinajuma_veids FROM est_pieteikumi WHERE id = ? AND lietotaja_id = ? LIMIT 1");
if (!$appStmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$appStmt->bind_param('ii', $application_id, $user_id);
$appStmt->execute();
$appRow = $appStmt->get_result()->fetch_assoc();
$appStmt->close();
if (!$appRow) {
    echo json_encode(['success' => false, 'message' => 'Application not found']);
    exit();
}

$sludinajuma_id = (int)($appRow['sludinajuma_id'] ?? 0);
$sludinajuma_veids = (string)($appRow['sludinajuma_veids'] ?? '');

if ($sludinajuma_veids === 'istermina_ire' && $sakuma_datums !== null && $beigu_datums !== null) {
    $checkStart = substr($sakuma_datums, 0, 10);
    $checkEnd = substr($beigu_datums, 0, 10);
    $overlapStmt = $savienojums->prepare("
        SELECT id
        FROM est_pieteikumi
        WHERE sludinajuma_id = ?
          AND id <> ?
          AND statuss IN ('Apstiprinats', 'Rezervets')
          AND sakuma_datums IS NOT NULL
          AND beigu_datums IS NOT NULL
          AND sakuma_datums < ?
          AND beigu_datums > ?
        LIMIT 1
    ");
    if ($overlapStmt) {
        $overlapStmt->bind_param('iiss', $sludinajuma_id, $application_id, $checkEnd, $checkStart);
        $overlapStmt->execute();
        $overlap = $overlapStmt->get_result()->fetch_assoc();
        $overlapStmt->close();
        if ($overlap) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Izvēlētie datumi nav pieejami.']);
            exit();
        }
    }
}

$piedavata_summa = isset($data['piedavata_summa']) && $data['piedavata_summa'] !== '' ? (float)$data['piedavata_summa'] : null;
$finansesanas_veids = isset($data['finansesanas_veids']) && $data['finansesanas_veids'] !== '' ? trim((string)$data['finansesanas_veids']) : null;

try {
    $query = "UPDATE est_pieteikumi SET 
                statuss = ?, 
                vards_uzvards = ?, 
                epasts = ?, 
                telefons = ?, 
                komentars = ?, 
                ires_menesi = ?, 
                nav_zinams = ?, 
                ires_sakuma_datums = ?, 
                sakuma_datums = ?, 
                beigu_datums = ?, 
                piedavata_summa = ?, 
                finansesanas_veids = ? 
              WHERE id = ? AND lietotaja_id = ?";
    $stmt = $savienojums->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    $stmt->bind_param(
        'sssssiissssdii',
        $dbStatus,
        $vards_uzvards,
        $epasts,
        $telefons,
        $komentars,
        $ires_menesi,
        $nav_zinams,
        $ires_sakuma_datums,
        $sakuma_datums,
        $beigu_datums,
        $piedavata_summa,
        $finansesanas_veids,
        $application_id,
        $user_id
    );
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Application updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found or no changes made']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
