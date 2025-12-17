<?php
session_start();
require_once "../stripe-php-master/init.php";
require_once "../con_db.php";
require_once "config.php";

// Must be logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login/login.php");
    exit;
}

$username = $_SESSION['username'];
$allowed_plans = ['Silver', 'Gold'];

// Get Stripe session ID
$session_id = $_GET['session_id'] ?? '';
if (!$session_id) {
    $_SESSION["pazinojums_modal"] = "Nav norādīts Stripe sesijas ID!";
    header("Location: ../index.php");
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
        header("Location: ../index.php");
        exit;
    }

    if (!in_array($plan_name, $allowed_plans)) {
        $_SESSION["pazinojums_modal"] = "Nepareizs plāna nosaukums!";
        header("Location: ../index.php");
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
            header("Location: ../index.php");
            exit;
        }

        $hasPlanCol = false;
        $planColCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'plan'");
        if ($planColCheck && mysqli_num_rows($planColCheck) > 0) {
            $hasPlanCol = true;
        }

        if ($hasPlanCol) {
            $stmtUp = $savienojums->prepare("UPDATE est_lietotaji SET loma='ipasnieks', plan=? WHERE lietotajvards=?");
            $stmtUp->bind_param("ss", $plan_name, $username);
            $stmtUp->execute();
            $stmtUp->close();
        } else {
            // Fallback: at least set role to owner
            mysqli_query($savienojums, "UPDATE est_lietotaji SET loma='ipasnieks' WHERE lietotajvards='" . mysqli_real_escape_string($savienojums, $username) . "'");
        }

        $_SESSION['role'] = 'ipasnieks';
        if ($hasPlanCol) {
            $_SESSION['plan'] = $plan_name;
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

header("Location: ../index.php");
exit;
?>
