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
$status = (string)$data['status'];
$user_id = (int)$_SESSION['user_id'];

$map = [
    'pending' => 'Jauns',
    'approved' => 'Apstiprinats',
    'rejected' => 'Noraidits',
    'Jauns' => 'Jauns',
    'Apstiprinats' => 'Apstiprinats',
    'Rezervets' => 'Rezervets',
    'Noraidits' => 'Noraidits',
];

if (!isset($map[$status])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$dbStatus = $map[$status];

try {
    $query = "UPDATE est_pieteikumi SET statuss = ? WHERE id = ? AND lietotaja_id = ?";
    $stmt = $savienojums->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    $stmt->bind_param('sii', $dbStatus, $application_id, $user_id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result && $savienojums->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Application updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Application not found or no changes made']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
