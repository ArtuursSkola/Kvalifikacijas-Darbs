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
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Jaunās paroles nesakrīt.', 'section' => 'password'];
    header('Location: ' . $redirectTo);
    exit;
}

$errors = [];
if (strlen($newPassword) < 8) {
    $errors[] = 'Parolei jābūt vismaz 8 simbolus garai.';
}
if (!preg_match('/[0-9]/', $newPassword)) {
    $errors[] = 'Parolei jāsatur vismaz viens skaitlis.';
}
if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
    $errors[] = 'Parolei jāsatur vismaz viens simbols.';
}

if (!empty($errors)) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => implode(' ', $errors), 'section' => 'password'];
    header('Location: ' . $redirectTo);
    exit;
}

$userType = $_SESSION['user_type'] ?? 'user';
$user = fetchUserById($savienojums, $userId, $userType);

if (!$user) {
    header('Location: ' . main_route('logout'));
    exit;
}

if (!password_verify($currentPassword, (string)($user['parole'] ?? ''))) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Pašreizējā parole nav pareiza.', 'section' => 'password'];
    header('Location: ' . $redirectTo);
    exit;
}

$table = ($userType === 'admin') ? 'est_admin' : 'est_lietotaji';
$idCol = ($userType === 'admin') ? 'admin_id' : 'lietotaja_id';

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $savienojums->prepare("UPDATE $table SET parole = ? WHERE $idCol = ?");
if ($stmt) {
    $stmt->bind_param('si', $newHash, $userId);
    if ($stmt->execute()) {
        $_SESSION['password_change_success'] = true;
        $_SESSION['settings_flash'] = ['type' => 'success', 'message' => 'Parole nomainīta veiksmīgi.', 'section' => 'password'];
    } else {
        $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Sistēmas kļūda, mēģiniet vēlreiz.', 'section' => 'password'];
    }
    $stmt->close();
}

header('Location: ' . $redirectTo);
exit;

