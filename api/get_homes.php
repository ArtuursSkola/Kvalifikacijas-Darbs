<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

function json_out(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($savienojums) || !$savienojums instanceof mysqli) {
    json_out(['ok' => false, 'error' => 'Database connection failed'], 500);
}

$action = trim((string)($_REQUEST['action'] ?? ''));

if ($action === 'availability') {
    $homeId = isset($_GET['home_id']) ? (int)$_GET['home_id'] : 0;
    $month = trim((string)($_GET['month'] ?? ''));
    if ($homeId <= 0 || !preg_match('/^\\d{4}-\\d{2}$/', $month)) {
        json_out(['ok' => false, 'error' => 'Bad request'], 400);
    }

    $from = $month . '-01';
    $to = date('Y-m-d', strtotime($from . ' +1 month'));

    $stmt = $savienojums->prepare("
        SELECT sakuma_datums, beigu_datums
        FROM est_pieteikumi
        WHERE sludinajuma_id = ?
          AND sakuma_datums IS NOT NULL
          AND beigu_datums IS NOT NULL
          AND statuss IN ('Apstiprinats', 'Rezervets')
          AND sakuma_datums < ?
          AND beigu_datums > ?
        ORDER BY sakuma_datums ASC
    ");
    if (!$stmt) {
        json_out(['ok' => false, 'error' => 'Failed to prepare query'], 500);
    }
    $stmt->bind_param('iss', $homeId, $to, $from);
    $stmt->execute();
    $res = $stmt->get_result();
    $ranges = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $ranges[] = [
            'from' => (string)($row['sakuma_datums'] ?? ''),
            'to' => (string)($row['beigu_datums'] ?? ''),
        ];
    }
    $stmt->close();
    $savienojums->close();
    json_out(['ok' => true, 'home_id' => $homeId, 'month' => $month, 'ranges' => $ranges]);
}

if ($action === 'pieteikums_create') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
    }

    $homeId = isset($_POST['home_id']) ? (int)$_POST['home_id'] : 0;
    if ($homeId <= 0) {
        json_out(['ok' => false, 'error' => 'Missing home_id'], 400);
    }

    $homeStmt = $savienojums->prepare("SELECT id, veids, statuss FROM est_homes WHERE id = ? LIMIT 1");
    if (!$homeStmt) {
        json_out(['ok' => false, 'error' => 'Failed to prepare query'], 500);
    }
    $homeStmt->bind_param('i', $homeId);
    $homeStmt->execute();
    $home = $homeStmt->get_result()->fetch_assoc();
    $homeStmt->close();

    if (!$home) {
        json_out(['ok' => false, 'error' => 'Sludinājums nav atrasts'], 404);
    }

    if (($home['statuss'] ?? '') === 'Pardots') {
        json_out(['ok' => false, 'error' => 'Šis īpašums jau ir pārdots vai izīrēts.'], 403);
    }

    $veids = (string)($home['veids'] ?? '');
    $lietotajaId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $vardsUzvards = trim((string)($_POST['vards_uzvards'] ?? ''));
    $epasts = trim((string)($_POST['epasts'] ?? ''));
    $telefons = trim((string)($_POST['telefons'] ?? ''));
    $komentars = trim((string)($_POST['komentars'] ?? ''));

    if ($vardsUzvards === '' || $epasts === '') {
        json_out(['ok' => false, 'error' => 'Aizpildiet vārdu/uzvārdu un e-pastu.'], 422);
    }
    if (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        json_out(['ok' => false, 'error' => 'Nederīgs e-pasts.'], 422);
    }

    $iresMenesi = null;
    $navZinams = 0;
    $iresSakumaDatums = null;
    $sakumaDatums = null;
    $beiguDatums = null;
    $piedavataSumma = null;
    $finansesanasVeids = null;

    if ($veids === 'ire' || $veids === 'rent') {
        $iresMenesiRaw = trim((string)($_POST['ires_menesi'] ?? ''));
        $iresMenesi = $iresMenesiRaw === '' ? null : (int)$iresMenesiRaw;
        $navZinams = isset($_POST['nav_zinams']) && (string)$_POST['nav_zinams'] === '1' ? 1 : 0;
        $iresSakumaDatums = trim((string)($_POST['ires_sakuma_datums'] ?? ''));
        $iresSakumaDatums = substr($iresSakumaDatums, 0, 10);
        if ($iresSakumaDatums === '') {
            json_out(['ok' => false, 'error' => 'Norādiet sākuma datumu.'], 422);
        }
    } elseif ($veids === 'istermina_ire') {
        $sakumaDatums = trim((string)($_POST['sakuma_datums'] ?? ''));
        $beiguDatums  = trim((string)($_POST['beigu_datums']  ?? ''));

        if ($sakumaDatums === '' || $beiguDatums === '') {
            json_out(['ok' => false, 'error' => 'Norādiet sākuma un beigu datumu.'], 422);
        }

        $sakumaDatums = substr($sakumaDatums, 0, 10);
        $beiguDatums  = substr($beiguDatums,  0, 10);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sakumaDatums) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $beiguDatums)) {
            json_out(['ok' => false, 'error' => 'Nederīgs datuma formāts.'], 422);
        }
        if (strtotime($beiguDatums) <= strtotime($sakumaDatums)) {
            json_out(['ok' => false, 'error' => 'Beigu datumam jābūt pēc sākuma datuma.'], 422);
        }

        $overlapStmt = $savienojums->prepare("
            SELECT id
            FROM est_pieteikumi
            WHERE sludinajuma_id = ?
              AND statuss IN ('Apstiprinats', 'Rezervets')
              AND sakuma_datums IS NOT NULL
              AND beigu_datums IS NOT NULL
              AND sakuma_datums < ?
              AND beigu_datums > ?
            LIMIT 1
        ");
        if ($overlapStmt) {
            $overlapStmt->bind_param('iss', $homeId, $beiguDatums, $sakumaDatums);
            $overlapStmt->execute();
            $overlap = $overlapStmt->get_result()->fetch_assoc();
            $overlapStmt->close();
            if ($overlap) {
                json_out(['ok' => false, 'error' => 'Izvēlētie datumi nav pieejami.'], 409);
            }
        }
    } else {
        $piedavataRaw = trim((string)($_POST['piedavata_summa'] ?? ''));
        $piedavataSumma = $piedavataRaw === '' ? null : (float)$piedavataRaw;
        $finansesanasVeids = trim((string)($_POST['finansesanas_veids'] ?? ''));
        if ($finansesanasVeids === '') {
            $finansesanasVeids = null;
        }
    }

    $statuss = 'Jauns';

    $ins = $savienojums->prepare("
        INSERT INTO est_pieteikumi (
            sludinajuma_id,
            sludinajuma_veids,
            lietotaja_id,
            vards_uzvards,
            epasts,
            telefons,
            komentars,
            ires_menesi,
            nav_zinams,
            ires_sakuma_datums,
            sakuma_datums,
            beigu_datums,
            piedavata_summa,
            finansesanas_veids,
            statuss,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    if (!$ins) {
        json_out(['ok' => false, 'error' => 'Failed to prepare insert'], 500);
    }

    $ins->bind_param(
        'isissssiisssdss',
        $homeId,
        $veids,
        $lietotajaId,
        $vardsUzvards,
        $epasts,
        $telefons,
        $komentars,
        $iresMenesi,
        $navZinams,
        $iresSakumaDatums,
        $sakumaDatums,
        $beiguDatums,
        $piedavataSumma,
        $finansesanasVeids,
        $statuss
    );

    if (!$ins->execute()) {
        $err = $ins->error ?: 'Insert failed';
        $ins->close();
        json_out(['ok' => false, 'error' => $err], 500);
    }

    $newId = (int)$ins->insert_id;
    $ins->close();
    $savienojums->close();
    json_out(['ok' => true, 'id' => $newId]);
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$select = "SELECT id, ipasnieka_id, nosaukums, pilseta, atrasanas_vieta, veids, cena, platiba, gulamistabas, vannasistabas,
        apraksts, galvenais_attels, statuss, kategorija
    FROM est_homes";

$where = " WHERE statuss = 'Aktivs'";
$order = " ORDER BY created_at DESC";

$stmt = null;
$result = $savienojums->query($select . $where . $order);


$homes = [];
$fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';

$rawHomes = [];
$ownerIds = [];

if ($result && $result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $ownerId = (int)($row['ipasnieka_id'] ?? 0);
        if ($ownerId > 0) {
            $ownerIds[] = $ownerId;
        }
        $rawHomes[] = $row;
    }
}

$ownersMap = [];
if ($ownerIds !== []) {
    $uniqueOwnerIds = array_unique($ownerIds);
    $safeOwnerIds = implode(',', array_map('intval', $uniqueOwnerIds));
    $ownerStmt = $savienojums->prepare("SELECT lietotaja_id, lietotajvards, profila_bilde, plans FROM est_lietotaji WHERE lietotaja_id IN ($safeOwnerIds)");
    if ($ownerStmt) {
        $ownerStmt->execute();
        $ownerRes = $ownerStmt->get_result();
        while ($o = $ownerRes->fetch_assoc()) {
            $ownersMap[(int)$o['lietotaja_id']] = $o;
        }
        $ownerStmt->close();
    }
}

foreach ($rawHomes as $row) {
    $desc = (string)($row['apraksts'] ?? '');
    $descShort = '';
    if ($desc !== '') {
        $slice = function_exists('mb_substr') ? mb_substr($desc, 0, 150) : substr($desc, 0, 150);
        $descShort = $slice . '...';
    }
    $type = (string)($row['veids'] ?? '');
    if ($type === 'rent') $type = 'ire';
    if ($type === 'buy') $type = 'pardod';
    $city = (string)($row['pilseta'] ?? '');
    $locText = (string)($row['atrasanas_vieta'] ?? '');
    $ownerId = (int)($row['ipasnieka_id'] ?? 0);
    $owner = $ownersMap[$ownerId] ?? null;

    $badgeText = $type === 'ire' ? 'Īrēšana' : ($type === 'istermina_ire' ? 'Īstermiņa īre' : 'Pārdod');

    $homes[] = [
        'id' => (int)($row['id'] ?? 0),
        'status' => (string)($row['statuss'] ?? ''),
        'is_own' => $userId > 0 && $ownerId === $userId,
        'title' => (string)($row['nosaukums'] ?? ''),
        'city' => $city,
        'location' => $city . ($locText !== '' ? ', ' . $locText : ''),
        'type' => $type,
        'category' => (string)($row['kategorija'] ?? ''),
        'price' => (float)($row['cena'] ?? 0),
        'beds' => (int)($row['gulamistabas'] ?? 0),
        'baths' => (int)($row['vannasistabas'] ?? 0),
        'size' => (int)($row['platiba'] ?? 0),
        'badge' => $badgeText,
        'image' => media_absolute_url(!empty($row['galvenais_attels']) ? (string)$row['galvenais_attels'] : $fallbackImg),
        'desc' => $descShort,
        'owner_username' => (string)($owner['lietotajvards'] ?? ''),
        'owner_pfp' => media_absolute_url($owner['profila_bilde'] ?? ''),
        'owner_plan' => (string)($owner['plans'] ?? '')
    ];
}

if ($stmt) $stmt->close();
$savienojums->close();

echo json_encode($homes, JSON_UNESCAPED_UNICODE);
