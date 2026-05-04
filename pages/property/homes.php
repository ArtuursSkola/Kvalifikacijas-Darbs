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
$sql = "SELECT id, ipasnieka_id, nosaukums, pilseta, atrasanas_vieta, veids, cena, platiba, gulamistabas, vannasistabas, galvenais_attels, statuss
    FROM est_homes
    WHERE statuss = 'Aktivs' ORDER BY created_at DESC";

$res = $savienojums->query($sql);
while ($res && $row = $res->fetch_assoc()) {
    $initialHomes[] = $row;
}

$activeHomesCount = 0;
$resActive = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Aktivs'");
if ($resActive && $row = $resActive->fetch_row()) $activeHomesCount = (int)$row[0];

$citiesCount = 0;
$resCities = $savienojums->query("SELECT COUNT(DISTINCT pilseta) FROM est_homes WHERE statuss = 'Aktivs'");
if ($resCities && $row = $resCities->fetch_row()) $citiesCount = (int)$row[0];

$dealsCount = 0;
$resDeals = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Pardots'");
if ($resDeals && $row = $resDeals->fetch_row()) $dealsCount = (int)$row[0];
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
                    <span class="metric-value"><?php echo number_format($activeHomesCount, 0, ',', ' '); ?>+</span>
                    <span class="metric-sub">Aktīvi piedāvājumi</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Pilsētas</span>
                    <span class="metric-value"><?php echo number_format($citiesCount, 0, ',', ' '); ?>+</span>
                    <span class="metric-sub">Visā Latvijā</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Veiksmīgi darījumi</span>
                    <span class="metric-value"><?php echo number_format($dealsCount, 0, ',', ' '); ?>+</span>
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
                        <option value="ire">Īrēt</option>
                        <option value="istermina_ire">Īstermiņa īre</option>
                        <option value="pardod">Pirkt</option>
                    </select>
                </div>
                <div class="filter-group price-range-group">
                    <label><i class="fas fa-euro-sign"></i> Cenas diapazons</label>
                    <div class="price-range-container">
                        <div class="price-inputs">
                            <div class="manual-price">
                                <input type="number" id="price-min-field" class="price-field" value="0">
                                <span> €</span>
                            </div>
                            <span> - </span>
                            <div class="manual-price">
                                <input type="number" id="price-max-field" class="price-field" value="0">
                                <span> €</span>
                            </div>
                        </div>
                        <div class="range-slider">
                            <div class="range-track"></div>
                            <input type="range" id="filter-price-min" class="min-range" value="0">
                            <input type="range" id="filter-price-max" class="max-range" value="0">
                        </div>
                    </div>
                </div>
                <button id="filter-apply" class="btn-filter" type="button">
                    <i class="fas fa-search"></i>
                    Meklēt
                </button>
            </div>

            <div class="advanced-filters" id="advanced-filters-panel" style="display: none;">
                <div class="filters">
                    <div class="filter-group">
                        <label><i class="fas fa-bed"></i> Guļamistabas</label>
                        <input type="number" id="filter-beds" min="0" max="10" step="1" placeholder="Min. skaits">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-bath"></i> Vannas istabas</label>
                        <input type="number" id="filter-baths" min="0" max="10" step="1" placeholder="Min. skaits">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-ruler-combined"></i> Platība (m²)</label>
                        <div class="area-inputs">
                            <input type="number" id="filter-area-min" placeholder="Min">
                            <input type="number" id="filter-area-max" placeholder="Max">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Īpašuma veids</label>
                        <select id="filter-category">
                            <option value="">Visi veidi</option>
                            <option value="dzivoklis">Dzīvoklis</option>
                            <option value="maja">Māja</option>
                            <option value="apartaments">Apartaments</option>
                        </select>
                    </div>
                    <div class="filter-group checkbox-group">
                        <label class="checkbox-container">
                            <input type="checkbox" id="filter-verified">
                            <span class="checkmark"></span>
                            Tikai pārbaudīti īpašnieki <i class="fas fa-shield-alt" style="color: #30b607;"></i>
                        </label>
                    </div>
                </div>
            </div>

            <div class="filter-footer-actions">
                <div class="advanced-filters-toggle">
                    <button type="button" id="toggle-advanced-filters">
                        Papildu filtri <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="filter-hint"><i class="fas fa-info-circle"></i> Rezultāti tiek atjaunoti uzreiz pēc filtra piemērošanas.</div>
            </div>
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
                $img = trim((string)($home['galvenais_attels'] ?? ''));
                if ($img === '') {
                    $img = $fallbackImg;
                }
                $type = (string)($home['veids'] ?? '');
                $isRent = in_array($type, ['ire', 'rent'], true);
                $badge = $isRent ? 'Īrēšana' : 'Pārdod';
                $badgeClass = $isRent ? 'rent' : 'sale';
                if ($type === 'istermina_ire') {
                    $badge = 'Īstermiņa īre';
                    $badgeClass = 'short-rent';
                }
                $location = trim((string)($home['pilseta'] ?? '')) . ', ' . trim((string)($home['atrasanas_vieta'] ?? ''));
                $price = (float)($home['cena'] ?? 0);
                $priceLabel = $isRent
                    ? number_format($price, 0, ',', ' ') . ' € / mēn'
                    : number_format($price, 0, ',', ' ') . ' €';
                if ($type === 'istermina_ire') {
                    $priceLabel = number_format($price, 0, ',', ' ') . ' € / nakti';
                }
                ?>
                <div class="property-card">
                    <div class="property-image">
                        <img
                            src="<?php echo htmlspecialchars(media_url($img)); ?>"
                            alt="<?php echo htmlspecialchars((string)($home['nosaukums'] ?? '')); ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImg, ENT_QUOTES); ?>';"
                        >
                        <span class="property-badge <?php echo htmlspecialchars($badgeClass); ?>"><?php echo htmlspecialchars($badge); ?></span>
                        <button class="property-favorite" title="Pievienot favorītiem" type="button" data-home-id="<?php echo (int)$home['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="property-details">
                        <h3><?php echo htmlspecialchars((string)($home['nosaukums'] ?? '')); ?></h3>
                        <p class="property-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($location); ?></p>
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?php echo (int)($home['gulamistabas'] ?? 0); ?> guļamist.</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo (int)($home['platiba'] ?? 0); ?> m²</span>
                            <span><i class="fas fa-bath"></i> <?php echo (int)($home['vannasistabas'] ?? 0); ?> vannas</span>
                        </div>
                        <div class="property-footer">
	                            <div class="property-price"><?php echo htmlspecialchars($priceLabel); ?></div>
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
