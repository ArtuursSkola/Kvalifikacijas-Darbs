<?php
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit;
}

$userId = (int)$_SESSION['user_id'];
$newRole = 'ipasnieks';

$stmt = $savienojums->prepare("UPDATE est_lietotaji SET loma = ? WHERE lietotaja_id = ?");
if ($stmt) {
    $stmt->bind_param('si', $newRole, $userId);
    if ($stmt->execute()) {
        $user = fetchUserById($savienojums, $userId);
        if ($user) {
            storeUserSessionData($user);
        }
        $_SESSION['settings_flash'] = ['type' => 'success', 'message' => 'Apsveicam! Jūs tagad esat īpašnieks.'];
    } else {
        $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Kļūda atjauninot lomu.'];
    }
    $stmt->close();
}

header('Location: ' . main_route('account.settings_page'));
exit;
