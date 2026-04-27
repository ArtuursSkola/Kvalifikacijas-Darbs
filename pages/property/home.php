<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

$homeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($homeId <= 0) {
    header('Location: ' . main_route('property.list'));
    exit;
}

$homeStmt = $savienojums->prepare("SELECT * FROM est_homes WHERE id = ? LIMIT 1");
$homeStmt->bind_param('i', $homeId);
$homeStmt->execute();
$homeRes = $homeStmt->get_result();

if ($homeRes->num_rows === 0) {
    header('Location: ' . main_route('property.list'));
    exit;
}

$home = $homeRes->fetch_assoc();
$homeStmt->close();

$viewStmt = $savienojums->prepare("UPDATE est_homes SET skatijumi = skatijumi + 1 WHERE id = ?");
if ($viewStmt) {
    $viewStmt->bind_param('i', $homeId);
    $viewStmt->execute();
    $viewStmt->close();
}

$ownerId = (int)($home['ipasnieka_id'] ?? 0);
$owner = null;
if ($ownerId > 0) {
    $ownStmt = $savienojums->prepare("SELECT lietotajvards, epasts, profila_bilde, plan FROM est_lietotaji WHERE lietotaja_id = ? LIMIT 1");
    if ($ownStmt) {
        $ownStmt->bind_param('i', $ownerId);
        $ownStmt->execute();
        $owner = $ownStmt->get_result()->fetch_assoc();
        $ownStmt->close();
    }
}

$pageTitle = htmlspecialchars($home['nosaukums']) . ' - HomeEstate';
$extraStyles = ['home'];
$bodyClass = 'property-detail-page';
include __DIR__ . '/../../includes/header.php';

$ownerName = trim((string)($owner['lietotajvards'] ?? ''));
$ownerEmail = trim((string)($owner['epasts'] ?? ''));
$ownerAvatarUrl = userProfileImageUrl($owner['profila_bilde'] ?? '');
$ownerInitial = strtoupper(substr($ownerName !== '' ? $ownerName : 'U', 0, 1));
$ownerPlan = (string)($owner['plan'] ?? '');
$hasShield = in_array($ownerPlan, ['Silver', 'Gold'], true);

$amenities = [];
if (!empty($home['ertibas'])) {
    $amenities = array_map('trim', explode(',', $home['ertibas']));
}

$gallery = [];
if (!empty($home['galerija'])) {
    $gallery = json_decode($home['galerija'], true) ?: [];
}

$mainImage = media_url($home['galvenais_attels'] ?? '');

$floorDisplay = htmlspecialchars($home['stavu_info'] ?: $home['stavs']);
if ($home['kategorija'] === 'house' && !empty($home['stavu_info'])) {
    $floorDisplay = htmlspecialchars($home['stavu_info']);
}

$priceDisplay = $home['veids'] === 'rent'
    ? number_format($home['cena'], 0, ',', ' ') . ' € / mēn'
    : number_format($home['cena'], 0, ',', ' ') . ' €';

$rentPrice = $home['ires_maksa'] ?: $home['cena'];
$utilitiesPrice = $home['komunalo_maksa'] ?: 0;
$totalPrice = $home['kopa_maksa'] ?: ($rentPrice + $utilitiesPrice);
?>
    <div class="back-nav">
        <a href="<?php echo main_route('property.list'); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Atpakaļ uz meklēšanu
        </a>
    </div>

    <div class="property-wrapper">
        <main class="property-main">
            <div class="property-header">
                <h1><?php echo htmlspecialchars($home['nosaukums']); ?></h1>
                <div class="property-meta">
                    <span class="type-badge <?php echo $home['veids']; ?>">
                        <?php echo $home['veids'] === 'rent' ? 'Izīrē' : 'Pārdod'; ?>
                    </span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['pilseta'] . ', ' . $home['atrasanas_vieta']); ?></span>
                    <span class="chip"><i class="fas fa-bed"></i> <?php echo $home['gulamistabas']; ?> guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> <?php echo $home['platiba']; ?> m²</span>
                    <?php if ($home['kategorija']): ?>
                    <span class="chip">
                        <i class="fas <?php echo $home['kategorija'] === 'house' ? 'fa-home' : 'fa-building'; ?>"></i> 
                        <?php 
                            $catLabels = ['apartment' => 'Dzīvoklis', 'house' => 'Māja', 'land' => 'Zeme'];
                            echo htmlspecialchars($catLabels[$home['kategorija']] ?? $home['kategorija']); 
                        ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="property-gallery">
                <div class="main-image">
                    <img id="gallery-main" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($home['nosaukums']); ?>">
                </div>
                <div class="thumb-images">
                    <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="Galvenais attēls" class="active" onclick="changeImage(this)">
                    <?php foreach ($gallery as $idx => $imgUrl): ?>
                        <img src="<?php echo htmlspecialchars(media_url($imgUrl)); ?>" alt="Attēls <?php echo $idx + 1; ?>" onclick="changeImage(this)">
                    <?php endforeach; ?>
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
                    <p><?php echo nl2br(htmlspecialchars($home['apraksts'])); ?></p>
                    
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
                    <p><?php echo nl2br(htmlspecialchars($home['planojums'] ?: 'Plānojuma informācija nav pieejama.')); ?></p>
                    
                    <div class="spec-grid">
                        <div class="spec-card">
                            <strong>Platība</strong>
                            <span><?php echo $home['platiba']; ?> m²</span>
                        </div>
                        <div class="spec-card">
                            <strong>Guļamistabas</strong>
                            <span><?php echo $home['gulamistabas']; ?></span>
                        </div>
                        <div class="spec-card">
                            <strong><?php echo $home['kategorija'] === 'house' ? 'Stāvi' : 'Stāvs'; ?></strong>
                            <span><?php echo $floorDisplay; ?></span>
                        </div>
                        <div class="spec-card">
                            <strong>Vannasistabas</strong>
                            <span><?php echo $home['vannasistabas']; ?></span>
                        </div>
                    </div>
                </div>

                <div id="map" class="tab-content">
                    <h3>Atrašanās vieta</h3>
                    <p><?php echo nl2br(htmlspecialchars($home['karte'] ?: 'Īpašums atrodas ' . $home['pilseta'] . ', ' . $home['adrese'])); ?></p>
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
                        <strong>
                            <?php echo htmlspecialchars($ownerName !== '' ? $ownerName : 'Īpašnieks'); ?>
                            <?php if ($hasShield): ?>
                                <i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>
                            <?php endif; ?>
                        </strong>
                        <span>Īpašnieks</span>
                    </div>
                </div>
                <?php if ($ownerEmail !== ''): ?>
                    <a href="mailto:<?php echo htmlspecialchars($ownerEmail); ?>?subject=Interese%20par%20<?php echo urlencode($home['nosaukums']); ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Sazināties
                    </a>
                <?php else: ?>
                    <a href="mailto:info@homeestate.lv?subject=Interese%20par%20<?php echo urlencode($home['nosaukums']); ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Sazināties
                    </a>
                <?php endif; ?>
            </div>

            <div class="sidebar-widget sidebar-price">
                <span class="price"><?php echo $priceDisplay; ?></span>
                <a href="mailto:info@homeestate.lv?subject=Interese%20par%20<?php echo urlencode($home['nosaukums']); ?>" class="btn-primary">
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
            
            <?php if ($home['veids'] === 'rent'): ?>
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
        document.querySelectorAll('.tab-link').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        function changeImage(thumb) {
            document.getElementById('gallery-main').src = thumb.src.replace('w=600', 'w=1200');
            document.querySelectorAll('.thumb-images img').forEach(img => img.classList.remove('active'));
            thumb.classList.add('active');
        }
    </script>
    <script src="<?php echo asset_path('script.js'); ?>"></script>
</body>
</html>
