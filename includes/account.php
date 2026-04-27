<?php



function fetchUserById(mysqli $conn, int $userId, string $type = 'user'): ?array
{
    $table = ($type === 'admin') ? 'est_admin' : 'est_lietotaji';
    $idCol = ($type === 'admin') ? 'admin_id' : 'lietotaja_id';

    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idCol = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($user && $type === 'admin') {
        $user['lietotaja_id'] = $user['admin_id'];
    }

    return $user ?: null;
}

function storeUserSessionData(array $user): void
{
    $_SESSION['user_id'] = (int)($user['lietotaja_id'] ?? $_SESSION['user_id'] ?? 0);
    $_SESSION['username'] = $user['lietotajvards'] ?? $_SESSION['username'] ?? '';
    $_SESSION['role'] = $user['loma'] ?? $_SESSION['role'] ?? 'lietotajs';
    $_SESSION['plan'] = $user['plan'] ?? null;
    $_SESSION['profile_picture'] = $user['profila_bilde'] ?? null;
    $_SESSION['plan_activated_at'] = $user['plan_activated_at'] ?? null;
    $_SESSION['plan_expires_at'] = $user['plan_expires_at'] ?? null;
}

function expirePlanIfNeeded(mysqli $conn, array $user): array
{
    $plan = $user['plan'] ?? null;
    $expiresAt = $user['plan_expires_at'] ?? null;

    if (!in_array($plan, ['Silver', 'Gold'], true) || empty($expiresAt)) {
        return $user;
    }

    $expiresTimestamp = strtotime($expiresAt);
    if ($expiresTimestamp === false || $expiresTimestamp >= time()) {
        return $user;
    }

    $stmt = $conn->prepare("UPDATE est_lietotaji SET plan = NULL, plan_activated_at = NULL, plan_expires_at = NULL WHERE lietotaja_id = ?");
    if ($stmt) {
        $userId = (int)$user['lietotaja_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    $user['plan'] = null;
    $user['plan_activated_at'] = null;
    $user['plan_expires_at'] = null;

    return $user;
}

function loadCurrentUserContext(mysqli $conn): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $user = fetchUserById($conn, (int)$_SESSION['user_id'], $_SESSION['user_type'] ?? 'user');
    if (!$user) {
        return null;
    }

    $user = expirePlanIfNeeded($conn, $user);
    storeUserSessionData($user);

    return $user;
}

function userHasActivePaidPlan(?array $user): bool
{
    if (!$user) {
        return false;
    }

    $plan = $user['plan'] ?? null;
    $expiresAt = $user['plan_expires_at'] ?? null;

    return in_array($plan, ['Silver', 'Gold'], true)
        && !empty($expiresAt)
        && strtotime($expiresAt) !== false
        && strtotime($expiresAt) >= time();
}

function getCurrentPlanLabel(?array $user): string
{
    if (!$user) {
        return 'Free';
    }

    $plan = trim((string)($user['plan'] ?? ''));
    if ($plan === '') {
        return 'Free';
    }

    if (in_array($plan, ['Silver', 'Gold'], true) && !userHasActivePaidPlan($user)) {
        return 'Free';
    }

    return $plan;
}

function getPlanDaysLeft(?array $user): ?int
{
    if (!userHasActivePaidPlan($user)) {
        return null;
    }

    $expiresTimestamp = strtotime((string)$user['plan_expires_at']);
    if ($expiresTimestamp === false) {
        return null;
    }

    return max(0, (int)ceil(($expiresTimestamp - time()) / 86400));
}

function userProfileImageUrl(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    // Uses shared resolver so absolute URLs remain untouched and relative uploads work from any page.
    return media_url($path);
}

function fetchUserPlanHistory(mysqli $conn, int $userId, ?array $currentUser = null): array
{

    $history = [];
    $stmt = $conn->prepare("SELECT plan_name, amount_paid, currency, purchased_at, expires_at, payment_status
        FROM est_plan_purchases
        WHERE user_id = ?
        ORDER BY purchased_at DESC");

    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && $row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
    }

    if ($history === [] && $currentUser && !empty($currentUser['plan_activated_at'])) {
        $history[] = [
            'plan_name' => getCurrentPlanLabel($currentUser),
            'amount_paid' => null,
            'currency' => 'EUR',
            'purchased_at' => $currentUser['plan_activated_at'],
            'expires_at' => $currentUser['plan_expires_at'] ?? null,
            'payment_status' => userHasActivePaidPlan($currentUser) ? 'active' : 'expired',
        ];
    }

    return $history;
}


function fetchUserPropertyTransactions(mysqli $conn, int $userId): array
{

    $items = [];
    $stmt = $conn->prepare("SELECT t.transaction_type, t.amount, t.currency, t.created_at, t.home_id,
        h.nosaukums as home_title, h.pilseta as home_city, h.veids as home_type
        FROM est_property_transactions t
        LEFT JOIN est_homes h ON h.id = t.home_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC");

    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && $row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
    }

    return $items;
}
