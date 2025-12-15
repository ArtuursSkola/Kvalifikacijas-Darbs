<?php
session_start();
require_once "../stripe-php-master/init.php";
require_once "../con_db.php";
require_once "config.php"; // Stripe API key

$offer_id = intval($_GET['offer_id'] ?? 0);
$session_id = $_GET['session_id'] ?? '';

if (!$offer_id || !$session_id) {
    $_SESSION['pazinojums'] = "Nav norādīts pakalpojums vai sesijas ID!";
    header("Location: ../index.php#pakalpojumi");
    exit;
}

try {
    $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
    $payment_intent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent);

if ($payment_intent->status === "succeeded") {
    // Increase order count in DB
   // Increase order count and set last order timestamp
        $stmt = $savienojums->prepare("
            UPDATE net_offers 
            SET orders = orders + 1, last_order_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $offer_id);
        $stmt->execute();
        $stmt->close();


    // Get user's address
    $stmt = $savienojums->prepare("SELECT adresse FROM net_users WHERE username=?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $adresse = $user['adresse'] ?? 'jūsu norādītā adrese';
    $stmt->close();

    // Random day and time
    $days = ["Pirmdien", "Otrdien", "Trešdien", "Ceturtdien", "Piektdien", "Sestdien", "Svētdien"];
    $times = ["10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00"];
    $random_day = $days[array_rand($days)];
    $random_time = $times[array_rand($times)];

    // Build message
    $_SESSION["pazinojums_modal"] = "<h2>Pakalpojums veiksmīgi pasūtīts!</h2>
        <hr>
        <p>Maksājuma reference: <b>{$payment_intent->id}</b></p>
        <p>Mūsu tehniskais darbinieks ieradīsies uz jūsu adresi: <b>{$adresse}</b> {$random_day} plkst. {$random_time}</p>";
}
 
} catch (Exception $e) {
    $_SESSION["pazinojums"] = "Kļūda maksājuma apstrādē: " . $e->getMessage();
}

header("Location: ../index.php#pakalpojumi");
exit;
