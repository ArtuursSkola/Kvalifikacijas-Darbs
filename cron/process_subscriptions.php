<?php
require_once __DIR__ . '/../con_db.php';

$stmt = $savienojums->prepare("
    SELECT lietotaja_id, lietotajvards, plans, plana_beigas
    FROM est_lietotaji 
    WHERE plans IN ('Sudraba', 'Zelta') 
    AND plana_beigas IS NOT NULL
    AND plana_beigas <= NOW()
    AND abonements = 'Aktivs'
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
        SET plana_beigas = ?, plans_aktivizets = NOW() 
        WHERE lietotaja_id = ?
    ");
    $updateStmt->bind_param('si', $newExpiresAt, $userId);
    $updateSuccess = $updateStmt->execute();
    $updateStmt->close();

    if ($updateSuccess) {
        $fakeStripeId  = 'pi_auto_' . time() . '_' . $userId;
        $fakeSessionId = 'cs_auto_' . time() . '_' . $userId;

        $insertStmt = $savienojums->prepare("
            INSERT INTO est_plana_pirkums 
            (user_id, plana_vards, maksa, valuta, nopirkts_at, beidzas_at, stripe_session_id, maksajuma_id, maksajuma_statuss)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'succeeded')
        ");
        $insertStmt->bind_param('isdssss', $userId, $planName, $amount, $currency, $newExpiresAt, $fakeSessionId, $fakeStripeId);
        $insertStmt->execute();
        $insertStmt->close();
    }
}
