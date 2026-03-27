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
    <title>Meklēt īpašumus - HomeEstate</title>
    <link rel="icon" type="image/png" href="Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/homes.css">
</head>
<body class="homes-page">
    <nav class="navbar">
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
                <span style="margin-right: 15px; font-weight: 600; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login/logout.php" class="btn-register" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">Iziet</a>
            <?php else: ?>
                <a href="login/login.php" class="btn-login" style="color: inherit;">Ielogoties</a>
                <a href="login/register.php" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <header class="homes-hero">
        <div class="homes-hero__inner">
            <div class="homes-hero__text">
                <p class="badge-pill"><i class="fas fa-search"></i> Meklē un filtrē</p>
                <h1>Atrodi sev piemērotu īpašumu</h1>
                <p>Izvēlies pilsētu, tipu un budžetu. Plašs piedāvājumu klāsts ar detalizētiem aprakstiem un ātru saziņu ar īpašniekiem.</p>
                <div class="hero-actions">
                    <button id="filter-hero" class="btn-hero-search" type="button">
                        <i class="fas fa-filter"></i>
                        Sākt meklēšanu
                    </button>
                    <span class="hero-note"><i class="fas fa-shield-alt"></i> Pārbaudīti sludinājumi</span>
                </div>
            </div>
            <div class="homes-hero__card">
                <div class="hero-metric">
                    <span class="metric-label">Pārbaudīti sludinājumi</span>
                    <span class="metric-value">1,250+</span>
                    <span class="metric-sub">Aktīvi piedāvājumi</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Pilsētas</span>
                    <span class="metric-value">15+</span>
                    <span class="metric-sub">Visā Latvijā</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Veiksmīgi darījumi</span>
                    <span class="metric-value">380+</span>
                    <span class="metric-sub">Apmierināti klienti</span>
                </div>
            </div>
        </div>
    </header>

    <section class="search-listings-section">
        <div class="filter-shell">
            <div class="filter-header">
                <h3><i class="fas fa-sliders-h"></i> Filtrēt īpašumus</h3>
                <span class="filter-count" id="results-count">0 rezultāti</span>
            </div>
            <div class="filters">
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Pilsēta</label>
                    <select id="filter-city">
                        <option value="">Visas pilsētas</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-home"></i> Tips</label>
                    <select id="filter-type">
                        <option value="">Visi tipi</option>
                        <option value="rent">Īrēt</option>
                        <option value="buy">Pirkt</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-euro-sign"></i> Maks. cena</label>
                    <input type="number" id="filter-price" placeholder="Piem., 200000">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-bed"></i> Guļamistabas</label>
                    <input type="number" id="filter-beds" min="0" max="10" step="1" placeholder="Min. skaits">
                </div>
                <button id="filter-apply" class="btn-filter" type="button">
                    <i class="fas fa-search"></i>
                    Meklēt
                </button>
            </div>
            <div class="filter-hint"><i class="fas fa-info-circle"></i> Rezultāti tiek atjaunoti uzreiz pēc filtra piemērošanas.</div>
        </div>

        <div class="results-header">
            <h2>Pieejamie <span>īpašumi</span></h2>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Režģis"><i class="fas fa-th-large"></i></button>
                <button class="view-btn" data-view="list" title="Saraksts"><i class="fas fa-list"></i></button>
            </div>
        </div>

        <div id="homes-results" class="listing-grid"></div>
        
        <div id="homes-empty" class="search-empty" style="display:none;">
            <i class="fas fa-home"></i>
            <h3>Nav atrasto īpašumu</h3>
            <p>Mēģiniet mainīt filtrus vai meklēt citā pilsētā.</p>
        </div>
    </section>

    <div id="homes-modal" class="listing-modal" style="display:none;">
        <div class="listing-modal__backdrop"></div>
        <div class="listing-modal__content">
            <button class="modal-close" id="homes-modal-close" aria-label="Aizvērt">&times;</button>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="homes-modal-img" src="" alt="">
                    <span id="homes-modal-badge" class="badge"></span>
                </div>
                <div class="modal-info">
                    <h3 id="homes-modal-title"></h3>
                    <p id="homes-modal-location" class="location"><i class="fas fa-map-marker-alt"></i> <span></span></p>
                    <div class="features">
                        <span id="homes-modal-beds"><i class="fas fa-bed"></i> </span>
                        <span id="homes-modal-size"><i class="fas fa-ruler-combined"></i> </span>
                        <span><i class="fas fa-bath"></i> 1 vannas</span>
                    </div>
                    <p id="homes-modal-desc" class="description"></p>
                    <div class="modal-price-row">
                        <span id="homes-modal-price" class="price"></span>
                        <a id="homes-modal-contact" class="btn-contact" href="#">
                            <i class="fas fa-envelope"></i>
                            Sazināties
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>Home<span>Estate</span></h3>
                    <p>Mūsdienīga platforma nekustamā īpašuma īrei un pārdošanai. Mēs palīdzam atrast jūsu nākamās mājas.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Navigācija</h4>
                    <ul>
                        <li><a href="index.php">Sākums</a></li>
                        <li><a href="homes.php">Īpašumi</a></li>
                        <li><a href="about.php">Par mums</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Juridiskā Info</h4>
                    <ul>
                        <li><a href="#">Lietošanas noteikumi</a></li>
                        <li><a href="#">Privātuma politika</a></li>
                        <li><a href="#">Sīkdatņu politika</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontakti</h4>
                    <ul>
                        <li><i class="fas fa-envelope" style="margin-right: 8px; color: #30b607;"></i> info@homeestate.lv</li>
                        <li><i class="fas fa-phone" style="margin-right: 8px; color: #30b607;"></i> +371 20000000</li>
                        <li><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #30b607;"></i> Rīga, Latvija</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HomeEstate. Visas tiesības aizsargātas.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
