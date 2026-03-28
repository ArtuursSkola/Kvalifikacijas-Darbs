<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

// Helper to determine active link
function isActive($pageName) {
    $current = basename($_SERVER['PHP_SELF']);
    return ($current === $pageName) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'HomeEstate - Tavs mājoklis'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo asset_path('Images/Logo.png'); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_path('style.css'); ?>">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo asset_path('css/' . $style . '.css'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">

    <nav class="navbar" id="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="<?php echo main_route('home'); ?>" class="<?php echo isActive('index.php'); ?>">Sākums</a></li>
            <li><a href="<?php echo main_route('property.list'); ?>" class="<?php echo isActive('homes.php'); ?>">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="<?php echo main_route('owner'); ?>" class="<?php echo isActive('owner.php'); ?>">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="<?php echo main_route('property.create'); ?>" class="<?php echo isActive('newhome.php'); ?>">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="<?php echo main_route('about'); ?>" class="<?php echo isActive('about.php'); ?>">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: 600;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?><?php echo $plan ? ' (' . htmlspecialchars($plan) . ')' : ''; ?>!</span>
                <a href="<?php echo main_route('logout', ['i' => 1]); ?>" class="btn-register" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>
