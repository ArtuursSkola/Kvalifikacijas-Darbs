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

if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_token_main'] ?? '', (string)$_POST['csrf'])) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'CSRF marķiera validācija neizdevās!', 'section' => 'plan'];
    header('Location: ' . $redirectTo);
    exit;
}

$mode = (string)($_POST['mode'] ?? '');
if (!in_array($mode, ['cancel', 'continue'], true)) {
    header('Location: ' . $redirectTo);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$currentUser = fetchUserById($savienojums, $userId, $_SESSION['user_type'] ?? 'user');
if (!$currentUser) {
    header('Location: ' . main_route('logout'));
    exit;
}

$currentUser = expirePlanIfNeeded($savienojums, $currentUser);
if (!userHasActivePaidPlan($currentUser)) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Nav aktīva abonementa.', 'section' => 'plan'];
    header('Location: ' . $redirectTo);
    exit;
}

$plan = (string)($currentUser['plans'] ?? '');
if (!in_array($plan, ['Sudraba', 'Zelta'], true)) {
    $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Nav aktīva abonementa.', 'section' => 'plan'];
    header('Location: ' . $redirectTo);
    exit;
}

$newValue = $mode === 'cancel' ? 'Neaktivs' : 'Aktivs';
$stmt = $savienojums->prepare("UPDATE est_lietotaji SET abonements = ? WHERE lietotaja_id = ?");
if ($stmt) {
    $stmt->bind_param('si', $newValue, $userId);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['settings_flash'] = [
    'type' => 'success',
    'message' => $mode === 'cancel'
        ? 'Abonements atcelts. Plāns būs aktīvs līdz termiņa beigām.'
        : 'Abonements turpināsies un tiks atjaunots pēc termiņa beigām.',
    'section' => 'plan'
];

header('Location: ' . $redirectTo);
exit;

