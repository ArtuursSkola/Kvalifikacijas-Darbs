<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

// Get home ID from URL
$homeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($homeId <= 0) {
    header('Location: ' . main_route('property.list'));
    exit;
}

// Fetch property + owner info from database
$stmt = $savienojums->prepare("SELECT h.*, u.lietotajvards as owner_username, u.epasts as owner_email, u.profila_bilde as owner_avatar
    FROM est_homes h
    LEFT JOIN est_lietotaji u ON u.lietotaja_id = h.owner_id
    WHERE h.id = ?
    LIMIT 1");
$stmt->bind_param('i', $homeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . main_route('property.list'));
    exit;
}

$home = $result->fetch_assoc();
$stmt->close();

$ownerName = trim((string)($home['owner_username'] ?? ''));
$ownerEmail = trim((string)($home['owner_email'] ?? ''));
$ownerAvatarUrl = userProfileImageUrl($home['owner_avatar'] ?? '');
$ownerInitial = strtoupper(substr($ownerName !== '' ? $ownerName : 'U', 0, 1));

// Parse amenities
$amenities = [];
if (!empty($home['amenities'])) {
    $amenities = array_map('trim', explode(',', $home['amenities']));
}

// Default images if not set
$mainImage = $home['main_image'] ?: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80';
$thumb1 = $home['thumb1'] ?: 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=70';
$thumb2 = $home['thumb2'] ?: 'https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=600&q=70';
$thumb3 = $home['thumb3'] ?: 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=600&q=70';

// Price formatting
$priceDisplay = $home['type'] === 'rent' 
    ? number_format($home['price'], 0, ',', ' ') . ' € / mēn'
    : number_format($home['price'], 0, ',', ' ') . ' €';

$rentPrice = $home['rent_price'] ?: $home['price'];
$utilitiesPrice = $home['utilities_price'] ?: 0;
$totalPrice = $home['total_price'] ?: ($rentPrice + $utilitiesPrice);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($home['title']); ?> - HomeEstate</title>
    <link rel="icon" type="image/png" href="<?php echo asset_path('Images/Logo.png'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_path('style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_path('css/home.css'); ?>">
</head>
<body class="property-detail-page">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
            <li><a href="<?php echo main_route('property.list'); ?>" class="active">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="<?php echo main_route('owner'); ?>">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="<?php echo main_route('property.create'); ?>">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="<?php echo main_route('about'); ?>">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: 600; color: var(--primary);">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="<?php echo main_route('logout'); ?>" class="btn-register" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="back-nav">
        <a href="<?php echo main_route('property.list'); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Atpakaļ uz meklēšanu
        </a>
    </div>

    <div class="property-wrapper">
        <main class="property-main">
            <div class="property-header">
                <h1><?php echo htmlspecialchars($home['title']); ?></h1>
                <div class="property-meta">
                    <span class="type-badge <?php echo $home['type']; ?>">
                        <?php echo $home['type'] === 'rent' ? 'Izīrē' : 'Pārdod'; ?>
                    </span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['city'] . ', ' . $home['location_text']); ?></span>
                    <span class="chip"><i class="fas fa-bed"></i> <?php echo $home['bedrooms']; ?> guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> <?php echo $home['area']; ?> m²</span>
                    <?php if ($home['property_category']): ?>
                    <span class="chip"><i class="fas fa-home"></i> <?php echo htmlspecialchars($home['property_category']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="property-gallery">
                <div class="main-image">
                    <img id="gallery-main" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($home['title']); ?>">
                </div>
                <div class="thumb-images">
                    <img src="<?php echo htmlspecialchars($thumb1); ?>" alt="Attēls 1" class="active" onclick="changeImage(this)">
                    <img src="<?php echo htmlspecialchars($thumb2); ?>" alt="Attēls 2" onclick="changeImage(this)">
                    <img src="<?php echo htmlspecialchars($thumb3); ?>" alt="Attēls 3" onclick="changeImage(this)">
                </div>
            </div>

            <div class="property-details">
                <div class="tabs">
                    <button class="tab-link active" data-tab="description">Apraksts</button>
                    <button class="tab-link" data-tab="layout">Plānojums</button>
                    <button class="tab-link" data-tab="map">Atrašanās vieta</button>
                </div>

                <div id="description" class="tab-content active">
                    <h3>Par īpašumu</h3>
                    <p><?php echo nl2br(htmlspecialchars($home['description'])); ?></p>
                    
                    <?php if (!empty($amenities)): ?>
                    <h3 style="margin-top: 24px;">Ērtības un aprīkojums</h3>
                    <ul class="amenities-list">
                        <?php foreach ($amenities as $amenity): ?>
                        <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($amenity); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div id="layout" class="tab-content">
                    <h3>Īpašuma plānojums</h3>
                    <p><?php echo nl2br(htmlspecialchars($home['layout_text'] ?: 'Plānojuma informācija nav pieejama.')); ?></p>
                    
                    <div class="spec-grid">
                        <div class="spec-card">
                            <strong>Platība</strong>
                            <span><?php echo $home['area']; ?> m²</span>
                        </div>
                        <div class="spec-card">
                            <strong>Guļamistabas</strong>
                            <span><?php echo $home['bedrooms']; ?></span>
                        </div>
                        <div class="spec-card">
                            <strong>Stāvs</strong>
                            <span><?php echo htmlspecialchars($home['floor_info'] ?: $home['floor']); ?></span>
                        </div>
                        <div class="spec-card">
                            <strong>Vannasistabas</strong>
                            <span><?php echo $home['bathrooms']; ?></span>
                        </div>
                    </div>
                </div>

                <div id="map" class="tab-content">
                    <h3>Atrašanās vieta</h3>
                    <p><?php echo nl2br(htmlspecialchars($home['map_text'] ?: 'Īpašums atrodas ' . $home['city'] . ', ' . $home['address'])); ?></p>
                    <div class="map-placeholder">
                        <img src="https://i.imgur.com/5g2j3fD.png" alt="Karte ar atrašanās vietu">
                    </div>
                </div>
            </div>
        </main>

        <aside class="property-sidebar">
            <div class="sidebar-widget sidebar-owner">
                <h4>Kontaktpersona</h4>
                <div class="agent-info">
                    <?php if ($ownerAvatarUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($ownerAvatarUrl); ?>" alt="Profila bilde" class="agent-photo">
                    <?php else: ?>
                        <div class="agent-photo-fallback" aria-hidden="true"><?php echo htmlspecialchars($ownerInitial); ?></div>
                    <?php endif; ?>
                    <div>
                        <strong><?php echo htmlspecialchars($ownerName !== '' ? $ownerName : 'Īpašnieks'); ?></strong>
                        <span>Īpašnieks</span>
                    </div>
                </div>
                <?php if ($ownerEmail !== ''): ?>
                    <a href="mailto:<?php echo htmlspecialchars($ownerEmail); ?>?subject=Interese%20par%20<?php echo urlencode($home['title']); ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Sazināties
                    </a>
                <?php else: ?>
                    <a href="mailto:info@homeestate.lv?subject=Interese%20par%20<?php echo urlencode($home['title']); ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Sazināties
                    </a>
                <?php endif; ?>
            </div>

            <div class="sidebar-widget sidebar-price">
                <span class="price"><?php echo $priceDisplay; ?></span>
                <a href="mailto:info@homeestate.lv?subject=Interese%20par%20<?php echo urlencode($home['title']); ?>" class="btn-primary">
                    <i class="fas fa-envelope"></i> Sazināties ar aģentu
                </a>
            </div>
            
            <div class="sidebar-widget sidebar-agent">
                <h4>Kontaktpersona</h4>
                <div class="agent-info">
                    <img src="https://i.pravatar.cc/150?img=12" alt="Aģenta foto" class="agent-photo">
                    <div>
                        <strong>Jānis Bērziņš</strong>
                        <span>HomeEstate</span>
                    </div>
                </div>
                <a href="tel:+37120000000" class="agent-contact">
                    <i class="fas fa-phone-alt"></i> Zvanīt
                </a>
            </div>
            
            <?php if ($home['type'] === 'rent'): ?>
            <div class="sidebar-widget sidebar-calculator">
                <h4><i class="fas fa-calculator"></i> Īres izmaksas</h4>
                <div class="calc-row">
                    <span>Īres maksa</span>
                    <span><?php echo number_format($rentPrice, 0, ',', ' '); ?> €</span>
                </div>
                <div class="calc-row">
                    <span>Komunālie (apt.)</span>
                    <span><?php echo number_format($utilitiesPrice, 0, ',', ' '); ?> €</span>
                </div>
                <div class="calc-row total">
                    <span>Kopā mēnesī</span>
                    <span><?php echo number_format($totalPrice, 0, ',', ' '); ?> €</span>
                </div>
                <p class="calc-note">Aptuvenās izmaksas. Komunālie maksājumi var mainīties atkarībā no patēriņa.</p>
            </div>
            <?php endif; ?>
        </aside>
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
                        <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
                        <li><a href="<?php echo main_route('property.list'); ?>">Īpašumi</a></li>
                        <li><a href="<?php echo main_route('about'); ?>">Par mums</a></li>
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

    <script>
        // Tab functionality
        document.querySelectorAll('.tab-link').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        // Gallery image change
        function changeImage(thumb) {
            document.getElementById('gallery-main').src = thumb.src.replace('w=600', 'w=1200');
            document.querySelectorAll('.thumb-images img').forEach(img => img.classList.remove('active'));
            thumb.classList.add('active');
        }
    </script>
    <script src="<?php echo asset_path('script.js'); ?>"></script>
</body>
</html>
