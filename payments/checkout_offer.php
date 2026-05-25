<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../con_db.php';

if (!isset($_SESSION['username']) || !isset($_GET['offer_id'])) {
    header("Location: " . main_route('login'));
    exit;
}

$offer_id = intval($_GET['offer_id']);
$username = $_SESSION['username'];

$stmt = $savienojums->prepare("SELECT name, price FROM net_offers WHERE id=?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();
$offer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$offer) {
    $_SESSION["pazinojums"] = "Nepareizs pakalpojums.";
    header("Location: " . main_route('home') . '#pakalpojumi');
    exit;
}

try {
    $session = \Stripe\Checkout\Session::create([
        "payment_method_types" => ["card"],
        "line_items" => [[
            "price_data" => [
                "currency" => "eur",
                "unit_amount" => $offer['price'] * 100,
                "product_data" => ["name" => $offer['name']]
            ],
            "quantity" => 1
        ]],
        "mode" => "payment",
        "success_url" => payments_base_url() . "success_offer.php?offer_id={$offer_id}&session_id={CHECKOUT_SESSION_ID}",
        "cancel_url" => main_route_absolute('owner'),
        "locale" => "lv"
    ]);

    header("Location: " . $session->url);
    exit;
} catch (\Exception $e) {
    die(stripe_friendly_error($e->getMessage()));
}
