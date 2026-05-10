<?php
ob_start();

$cronFile = __DIR__ . '/../cron/last_run.txt';
$currentTime = time();

// Nolasa pēdējo darbības laiku
$lastRun = file_exists($cronFile) ? (int)file_get_contents($cronFile) : 0;

// Palaiž katru minūti
if ($currentTime - $lastRun > 60) {
    file_put_contents($cronFile, $currentTime);
    include_once __DIR__ . '/../cron/process_subscriptions.php';
}

ob_end_clean();
?>