<?php
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit;
}

$redirectTo = $_POST['redirect_to'] ?? ($_SERVER['HTTP_REFERER'] ?? main_route('account.settings_page'));
$redirectTo = trim((string)$redirectTo) !== '' ? (string)$redirectTo : main_route('account.settings_page');

$userId = (int)$_SESSION['user_id'];
$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$newPasswordRepeat = (string)($_POST['new_password_repeat'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $newPasswordRepeat === '') {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Lūdzu aizpildi visus paroles laukus.'];
    header('Location: ' . $redirectTo);
    exit;
}

if ($newPassword !== $newPasswordRepeat) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Jaunās paroles nesakrīt.'];
    header('Location: ' . $redirectTo);
    exit;
}

if (strlen($newPassword) < 8) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Jaunajai parolei jābūt vismaz 8 simbolus garai.'];
    header('Location: ' . $redirectTo);
    exit;
}

$user = fetchUserById($savienojums, $userId);
if (!$user) {
    header('Location: ' . main_route('logout'));
    exit;
}

if (!password_verify($currentPassword, (string)($user['parole'] ?? ''))) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Pašreizējā parole nav pareiza.'];
    header('Location: ' . $redirectTo);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $savienojums->prepare("UPDATE est_lietotaji SET parole = ? WHERE lietotaja_id = ?");
if ($stmt) {
    $stmt->bind_param('si', $newHash, $userId);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['settings_flash'] = ['type' => 'success', 'message' => 'Parole nomainīta veiksmīgi.'];
header('Location: ' . $redirectTo);
exit;

