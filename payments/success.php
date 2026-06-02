<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once "../con_db.php";
require_once "config.php";
require_once __DIR__ . '/../includes/account.php';

if (!isset($_SESSION['username'])) {
    header("Location: " . main_route('login'));
    exit;
}

$username = $_SESSION['username'];
$allowed_plans = ['Sudraba', 'Zelta'];

$session_id = $_GET['session_id'] ?? '';
if (!$session_id) {
    $_SESSION["pazinojums_modal"] = "Nav norādīts Stripe sesijas ID!";
    header("Location: " . main_route('home'));
    exit;
}

try {
    $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
    $payment_intent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent);

    $plana_vards = $checkout_session->metadata->plana_vards ?? ($checkout_session->metadata->plan_name ?? '');
    $plana_vards = trim(html_entity_decode($plana_vards, ENT_QUOTES, 'UTF-8'));

    if ($plana_vards === '') {
        $_SESSION["pazinojums_modal"] = "Nevar iegūt plāna nosaukumu no Stripe metadata!";
        header("Location: " . main_route('home'));
        exit;
    }

    if (!in_array($plana_vards, $allowed_plans)) {
        $_SESSION["pazinojums_modal"] = "Nepareizs plāna nosaukums!";
        header("Location: " . main_route('home'));
        exit;
    }

    if ($payment_intent->status === 'succeeded') {
        $stmt = $savienojums->prepare("SELECT lietotaja_id, loma, plans FROM est_lietotaji WHERE lietotajvards=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $_SESSION["pazinojums_modal"] = "Neizdevās atrast lietotāju datubāzē.";
            header("Location: " . main_route('home'));
            exit;
        }

        $userId = (int)($user['lietotaja_id'] ?? 0);
        $expiresAt = null;

        if ($userId > 0) {
            $activatedAt = date('Y-m-d H:i:s');
            $planExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmtUp = $savienojums->prepare("UPDATE est_lietotaji
                SET loma='ipasnieks', plans=?, plans_aktivizets=?, plana_beigas=?, abonements='Aktivs'
                WHERE lietotajvards=?");
            if ($stmtUp) {
                $stmtUp->bind_param("ssss", $plana_vards, $activatedAt, $planExpiresAt, $username);
                $stmtUp->execute();
                $stmtUp->close();
            }

            $expiresRes = $savienojums->prepare("SELECT plana_beigas FROM est_lietotaji WHERE lietotaja_id=? LIMIT 1");
            if ($expiresRes) {
                $expiresRes->bind_param('i', $userId);
                $expiresRes->execute();
                $r = $expiresRes->get_result();
                $row = $r ? $r->fetch_assoc() : null;
                $expiresAt = $row['plana_beigas'] ?? null;
                $expiresRes->close();
            }

            $amountPaid = isset($payment_intent->amount_received) ? ((int)$payment_intent->amount_received / 100) : 0;
            $currency = strtoupper((string)($payment_intent->currency ?? 'EUR'));
            $paymentStatus = (string)($payment_intent->status ?? 'succeeded');
            $purchasedAt = date('Y-m-d H:i:s');

            $ins = $savienojums->prepare("INSERT INTO est_plana_pirkums
                (user_id, plana_vards, maksa, valuta, nopirkts_at, beidzas_at, stripe_session_id, maksajuma_id, maksajuma_statuss)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($ins) {
                $stripeSessionId = (string)$session_id;
                $paymentIntentId = (string)($payment_intent->id ?? '');
                $ins->bind_param('isdssssss', $userId, $plana_vards, $amountPaid, $currency, $purchasedAt, $expiresAt, $stripeSessionId, $paymentIntentId, $paymentStatus);
                $ins->execute();
                $ins->close();
            }

            deactivateListingsExceedingPlanLimit($savienojums, $userId, $plana_vards);

            $updatedUser = fetchUserById($savienojums, $userId);
            if ($updatedUser) {
                storeUserSessionData($updatedUser);
            }
        }

        $transaction_id = $payment_intent->id;
        

        if ($plana_vards === 'Sudraba') {
            $_SESSION['plan_change_success'] = true;
            $_SESSION['plan_change_message'] = 'Jūs veiksmīgi iegādājāties Sudraba plānu!';
        } elseif ($plana_vards === 'Zelta') {
            $_SESSION['plan_change_success'] = true;
            $_SESSION['plan_change_message'] = 'Jūs veiksmīgi iegādājāties Zelta plānu!';
        } else {

            $message = "<h2>Maksājums veiksmīgs</h2><hr>";
            $message .= "<p>Maksājuma reference: <b>$transaction_id</b></p>";
            $message .= "<p>Aktivizēts plāns: <b>$plana_vards</b>. Tagad vari izveidot sludinājumus.</p>";
            $_SESSION["pazinojums_modal"] = $message;
        }
    }

} catch (\Exception $e) {
    $_SESSION["pazinojums_modal"] = "Nav iespējams iegūt maksājuma informāciju: " . stripe_friendly_error($e->getMessage());
}

header("Location: " . main_route('home'));
exit;
?>
