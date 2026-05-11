<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';


$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$homeId = (int)($_POST['home_id'] ?? 0);

if (!$homeId) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_home_id']);
    exit;
}

$savienojums->begin_transaction();

try {
    $check = $savienojums->prepare("
        SELECT 1 
        FROM est_favoriti 
        WHERE user_id = ? AND home_id = ?
        LIMIT 1
    ");
    $check->bind_param('ii', $userId, $homeId);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();


    if ($exists) {
        $stmt = $savienojums->prepare("
            DELETE FROM est_favoriti 
            WHERE user_id = ? AND home_id = ?
        ");
        $stmt->bind_param('ii', $userId, $homeId);
        $stmt->execute();
        $stmt->close();

        $stmt = $savienojums->prepare("
            UPDATE est_homes 
            SET favoriti = GREATEST(favoriti - 1, 0)
            WHERE id = ?
        ");
        $stmt->bind_param('i', $homeId);
        $stmt->execute();
        $stmt->close();
        $savienojums->commit();

        echo json_encode([
            'home_id' => $homeId,
            'favorited' => false
        ]);
        exit;
    }
    $createdAt = (new DateTime('now', new DateTimeZone('Europe/Riga')))->format('Y-m-d H:i:s');
    $stmt = $savienojums->prepare("
        INSERT INTO est_favoriti (user_id, home_id, created_at)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iis', $userId, $homeId, $createdAt);
    $stmt->execute();
    $stmt->close();
    $stmt = $savienojums->prepare("
        UPDATE est_homes 
        SET favoriti = favoriti + 1 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $homeId);
    $stmt->execute();
    $stmt->close();

    $savienojums->commit();
    echo json_encode([
        'home_id' => $homeId,
        'favorited' => true
    ]);

} catch (Throwable $e) {
    $savienojums->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}