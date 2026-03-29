<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once "../con_db.php";
require_once "config.php";
require_once __DIR__ . '/../includes/account.php';

// Must be logged in
if (!isset($_SESSION['username'])) {
    header("Location: " . main_route('login'));
    exit;
}

$username = $_SESSION['username'];
$allowed_plans = ['Silver', 'Gold'];
ensureUserPlanColumns($savienojums);
ensurePlanPurchasesTable($savienojums);

// Get Stripe session ID
$session_id = $_GET['session_id'] ?? '';
if (!$session_id) {
    $_SESSION["pazinojums_modal"] = "Nav norādīts Stripe sesijas ID!";
    header("Location: " . main_route('home'));
    exit;
}

try {
    // Retrieve checkout session
    $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
    $payment_intent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent);

    // Get plan name from Stripe metadata
    $plan_name = $checkout_session->metadata->plan_name ?? '';
    $plan_name = trim(html_entity_decode($plan_name, ENT_QUOTES, 'UTF-8'));

    if ($plan_name === '') {
        $_SESSION["pazinojums_modal"] = "Nevar iegūt plāna nosaukumu no Stripe metadata!";
        header("Location: " . main_route('home'));
        exit;
    }

    if (!in_array($plan_name, $allowed_plans)) {
        $_SESSION["pazinojums_modal"] = "Nepareizs plāna nosaukums!";
        header("Location: " . main_route('home'));
        exit;
    }

    if ($payment_intent->status === 'succeeded') {
        // Fetch user data from est_lietotaji
        $stmt = $savienojums->prepare("SELECT lietotaja_id, loma, plan FROM est_lietotaji WHERE lietotajvards=? LIMIT 1");
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

        $hasPlanCol = false;
        $planColCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'plan'");
        if ($planColCheck && mysqli_num_rows($planColCheck) > 0) {
            $hasPlanCol = true;
        }

        $userId = (int)($user['lietotaja_id'] ?? 0);
        $expiresAt = null;

        if ($userId > 0) {
            // Always set owner role. If plan columns exist, also store activation + expiry (30 days).
            // We keep the older fallback behavior for hosts without the plan column.
            if ($hasPlanCol) {
                $stmtUp = $savienojums->prepare("UPDATE est_lietotaji
                    SET loma='ipasnieks', plan=?, plan_activated_at=NOW(), plan_expires_at=DATE_ADD(NOW(), INTERVAL 30 DAY)
                    WHERE lietotajvards=?");
                if ($stmtUp) {
                    $stmtUp->bind_param("ss", $plan_name, $username);
                    $stmtUp->execute();
                    $stmtUp->close();
                }

                $expiresRes = $savienojums->prepare("SELECT plan_expires_at FROM est_lietotaji WHERE lietotaja_id=? LIMIT 1");
                if ($expiresRes) {
                    $expiresRes->bind_param('i', $userId);
                    $expiresRes->execute();
                    $r = $expiresRes->get_result();
                    $row = $r ? $r->fetch_assoc() : null;
                    $expiresAt = $row['plan_expires_at'] ?? null;
                    $expiresRes->close();
                }
            } else {
                // Fallback: at least set role to owner
                $stmtUp = $savienojums->prepare("UPDATE est_lietotaji SET loma='ipasnieks' WHERE lietotajvards=?");
                if ($stmtUp) {
                    $stmtUp->bind_param("s", $username);
                    $stmtUp->execute();
                    $stmtUp->close();
                }
            }

            // Record purchase for history (even if user table lacks plan cols).
            $amountPaid = isset($payment_intent->amount_received) ? ((int)$payment_intent->amount_received / 100) : 0;
            $currency = strtoupper((string)($payment_intent->currency ?? 'EUR'));
            $paymentStatus = (string)($payment_intent->status ?? 'succeeded');

            $ins = $savienojums->prepare("INSERT INTO est_plan_purchases
                (user_id, plan_name, amount_paid, currency, purchased_at, expires_at, stripe_session_id, payment_intent_id, payment_status)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            if ($ins) {
                $stripeSessionId = (string)$session_id;
                $paymentIntentId = (string)($payment_intent->id ?? '');
                $ins->bind_param('isdsssss', $userId, $plan_name, $amountPaid, $currency, $expiresAt, $stripeSessionId, $paymentIntentId, $paymentStatus);
                $ins->execute();
                $ins->close();
            }

            $updatedUser = fetchUserById($savienojums, $userId);
            if ($updatedUser) {
                storeUserSessionData($updatedUser);
            } else {
                $_SESSION['role'] = 'ipasnieks';
                if ($hasPlanCol) {
                    $_SESSION['plan'] = $plan_name;
                }
            }
        }

        $transaction_id = $payment_intent->id;

        $message = "<h2>Maksājums veiksmīgs</h2><hr>";
        $message .= "<p>Maksājuma reference: <b>$transaction_id</b></p>";
        $message .= "<p>Aktivizēts plāns: <b>$plan_name</b>. Tagad vari izveidot sludinājumus.</p>";
        $_SESSION["pazinojums_modal"] = $message;
    }

} catch (\Exception $e) {
    $_SESSION["pazinojums_modal"] = "Nav iespējams iegūt maksājuma informāciju: " . $e->getMessage();
}

header("Location: " . main_route('home'));
exit;
?>
