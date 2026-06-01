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
        COALESCE(u.lietotaja_id, l.other_user_id) AS user_id,
        COALESCE(u.lietotajvards, 'Sistēma') AS username,
        COALESCE(u.profila_bilde, '') AS profila_bilde,
        z.zina AS last_message,
        z.created_at AS created_at,
        (
            SELECT COUNT(*) 
            FROM est_zinas 
            WHERE (sutitaja_id = l.other_user_id OR (l.other_user_id = 0 AND sutitaja_id IS NULL))
              AND sanemeja_id = ? 
              AND izlasita = 0
        ) AS unread_count
    FROM (
        SELECT other_user_id, MAX(zinas_id) AS last_id
        FROM (
            SELECT zinas_id, 
                CASE 
                    WHEN sutitaja_id IS NULL THEN 0
                    WHEN sutitaja_id = ? THEN sanemeja_id 
                    ELSE sutitaja_id 
                END AS other_user_id
            FROM est_zinas
            WHERE sutitaja_id = ? OR sanemeja_id = ?
        ) t
        GROUP BY other_user_id
    ) l
    INNER JOIN est_zinas z ON z.zinas_id = l.last_id
    LEFT JOIN est_lietotaji u ON u.lietotaja_id = l.other_user_id
    WHERE l.other_user_id != ?
    ORDER BY z.created_at DESC
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
