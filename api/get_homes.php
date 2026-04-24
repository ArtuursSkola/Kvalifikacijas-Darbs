<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

if (!isset($savienojums) || !$savienojums instanceof mysqli) {
    echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$select = "SELECT id, owner_id, title, city, location_text, type, price, area, bedrooms, bathrooms,
        description, main_image, status
    FROM est_homes";

$where = $userId > 0 ? " WHERE status = 'Aktivs' OR owner_id = ?" : " WHERE status = 'Aktivs'";
$order = " ORDER BY created_at DESC";

$stmt = null;
$result = false;

if ($userId > 0) {
    $stmt = $savienojums->prepare($select . $where . $order);
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    $result = $savienojums->query($select . $where);
}

$homes = [];
$fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';

$rawHomes = [];
$ownerIds = [];

if ($result && $result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $ownerId = (int)($row['owner_id'] ?? 0);
        if ($ownerId > 0) {
            $ownerIds[] = $ownerId;
        }
        $rawHomes[] = $row;
    }
}

$ownersMap = [];
if ($ownerIds !== []) {
    $uniqueOwnerIds = array_unique($ownerIds);
    $placeholders = implode(',', array_fill(0, count($uniqueOwnerIds), '?'));
    $ownerStmt = $savienojums->prepare("SELECT lietotaja_id, lietotajvards, profila_bilde, plan FROM est_lietotaji WHERE lietotaja_id IN ($placeholders)");
    if ($ownerStmt) {
        $ownerStmt->bind_param(str_repeat('i', count($uniqueOwnerIds)), ...array_values($uniqueOwnerIds));
        $ownerStmt->execute();
        $ownerRes = $ownerStmt->get_result();
        while ($o = $ownerRes->fetch_assoc()) {
            $ownersMap[(int)$o['lietotaja_id']] = $o;
        }
        $ownerStmt->close();
    }
}

foreach ($rawHomes as $row) {
    $desc = (string)($row['description'] ?? '');
    $descShort = '';
    if ($desc !== '') {
        $slice = function_exists('mb_substr') ? mb_substr($desc, 0, 150) : substr($desc, 0, 150);
        $descShort = $slice . '...';
    }
    $type = (string)($row['type'] ?? '');
    $city = (string)($row['city'] ?? '');
    $locText = (string)($row['location_text'] ?? '');
    $ownerId = (int)($row['owner_id'] ?? 0);
    $owner = $ownersMap[$ownerId] ?? null;

    $homes[] = [
        'id' => (int)($row['id'] ?? 0),
        'status' => (string)($row['status'] ?? ''),
        'is_own' => $userId > 0 && $ownerId === $userId,
        'title' => (string)($row['title'] ?? ''),
        'city' => $city,
        'location' => $city . ($locText !== '' ? ', ' . $locText : ''),
        'type' => $type,
        'price' => (float)($row['price'] ?? 0),
        'beds' => (int)($row['bedrooms'] ?? 0),
        'baths' => (int)($row['bathrooms'] ?? 0),
        'size' => (int)($row['area'] ?? 0),
        'badge' => $type === 'rent' ? 'Izīrē' : 'Pārdod',
        'image' => media_absolute_url(!empty($row['main_image']) ? (string)$row['main_image'] : $fallbackImg),
        'desc' => $descShort,
        'owner_username' => (string)($owner['lietotajvards'] ?? ''),
        'owner_pfp' => media_absolute_url($owner['profila_bilde'] ?? ''),
        'owner_plan' => (string)($owner['plan'] ?? '')
    ];
}

if ($stmt) $stmt->close();
$savienojums->close();

echo json_encode($homes, JSON_UNESCAPED_UNICODE);
