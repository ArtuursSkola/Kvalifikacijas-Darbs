<?php
session_start();
$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dzīvoklis pie jūras - HomeEstate</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="property-page property-premium-v2">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="homes.php" class="active">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="owner.php">Kļūsti par īpašnieku</a></li>
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

    <div class="property-container">
        <main class="property-main">
            <div class="property-header">
                <h1>Dzīvoklis pie jūras</h1>
                <div class="property-meta">
                    <span><i class="fas fa-map-marker-alt"></i> Jūrmala, Mežaparka iela</span>
                    <span class="chip"><i class="fas fa-bed"></i> 3 guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> 95 m²</span>
                </div>
            </div>

            <div class="property-gallery">
                <div class="main-image">
                    <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80" alt="Dzīvoklis pie jūras">
                </div>
                <div class="thumb-images">
                    <img src="https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=600&q=70" alt="Guļamistaba" class="active">
                    <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=70" alt="Terase">
                    <img src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=600&q=70" alt="Viesistaba">
                </div>
            </div>

            <div class="property-details">
                <div class="tabs">
                    <button class="tab-link active" data-tab="description">Apraksts</button>
                    <button class="tab-link" data-tab="layout">Plānojums</button>
                    <button class="tab-link" data-tab="map">Karte</button>
                </div>

                <div id="description" class="tab-content active">
                    <h3>Par īpašumu</h3>
                    <p>Dzīvoklis ar terasi un skatu uz priežu mežu, tikai 5 minūšu gājienā līdz pludmalei. Plaša viesistaba, trīs guļamistabas, moderni iekārtots. Ideāla vieta atpūtai un dzīvošanai Jūrmalā.</p>
                    <ul class="amenities-list">
                        <li><i class="fas fa-check-circle"></i> 5 min līdz jūrai</li>
                        <li><i class="fas fa-check-circle"></i> Liela terase</li>
                        <li><i class="fas fa-check-circle"></i> Skats uz mežu</li>
                        <li><i class="fas fa-check-circle"></i> Pilnībā iekārtots</li>
                        <li><i class="fas fa-check-circle"></i> Pazemes stāvvieta</li>
                    </ul>
                </div>

                <div id="layout" class="tab-content">
                    <h3>Dzīvokļa plānojums</h3>
                    <p>Plaša viesistaba ar izeju uz terasi, apvienota ar virtuves zonu. Trīs izolētas guļamistabas, divi sanmezgli. Praktisks un ērts plānojums.</p>
                    <div class="spec-grid">
                        <div class="spec-card"><strong>Platība</strong><span>95 m²</span></div>
                        <div class="spec-card"><strong>Guļamistabas</strong><span>3</span></div>
                        <div class="spec-card"><strong>Stāvs</strong><span>3/6</span></div>
                        <div class="spec-card"><strong>Vannasistabas</strong><span>2</span></div>
                    </div>
                </div>

                <div id="map" class="tab-content">
                    <h3>Atrašanās vieta</h3>
                    <p>Īpašums atrodas klusā Jūrmalas rajonā, Mežaparka ielā. Blakus priežu mežs, jūra 5 minūšu gājienā. Tuvumā atrodas dzelzceļa stacija, veikali un kafejnīcas.</p>
                    <div class="map-placeholder">
                        <img src="https://i.imgur.com/5g2j3fD.png" alt="Karte ar atrašanās vietu">
                    </div>
                </div>
            </div>
        </main>

        <aside class="property-sidebar">
            <div class="sidebar-widget sidebar-price">
                <span class="price">780 € / mēn</span>
                <a href="mailto:info@homeestate.lv?subject=Interese%20par%20Dz%C4%ABvoklis%20pie%20j%C5%ABras" class="btn-primary">Sazināties ar aģentu</a>
            </div>
            <div class="sidebar-widget sidebar-agent">
                <h4>Pārdevējs</h4>
                <div class="agent-info">
                    <img src="https://i.pravatar.cc/150?img=12" alt="Aģenta foto" class="agent-photo">
                    <div>
                        <strong>Jānis Bērziņš</strong>
                        <span>HomeEstate</span>
                    </div>
                </div>
                <a href="tel:+37120000000" class="agent-contact"><i class="fas fa-phone-alt"></i> Zvanīt</a>
            </div>
            <div class="sidebar-widget sidebar-calculator">
                <h4>Īres izmaksas</h4>
                <div class="calc-row">
                    <span>Īres maksa</span>
                    <span>780 €</span>
                </div>
                <div class="calc-row">
                    <span>Komunālie (apt.)</span>
                    <span>200 €</span>
                </div>
                <div class="calc-row total">
                    <span>Kopā mēnesī</span>
                    <span>980 €</span>
                </div>
                <p class="calc-note">Aptuvenās izmaksas. Komunālie maksājumi var mainīties atkarībā no patēriņa.</p>
            </div>
        </aside>
    </div>

    <script src="script.js"></script>
</body>
</html>
