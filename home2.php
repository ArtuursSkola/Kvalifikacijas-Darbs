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
    <title>Ģimenes māja Pierīgā - HomeEstate</title>
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
                <h1>Ģimenes māja Pierīgā</h1>
                <div class="property-meta">
                    <span><i class="fas fa-map-marker-alt"></i> Mārupe, Ziedu iela</span>
                    <span class="chip"><i class="fas fa-bed"></i> 4 guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> 182 m²</span>
                </div>
            </div>

            <div class="property-gallery">
                <div class="main-image">
                    <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=80" alt="Ģimenes māja Pierīgā">
                </div>
                <div class="thumb-images">
                    <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=600&q=70" alt="Ārpuse" class="active">
                    <img src="https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=600&q=70" alt="Viesistaba">
                    <img src="https://images.unsplash.com/photo-1613553474173-8752b513f3a3?auto=format&fit=crop&w=600&q=70" alt="Virtuve">
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
                    <p>Plaša privātmāja ar dārzu, terasi un garāžu divām automašīnām. Klusa iela, ērta piekļuve Rīgas centram. Ideāli piemērota ģimenei, kas novērtē komfortu un dabas tuvumu.</p>
                    <ul class="amenities-list">
                        <li><i class="fas fa-check-circle"></i> Privāts dārzs</li>
                        <li><i class="fas fa-check-circle"></i> Liela terase</li>
                        <li><i class="fas fa-check-circle"></i> Garāža 2 auto</li>
                        <li><i class="fas fa-check-circle"></i> Siltās grīdas</li>
                        <li><i class="fas fa-check-circle"></i> Mierīgi kaimiņi</li>
                    </ul>
                </div>

                <div id="layout" class="tab-content">
                    <h3>Mājas plānojums</h3>
                    <p>Pārdomāts plānojums divos stāvos. Pirmajā stāvā plaša viesistaba, kas apvienota ar virtuvi, kabinets un sanmezgls. Otrajā stāvā trīs guļamistabas un liela vannasistaba.</p>
                    <div class="spec-grid">
                        <div class="spec-card"><strong>Platība</strong><span>182 m²</span></div>
                        <div class="spec-card"><strong>Guļamistabas</strong><span>4</span></div>
                        <div class="spec-card"><strong>Stāvi</strong><span>2</span></div>
                        <div class="spec-card"><strong>Zemes platība</strong><span>1200 m²</span></div>
                    </div>
                </div>

                <div id="map" class="tab-content">
                    <h3>Atrašanās vieta</h3>
                    <p>Īpašums atrodas Mārupē, Ziedu ielā. Tuvumā atrodas viss nepieciešamais komfortablai dzīvei: Mārupes Valsts ģimnāzija, bērnudārzi, veikali (Rimi, Ikea) un sabiedriskā transporta pieturas.</p>
                    <div class="map-placeholder">
                        <img src="https://i.imgur.com/5g2j3fD.png" alt="Karte ar atrašanās vietu">
                    </div>
                </div>
            </div>
        </main>

        <aside class="property-sidebar">
            <div class="sidebar-widget sidebar-price">
                <span class="price">215 000 €</span>
                <a href="mailto:info@homeestate.lv?subject=Interese%20par%20%C4%A3imenes%20m%C4%81ju%20Pier%C4%ABg%C4%81" class="btn-primary">Sazināties ar aģentu</a>
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
                <h4>Pirkuma kalkulators</h4>
                <div class="calc-row">
                    <span>Cena</span>
                    <span>215 000 €</span>
                </div>
                <div class="calc-row">
                    <span>Pirmā iemaksa (15%)</span>
                    <span>32 250 €</span>
                </div>
                <div class="calc-row total">
                    <span>Mēneša maks. (apt.)</span>
                    <span>~820 €</span>
                </div>
                <p class="calc-note">Aptuvenās izmaksas pie 3.5% likmes uz 30 gadiem. Sazinieties ar mums, lai saņemtu precīzu piedāvājumu.</p>
            </div>
        </aside>
    </div>

    <script src="script.js"></script>
</body>
</html>
