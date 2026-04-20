<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

$pageTitle = 'Meklēt īpašumus - HomeEstate';
$extraStyles = ['homes'];
$bodyClass = 'homes-page';
$bodyData = [
    'homes-api' => main_route('api.homes'),
    'property-route' => main_route('property.show'),
];
include __DIR__ . '/../../includes/header.php';

$initialHomes = [];
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$sql = "SELECT id, owner_id, title, city, location_text, type, price, area, bedrooms, bathrooms, main_image, status
    FROM est_homes
    WHERE status = 'active'"
    . ($currentUserId > 0 ? " OR owner_id = ?" : "")
    . " ORDER BY created_at DESC";

if ($currentUserId > 0) {
    $stmt = $savienojums->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && $row = $res->fetch_assoc()) {
            $initialHomes[] = $row;
        }
        $stmt->close();
    }
} else {
    $res = $savienojums->query($sql);
    while ($res && $row = $res->fetch_assoc()) {
        $initialHomes[] = $row;
    }
}
?>

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
                <span class="filter-count" id="results-count"><?php echo count($initialHomes); ?> rezultāti</span>
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

        <div id="homes-results" class="listing-grid">
            <?php foreach ($initialHomes as $home): ?>
                <?php
                $fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';
                $img = trim((string)($home['main_image'] ?? ''));
                if ($img === '') {
                    $img = $fallbackImg;
                }
                $type = (string)($home['type'] ?? '');
                $badge = $type === 'rent' ? 'Izīrē' : 'Pārdod';
                $badgeClass = $type === 'rent' ? 'rent' : 'sale';
                $location = trim((string)($home['city'] ?? '')) . ', ' . trim((string)($home['location_text'] ?? ''));
                $price = (float)($home['price'] ?? 0);
                $priceLabel = $type === 'rent'
                    ? number_format($price, 0, ',', ' ') . ' € / mēn'
                    : number_format($price, 0, ',', ' ') . ' €';
                ?>
                <div class="property-card">
                    <div class="property-image">
                        <img
                            src="<?php echo htmlspecialchars(media_url($img)); ?>"
                            alt="<?php echo htmlspecialchars((string)($home['title'] ?? '')); ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImg, ENT_QUOTES); ?>';"
                        >
                        <span class="property-badge <?php echo htmlspecialchars($badgeClass); ?>"><?php echo htmlspecialchars($badge); ?></span>
                        <button class="property-favorite" title="Pievienot favorītiem" type="button">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="property-details">
                        <h3><?php echo htmlspecialchars((string)($home['title'] ?? '')); ?></h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($location); ?></p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?php echo (int)($home['bedrooms'] ?? 0); ?> guļamist.</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo (int)($home['area'] ?? 0); ?> m²</span>
                            <span><i class="fas fa-bath"></i> <?php echo (int)($home['bathrooms'] ?? 0); ?> vannas</span>
                        </div>
                        <div class="property-footer">
                            <span class="property-price"><?php echo htmlspecialchars($priceLabel); ?></span>
                            <a href="<?php echo main_route('property.show', ['id' => (int)$home['id']]); ?>" class="btn-view-property">Skatīt <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div id="homes-empty" class="search-empty" style="<?php echo empty($initialHomes) ? '' : 'display:none;'; ?>">
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>

