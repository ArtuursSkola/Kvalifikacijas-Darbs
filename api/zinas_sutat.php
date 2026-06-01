<?php
session_start();
require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if (!$receiverId || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

if ((int)$receiverId === 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot message system']);
    exit;
}

if ((int)$currentUserId === (int)$receiverId) {
    echo json_encode(['success' => false, 'message' => 'Cannot message yourself']);
    exit;
}


$checkStmt = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE lietotaja_id = ?");
$checkStmt->bind_param('i', $receiverId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$checkStmt->close();


$createdAt = (new DateTime('now', new DateTimeZone('Europe/Riga')))->format('Y-m-d H:i:s');
$stmt = $savienojums->prepare("
    INSERT INTO est_zinas (sutitaja_id, sanemeja_id, zina, izlasita, created_at) 
    VALUES (?, ?, ?, 0, ?)
");

$stmt->bind_param('iiss', $currentUserId, $receiverId, $message, $createdAt);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}

$stmt->close();
?>
