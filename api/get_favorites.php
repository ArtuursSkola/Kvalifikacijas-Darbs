<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';


$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$sql = "
SELECT 
    h.*, 
    f.created_at AS favorited_at
FROM est_favoriti f
JOIN est_homes h ON h.id = f.home_id
WHERE f.user_id = ?
ORDER BY f.created_at DESC
";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

$homes = [];
$ownerIds = [];

while ($row = $result->fetch_assoc()) {
    $ownerId = (int)$row['ipasnieka_id'];
    if ($ownerId) {
        $ownerIds[] = $ownerId;
    }
    $homes[] = $row;
}

$stmt->close();

$ownersMap = [];

if ($ownerIds) {
    $ownerIds = array_unique($ownerIds);
    $placeholders = implode(',', array_fill(0, count($ownerIds), '?'));

    $ownerSql = "
        SELECT lietotaja_id, lietotajvards, profila_bilde, plan
        FROM est_lietotaji
        WHERE lietotaja_id IN ($placeholders)
    ";

    $ownerStmt = $savienojums->prepare($ownerSql);
    $ownerStmt->bind_param(str_repeat('i', count($ownerIds)), ...$ownerIds);
    $ownerStmt->execute();

    $ownerResult = $ownerStmt->get_result();

    while ($owner = $ownerResult->fetch_assoc()) {
        $ownersMap[(int)$owner['lietotaja_id']] = $owner;
    }

    $ownerStmt->close();
}

$response = [];

$fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';

foreach ($homes as $row) {
    $ownerId = (int)$row['ipasnieka_id'];
    $owner = $ownersMap[$ownerId] ?? null;

    $desc = $row['apraksts'];
    $descShort = $desc ? mb_substr($desc, 0, 150) . '...' : '';

    $city = $row['pilseta'];
    $location = $city;

    if (!empty($row['atrasanas_vieta'])) {
        $location .= ', ' . $row['atrasanas_vieta'];
    }

    $type = (string)($row['veids'] ?? '');
    if ($type === 'rent') $type = 'ire';
    if ($type === 'buy') $type = 'pardod';
    $badgeText = $type === 'ire' ? 'Īrēšana' : ($type === 'istermina_ire' ? 'Īstermiņa īre' : 'Pārdod');

    $response[] = [
        'id' => (int)$row['id'],
        'status' => $row['statuss'],
        'is_own' => $ownerId === $userId,
        'title' => $row['nosaukums'],
        'city' => $city,
        'location' => $location,
        'type' => $type,
        'category' => $row['kategorija'],
        'price' => (float)$row['cena'],
        'beds' => (int)$row['gulamistabas'],
        'baths' => (int)$row['vannasistabas'],
        'size' => (int)$row['platiba'],
        'badge' => $badgeText,
        'image' => media_absolute_url($row['galvenais_attels'] ?: $fallbackImg),
        'desc' => $descShort,
        'owner_username' => $owner['lietotajvards'] ?? '',
        'owner_pfp' => media_absolute_url($owner['profila_bilde'] ?? ''),
        'owner_plan' => $owner['plan'] ?? '',
        'favorited' => true,
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
