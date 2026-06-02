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
$selectedPlan = trim((string)($_POST['plan'] ?? ''));
$allowedPlans = ['Bezmaksas'];

$sql = "UPDATE est_lietotaji SET loma = ?";
$types = 's';
$params = [$newRole];

if ($selectedPlan !== '' && in_array($selectedPlan, $allowedPlans, true)) {
    $sql .= ", plans = ?, plans_aktivizets = NOW(), plana_beigas = NULL";
    $types .= 's';
    $params[] = $selectedPlan;
}

$sql .= " WHERE lietotaja_id = ?";
$types .= 'i';
$params[] = $userId;

$stmt = $savienojums->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $user = fetchUserById($savienojums, $userId);
        if ($user) {
            storeUserSessionData($user);
        }
        if ($selectedPlan === 'Bezmaksas') {
            deactivateListingsExceedingPlanLimit($savienojums, $userId, 'Bezmaksas');
            $_SESSION['plan_change_success'] = true;
            $_SESSION['plan_change_message'] = 'Jums tagad ir Bezmaksas plāns!';
            header('Location: ' . main_route('owner') . '#plans');
            exit;
        }
        $_SESSION['settings_flash'] = ['type' => 'success', 'message' => 'Apsveicam! Jūs tagad esat īpašnieks.'];
    } else {
        $_SESSION['settings_flash'] = ['type' => 'error', 'message' => 'Kļūda atjauninot lomu.'];
    }
    $stmt->close();
}

header('Location: ' . main_route('account.settings_page'));
exit;
