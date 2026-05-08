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

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$application_id = (int)$data['id'];
$status = $data['status'];
$user_id = $_SESSION['user_id'];

$valid_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $query = "UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = $savienojums->prepare($query);
    $stmt->bind_param('ssi', $status, $application_id, $user_id);
    $result = $stmt->execute();
    
    if ($result && $savienojums->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Application updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found or no changes made']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
