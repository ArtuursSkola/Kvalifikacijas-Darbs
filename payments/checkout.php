<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/config.php';

// Validate plan and price
if (!isset($_GET['plan'], $_GET['price'])) {
    die("Nav norādīts plāns vai cena!");
}

$plan = trim(html_entity_decode($_GET['plan'], ENT_QUOTES, 'UTF-8'));
$price = (int) $_GET['price']; // cents

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'success_url' => payments_base_url() . 'success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => main_route_absolute('owner'),
        'locale' => 'lv',
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $price,
                'product_data' => [
                    'name' => $plan . ' plāns'
                ]
            ]
        ]],
        'metadata' => [
            'plana_vards' => $plan
        ]
    ]);

    header('Location: ' . $checkout_session->url);
    exit;

} catch (\Exception $e) {
    die('Neizdevās izveidot maksājuma sesiju: ' . $e->getMessage());
}
