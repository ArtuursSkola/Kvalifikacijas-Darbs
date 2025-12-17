<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login/login.php');
    exit();
}
$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kļūsti par īpašnieku - HomeEstate</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="owner-page">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="homes.php">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="owner.php" class="active">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="newhome.php">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="about.php">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: bold; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login/logout.php" class="btn-register" style="background-color: #c0392b;">Iziet</a>
            <?php else: ?>
                <a href="login/login.php" class="btn-login">Ielogoties</a>
                <a href="login/register.php" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <header class="owner-hero">
        <div class="owner-hero__content">
            <p class="badge-pill">Pay-to-List modelis</p>
            <h1>Publicē savus īpašumus. Saņem rezultātus.</h1>
            <p>Tu maksā par sludinājuma publicēšanu, nevis par darījumu. Mēs nopelnām, kad tava sludinājuma vieta ir aktīva.</p>
            <div class="owner-hero__bullets">
                <span><i class="fas fa-bullhorn"></i> Prioritāte meklēšanā</span>
                <span><i class="fas fa-id-card"></i> Verificēta īpašnieka badge</span>
                <span><i class="fas fa-camera"></i> Līdz 10+ fotogrāfijām</span>
            </div>
        </div>
    </header>

    <section class="owner-model">
        <div class="container">
            <div class="owner-model__grid">
                <div>
                    <h2>Kāpēc Pay-to-List?</h2>
                    <p>Saņem ieņēmumus uzreiz — pirms īrnieks vai pircējs pat piesaka vizīti. Mēs neiekasējam komisiju no taviem darījumiem; tu maksā tikai par redzamību.</p>
                    <ul class="owner-list">
                        <li><i class="fas fa-check"></i> Ieņēmumi pirms darījuma — tu kontrolē procesu.</li>
                        <li><i class="fas fa-check"></i> Redzamība meklētājos un tematiskajos blokos.</li>
                        <li><i class="fas fa-check"></i> Lietotāji redz tevi kā uzticamu īpašnieku.</li>
                    </ul>
                </div>
                <div class="owner-model__card">
                    <h3>Darbojas vienkārši:</h3>
                    <ol>
                        <li>Izvēlies plānu.</li>
                        <li>Apstiprini maksājumu ar Stripe.</li>
                        <li>Publicē un pārvaldi savus sludinājumus.</li>
                    </ol>
                    <p class="muted">Maksājums notiek caur Stripe drošo checkout.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="owner-plans" id="plans">
        <div class="container">
            <h2>Plāni īpašniekiem</h2>
            <p class="owner-plans__sub">Izvēlies sev ērtāko Pay-to-List plānu. Cenas bez slēptām komisijām.</p>
            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-head">
                        <span class="plan-name">Free</span>
                        <span class="plan-price">€0</span>
                        <span class="plan-period">1 aktīvs sludinājums</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 1 aktīvs sludinājums</li>
                        <li><i class="fas fa-check"></i> Līdz 3 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta publikācija</li>
                        <li class="muted"><i class="fas fa-minus"></i> Nav prioritātes meklēšanā</li>
                    </ul>
                    <a class="plan-btn ghost" href="#">Sākt bez maksas</a>
                </div>

                <div class="plan-card highlight">
                    <div class="plan-head">
                        <span class="plan-badge">Visbiežāk izvēlas</span>
                        <span class="plan-name">Silver</span>
                        <span class="plan-price">€9.99</span>
                        <span class="plan-period">mēnesī · 5 sludinājumi</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 5 aktīvi sludinājumi</li>
                        <li><i class="fas fa-check"></i> Līdz 10 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta meklēšanas ranga līmenis</li>
                        <li><i class="fas fa-check"></i> Īpašnieka badge profilā</li>
                    </ul>
                    <a class="plan-btn" href="payments/checkout.php?plan=Silver&price=999">Pirkt ar Stripe</a>
                </div>

                <div class="plan-card">
                    <div class="plan-head">
                        <span class="plan-name">Gold</span>
                        <span class="plan-price">€29.99</span>
                        <span class="plan-period">mēnesī · Bez limita</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Neierobežoti sludinājumi</li>
                        <li><i class="fas fa-check"></i> 20+ fotogrāfijas</li>
                        <li><i class="fas fa-check"></i> Featured meklēšanā (top pozīcijas)</li>
                        <li><i class="fas fa-check"></i> "Verified Owner" badge</li>
                    </ul>
                    <a class="plan-btn" href="payments/checkout.php?plan=Gold&price=2999">Pirkt ar Stripe</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-strip">
        <div class="container">
            <div class="cta-strip__inner">
                <div>
                    <h3>Esi gatavs publicēt?</h3>
                    <p>Aktivizē plānu un pievieno sludinājumu minūtēs.</p>
                </div>
                <a class="btn-owner" href="#plans">Izvēlēties plānu</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container footer-content">
            <div class="footer-col">
                <h3>HomeEstate</h3>
                <p>Mūsdienīga platforma nekustamā īpašuma īrei un pārdošanai.</p>
            </div>
            <div class="footer-col">
                <h3>Saites</h3>
                <ul>
                    <li><a href="about.php">Par mums</a></li>
                    <li><a href="#">Lietošanas noteikumi</a></li>
                    <li><a href="#">Privātuma politika</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontakti</h3>
                <p>info@homeestate.lv</p>
                <p>+371 20000000</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 HomeEstate. Visas tiesības aizsargātas.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
