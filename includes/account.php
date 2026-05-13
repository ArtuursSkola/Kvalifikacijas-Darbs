<?php



function isValidMysqlDateTime(?string $value): bool
{
    $value = trim((string)$value);
    if ($value === '') {
        return false;
    }
    if (str_starts_with($value, '0000-00-00')) {
        return false;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return false;
    }
    return $ts >= 0;
}

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
    $_SESSION['plans'] = $user['plans'] ?? 'Nekads';
    $_SESSION['profile_picture'] = $user['profila_bilde'] ?? null;
    $_SESSION['plans_aktivizets'] = $user['plans_aktivizets'] ?? null;
    $_SESSION['plana_beigas'] = $user['plana_beigas'] ?? null;
}

function expirePlanIfNeeded(mysqli $conn, array $user): array
{
    $plan = $user['plans'] ?? 'Nekads';
    $expiresAt = $user['plana_beigas'] ?? null;

    if (!in_array($plan, ['Sudraba', 'Zelta'], true) || empty($expiresAt)) {
        return $user;
    }

    if (!isValidMysqlDateTime($expiresAt)) {
        $expiresTimestamp = 0;
    } else {
        $expiresTimestamp = strtotime($expiresAt);
    }

    if ($expiresTimestamp >= time()) {
        return $user;
    }

    $stmt = $conn->prepare("UPDATE est_lietotaji SET plans = 'Nekads', plans_aktivizets = NULL, plana_beigas = NULL WHERE lietotaja_id = ?");
    if ($stmt) {
        $userId = (int)$user['lietotaja_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    $user['plans'] = 'Nekads';
    $user['plans_aktivizets'] = null;
    $user['plana_beigas'] = null;

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

    $plan = $user['plans'] ?? 'Nekads';
    $expiresAt = $user['plana_beigas'] ?? null;

    return in_array($plan, ['Sudraba', 'Zelta'], true)
        && !empty($expiresAt)
        && isValidMysqlDateTime($expiresAt)
        && strtotime($expiresAt) >= time();
}

function userHasActiveOwnerPlan(?array $user): bool
{
    if (!$user) {
        return false;
    }

    $plan = (string)($user['plans'] ?? 'Nekads');
    if ($plan === '' || $plan === 'Nekads') {
        return false;
    }

    if (in_array($plan, ['Sudraba', 'Zelta'], true)) {
        return userHasActivePaidPlan($user);
    }

    return $plan === 'Bezmaksas';
}

function getCurrentPlanLabel(?array $user): string
{
    if (!$user) {
        return 'Nekads';
    }

    $plan = trim((string)($user['plans'] ?? ''));
    if ($plan === '') {
        return 'Nekads';
    }

    if (in_array($plan, ['Sudraba', 'Zelta'], true) && !userHasActivePaidPlan($user)) {
        return 'Nekads';
    }

    return $plan;
}

function getPlanDaysLeft(?array $user): ?int
{
    if (!userHasActivePaidPlan($user)) {
        return null;
    }

    if (!isValidMysqlDateTime((string)$user['plana_beigas'])) {
        return null;
    }

    $expiresTimestamp = strtotime((string)$user['plana_beigas']);
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
    $stmt = $conn->prepare("SELECT plana_vards, maksa, valuta, nopirkts_at, beidzas_at, maksajuma_statuss
        FROM est_plana_pirkums
        WHERE user_id = ?
        ORDER BY nopirkts_at DESC");

    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($result && $row = $result->fetch_assoc()) {
            if (in_array($row['plana_vards'], ['Bezmaksas', 'Nekads'], true)) {
                continue;
            }
            $history[] = $row;
        }
        $stmt->close();
    }

    if ($history === [] && $currentUser) {
        $currentPlan = getCurrentPlanLabel($currentUser);
        $activatedAt = (string)($currentUser['plans_aktivizets'] ?? '');
        if (in_array($currentPlan, ['Sudraba', 'Zelta'], true) && isValidMysqlDateTime($activatedAt)) {
            $expiresAt = (string)($currentUser['plana_beigas'] ?? '');
            $history[] = [
                'plana_vards' => $currentPlan,
                'maksa' => null,
                'valuta' => 'EUR',
                'nopirkts_at' => $activatedAt,
                'beidzas_at' => isValidMysqlDateTime($expiresAt) ? $expiresAt : null,
                'maksajuma_statuss' => 'succeeded',
            ];
        }
    }

    return $history;
}


function fetchUserPropertyTransactions(mysqli $conn, int $userId): array
{
    $items = [];
    try {
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
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, "doesn't exist") !== false
            || stripos($msg, 'does not exist') !== false
            || stripos($msg, 'Unknown table') !== false) {
            return [];
        }
        throw $e;
    }

    return $items;
}
