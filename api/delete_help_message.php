<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../con_db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$message_id = (int)$data['id'];
$user_id = (int)$_SESSION['user_id'];

try {
    $query = "DELETE FROM est_palidziba WHERE id = ? AND lietotaja_id = ?";
    $stmt = $savienojums->prepare($query);
    $stmt->bind_param('ii', $message_id, $user_id);
    $result = $stmt->execute();
    
    if ($result && $savienojums->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
