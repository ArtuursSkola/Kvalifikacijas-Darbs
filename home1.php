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
    <title>Moderns dzīvoklis centrā - HomeEstate</title>
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
                <h1>Moderns dzīvoklis centrā</h1>
                <div class="property-meta">
                    <span><i class="fas fa-map-marker-alt"></i> Rīga, Brīvības iela</span>
                    <span class="chip"><i class="fas fa-bed"></i> 2 guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> 62 m²</span>
                </div>
            </div>

            <div class="property-gallery">
                <div class="main-image">
                    <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80" alt="Moderns dzīvoklis centrā">
                </div>
                <div class="thumb-images">
                    <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=70" alt="Dzīvojamā zona" class="active">
                    <img src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=600&q=70" alt="Virtuve">
                    <img src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=70" alt="Skats pa logu">
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
                    <p>Gaišs, pilnībā iekārtots dzīvoklis ar balkonu un pazemes stāvvietu. Perfekts centram, ar ātru piekļuvi sabiedriskajam transportam. Panorāmas logi nodrošina lielisku skatu un daudz dabiskās gaismas.</p>
                    <ul class="amenities-list">
                        <li><i class="fas fa-check-circle"></i> Pilnībā mēbelēts</li>
                        <li><i class="fas fa-check-circle"></i> Balkons ar panorāmu</li>
                        <li><i class="fas fa-check-circle"></i> Pazemes stāvvieta</li>
                        <li><i class="fas fa-check-circle"></i> Augstie griesti</li>
                        <li><i class="fas fa-check-circle"></i> Apsardze 24/7</li>
                    </ul>
                </div>

                <div id="layout" class="tab-content">
                    <h3>Dzīvokļa plānojums</h3>
                    <p>Pārdomāts plānojums, kas ietver plašu viesistabu, kas apvienota ar virtuvi, divas izolētas guļamistabas un vannasistabu. No viesistabas ir izeja uz balkonu.</p>
                    <div class="spec-grid">
                        <div class="spec-card"><strong>Platība</strong><span>62 m²</span></div>
                        <div class="spec-card"><strong>Guļamistabas</strong><span>2</span></div>
                        <div class="spec-card"><strong>Stāvs</strong><span>5/8</span></div>
                        <div class="spec-card"><strong>Vannasistabas</strong><span>1</span></div>
                    </div>
                </div>

                <div id="map" class="tab-content">
                    <h3>Atrašanās vieta</h3>
                    <p>Īpašums atrodas Rīgas klusajā centrā, Brīvības ielā. Tuvumā atrodas viss nepieciešamais komfortablai dzīvei: veikali, skolas, bērnudārzi, sporta zāles un sabiedriskā transporta pieturas.</p>
                    <div class="map-placeholder">
                        <img src="https://i.imgur.com/5g2j3fD.png" alt="Karte ar atrašanās vietu">
                    </div>
                </div>
            </div>
        </main>

        <aside class="property-sidebar">
            <div class="sidebar-widget sidebar-price">
                <span class="price">520 € / mēn</span>
                <a href="mailto:info@homeestate.lv?subject=Interese%20par%20Moderns%20dz%C4%ABvoklis%20centr%C4%81" class="btn-primary">Sazināties ar aģentu</a>
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
                    <span id="calc-rent">520 €</span>
                </div>
                <div class="calc-row">
                    <span>Komunālie (apt.)</span>
                    <span id="calc-utilities">150 €</span>
                </div>
                <div class="calc-row total">
                    <span>Kopā mēnesī</span>
                    <span id="calc-total">670 €</span>
                </div>
                <p class="calc-note">Aptuvenās izmaksas. Komunālie maksājumi var mainīties atkarībā no patēriņa.</p>
            </div>
        </aside>
    </div>

    <script src="script.js"></script>
</body>
</html>
