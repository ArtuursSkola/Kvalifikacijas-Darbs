<?php
require_once __DIR__ . '/../con_db.php';


$stmt = $savienojums->prepare("
    SELECT lietotaja_id, lietotajvards, plans, plan_expires_at
    FROM est_lietotaji 
    WHERE plans IN ('Sudraba', 'Zelta') 
    AND plan_expires_at IS NOT NULL
    AND plan_expires_at <= NOW()
");
$stmt->execute();
$result = $stmt->get_result();
$usersToRenew = [];

while ($row = $result->fetch_assoc()) {
    $usersToRenew[] = $row;
}

$planPrices = [
    'Sudraba' => 9.99,
    'Zelta'   => 29.99,
];

foreach ($usersToRenew as $user) {
    $userId   = (int)$user['lietotaja_id'];
    $planName = $user['plans'];
    $amount   = $planPrices[$planName] ?? 29.99;
    $currency = 'EUR';

    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $updateStmt = $savienojums->prepare("
        UPDATE est_lietotaji 
        SET plan_expires_at = ?, plan_activated_at = NOW() 
        WHERE lietotaja_id = ?
    ");
    $updateStmt->bind_param('si', $newExpiresAt, $userId);
    $updateSuccess = $updateStmt->execute();
    $updateStmt->close();

    if ($updateSuccess) {
        $fakeStripeId  = 'pi_auto_' . time() . '_' . $userId;
        $fakeSessionId = 'cs_auto_' . time() . '_' . $userId;

        $insertStmt = $savienojums->prepare("
            INSERT INTO est_plan_purchases 
            (user_id, plan_name, amount_paid, currency, purchased_at, expires_at, stripe_session_id, payment_intent_id, payment_status)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'succeeded')
        ");
        $insertStmt->bind_param('isdssss', $userId, $planName, $amount, $currency, $newExpiresAt, $fakeSessionId, $fakeStripeId);
        $insertStmt->execute();
        $insertStmt->close();
    }
}