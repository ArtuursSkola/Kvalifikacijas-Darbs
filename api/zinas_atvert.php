<?php
session_start();
require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$otherUserId = $_GET['user_id'] ?? 0;

if (!$otherUserId) {
    echo json_encode([]);
    exit;
}


$stmt = $savienojums->prepare("
    SELECT z.*, u.lietotajvards as sender_name
    FROM est_zinas z
    INNER JOIN est_lietotaji u ON z.sutitaja_id = u.lietotaja_id
    WHERE (z.sutitaja_id = ? AND z.sanemeja_id = ?) 
    OR (z.sutitaja_id = ? AND z.sanemeja_id = ?)
    ORDER BY z.created_at ASC
");

$stmt->bind_param('iiii', $currentUserId, $otherUserId, $otherUserId, $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'zinas_id' => $row['zinas_id'],
        'sutitaja_id' => $row['sutitaja_id'],
        'sanemeja_id' => $row['sanemeja_id'],
        'zina' => $row['zina'],
        'izlasita' => $row['izlasita'],
        'created_at' => $row['created_at'],
        'sender_name' => $row['sender_name']
    ];
}

$stmt->close();


$updateStmt = $savienojums->prepare("
    UPDATE est_zinas 
    SET izlasita = 1 
    WHERE sutitaja_id = ? AND sanemeja_id = ? AND izlasita = 0
");
$updateStmt->bind_param('ii', $otherUserId, $currentUserId);
$updateStmt->execute();
$updateStmt->close();

echo json_encode($messages);
?>
