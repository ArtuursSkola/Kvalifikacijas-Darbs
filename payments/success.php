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
$allowed_plans = ['Ekonomiskais plāns', 'Normālais plāns', 'Ātrais plāns'];

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
        // Fetch user data
        $stmt = $savienojums->prepare("SELECT plan, plan_expires_at, adresse FROM net_users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $previous_plan = $user['plan'] ?? 'Nav';
        $adresse = $user['adresse'] ?? 'jūsu norādītā adrese';
        $now = new DateTime();

        // ✅ Calculate new expiry date correctly
        if ($previous_plan === $plan_name && !empty($user['plan_expires_at'])) {
            $current_expiry = new DateTime($user['plan_expires_at']);
            if ($current_expiry > $now) {
                $expiry = $current_expiry->modify("+30 days");
            } else {
                $expiry = (clone $now)->modify("+30 days");
            }
        } else {
            $expiry = (clone $now)->modify("+30 days");
        }

        $purchase_time = $now->format('Y-m-d H:i:s');
        $expiry_str = $expiry->format('Y-m-d H:i:s');

        // ✅ Update user plan with correct columns
        $stmt = $savienojums->prepare("UPDATE net_users SET plan=?, plan_purchased_at=?, plan_expires_at=? WHERE username=?");
        $stmt->bind_param("ssss", $plan_name, $purchase_time, $expiry_str, $username);
        $stmt->execute();
        $stmt->close();

        $transaction_id = $payment_intent->id;

        // Build success message
        $message = "<h2>Maksājums veiksmīgi izdevies</h2><hr>";
        $message .= "<p>Maksājuma reference: <b>$transaction_id</b></p>";

        if ($previous_plan === $plan_name && $previous_plan !== 'Nav') {
            $message .= "<p>Jūsu <b>$plan_name</b> plāns ir pagarināts par 30 dienām!</p>";
        } elseif ($previous_plan === 'Nav') {
            $message .= "<p>Jūsu <b>$plan_name</b> plāns ir aktivizēts!</p>";
            $message .= "<p>Jaunais rūteris tiks piegādāts uz jūsu adresi: <b>$adresse</b> 1–3 dienu laikā.</p>";
        } else {
            $message .= "<p>Jūs esat pārgājis uz jaunu plānu: <b>$plan_name</b>.</p>";
            $message .= "<p>Jaunais rūteris tiks piegādāts uz jūsu adresi: <b>$adresse</b> 1-3 dienu laikā.</p>";
            $message .= "<p>Lūdzu, atgrieziet veco rūteri 7 dienu laikā, savādāk tiks piemērots sods līdz pat 50 euro.</p>";
        }

        $_SESSION["pazinojums_modal"] = $message;
    }

} catch (\Exception $e) {
    $_SESSION["pazinojums_modal"] = "Nav iespējams iegūt maksājuma informāciju: " . $e->getMessage();
}

header("Location: ../index.php");
exit;
?>
