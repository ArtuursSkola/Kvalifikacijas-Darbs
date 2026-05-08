<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../con_db.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$ids = [];

if ($userId > 0 && isset($savienojums) && $savienojums instanceof mysqli) {
    $stmt = $savienojums->prepare("SELECT home_id FROM est_favoriti WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ids[] = (int)$row['home_id'];
            }
        }
        $stmt->close();
    }
}

echo json_encode($ids, JSON_UNESCAPED_UNICODE);