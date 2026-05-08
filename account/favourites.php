<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../includes/account.php';

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit;
}

$pageTitle = 'Favorīti - HomeEstate';
$extraStyles = ['homes'];
$bodyClass = 'favorites-page';
include __DIR__ . '/../includes/header.php';
?>

<section class="search-listings-section" style="padding-top: 30px;">
    <div class="results-header" style="margin-top: 0;">
        <h2>Favorīti</h2>
    </div>
    <div id="favorites-page-empty" style="display:none; font-weight:700; color: rgba(44, 62, 80, 0.7); padding: 10px 2px;">Nav favorītu.</div>
    <div id="favorites-page-results" class="listing-grid"></div>
</section>


<?php include __DIR__ . '/../includes/footer.php'; ?>

