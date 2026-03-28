<?php
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit;
}

$redirectTo = $_POST['redirect_to'] ?? ($_SERVER['HTTP_REFERER'] ?? main_route('home'));
$redirectTo = trim((string)$redirectTo) !== '' ? (string)$redirectTo : main_route('home');
$userId = (int)$_SESSION['user_id'];
$username = trim((string)($_POST['username'] ?? ''));

if ($username === '') {
    header('Location: ' . $redirectTo);
    exit;
}

$currentUser = fetchUserById($savienojums, $userId);
if (!$currentUser) {
    header('Location: ' . main_route('logout'));
    exit;
}

$stmt = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE lietotajvards = ? AND lietotaja_id != ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('si', $username, $userId);
    $stmt->execute();
    $exists = $stmt->get_result();
    if ($exists && $exists->num_rows > 0) {
        $stmt->close();
        header('Location: ' . $redirectTo);
        exit;
    }
    $stmt->close();
}

$profilePicture = $currentUser['profila_bilde'] ?? null;
if (isset($_FILES['profile_picture']) && is_array($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $extension = strtolower(pathinfo((string)$_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        $fileName = uniqid('profile_', true) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            $profilePicture = 'uploads/' . $fileName;
        }
    }
}

$stmt = $savienojums->prepare("UPDATE est_lietotaji SET lietotajvards = ?, profila_bilde = ? WHERE lietotaja_id = ?");
if ($stmt) {
    $stmt->bind_param('ssi', $username, $profilePicture, $userId);
    $stmt->execute();
    $stmt->close();
}

$updatedUser = fetchUserById($savienojums, $userId);
if ($updatedUser) {
    storeUserSessionData($updatedUser);
}

header('Location: ' . $redirectTo);
exit;
