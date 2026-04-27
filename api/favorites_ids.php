<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';


$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT home_id 
        FROM est_favoriti 
        WHERE user_id = ? 
        ORDER BY created_at DESC";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();

$ids = [];

while ($row = $result->fetch_assoc()) {
    $ids[] = (int)$row['home_id'];
}

$stmt->close();

echo json_encode($ids, JSON_UNESCAPED_UNICODE);