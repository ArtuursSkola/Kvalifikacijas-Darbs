<?php
session_start();
require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$currentUserId = $_SESSION['user_id'];


$stmt = $savienojums->prepare("
    UPDATE est_zinas 
    SET izlasita = 1 
    WHERE sanemeja_id = ? AND izlasita = 0
");

$stmt->bind_param('i', $currentUserId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark messages as read']);
}

$stmt->close();
?>
