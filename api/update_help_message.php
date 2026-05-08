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

if (!isset($data['id']) || !isset($data['tema']) || !isset($data['apraksts'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$message_id = (int)$data['id'];
$tema = $data['tema'];
$apraksts = $data['apraksts'];
$user_id = $_SESSION['user_id'];

try {
    $query = "UPDATE est_palidzibas SET tema = ?, apraksts = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = $savienojums->prepare($query);
    $stmt->bind_param('sssi', $tema, $apraksts, $message_id, $user_id);
    $result = $stmt->execute();
    
    if ($result && $savienojums->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Message updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found or no changes made']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
