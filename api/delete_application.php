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

$application_id = (int)$data['id'];
$user_id = $_SESSION['user_id'];

try {
    $query = "DELETE FROM applications WHERE id = ? AND user_id = ?";
    $stmt = $savienojums->prepare($query);
    $stmt->bind_param('ii', $application_id, $user_id);
    $result = $stmt->execute();
    
    if ($result && $savienojums->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Application deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
