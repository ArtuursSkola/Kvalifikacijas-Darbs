<?php
session_start();
require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

$stmt = $savienojums->prepare("
    SELECT 
        u.lietotaja_id AS user_id,
        u.lietotajvards AS username,
        u.profila_bilde AS profila_bilde,
        latest.zina AS last_message,
        latest.created_at AS created_at,
        (
            SELECT COUNT(*) 
            FROM est_zinas 
            WHERE sutitaja_id = u.lietotaja_id 
              AND sanemeja_id = ? 
              AND izlasita = 0
        ) AS unread_count
    FROM est_lietotaji u
    INNER JOIN (
        SELECT 
            CASE WHEN sutitaja_id = ? THEN sanemeja_id ELSE sutitaja_id END AS other_user_id,
            zina,
            created_at
        FROM est_zinas
        WHERE sutitaja_id = ? OR sanemeja_id = ?
        ORDER BY created_at DESC
    ) AS latest ON u.lietotaja_id = latest.other_user_id
    WHERE u.lietotaja_id != ?
    GROUP BY u.lietotaja_id, u.lietotajvards, u.profila_bilde
    ORDER BY MAX(latest.created_at) DESC
");

$stmt->bind_param('iiiii', $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$chatList = [];
while ($row = $result->fetch_assoc()) {
    $chatList[] = [
        'user_id'      => $row['user_id'],
        'username'     => $row['username'],
        'profila_bilde' => $row['profila_bilde'] ?? '',
        'last_message' => $row['last_message'] ?? '',
        'created_at'   => $row['created_at'],
        'unread_count' => (int)$row['unread_count'],
    ];
}

$stmt->close();
echo json_encode($chatList);
?>