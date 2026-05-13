<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/routes/main.php';

$pageTitle = 'HomeEstate - Tavs mājoklis';
$extraStyles = ['index'];
include 'includes/header.php';

if (isset($_SESSION['login_success'])) {
    unset($_SESSION['login_success']);
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showPageAlert('Jūs veiksmīgi pieteicāties', 'success'); });</script>";
}

if (isset($_SESSION['register_success'])) {
    unset($_SESSION['register_success']);
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showPageAlert('Jūs veiksmīgi reģistrējāties', 'success'); });</script>";
}


$newestHomes = [];
$sql = "SELECT id, ipasnieka_id, nosaukums, pilseta, atrasanas_vieta, veids, cena, platiba, gulamistabas, vannasistabas, galvenais_attels 
        FROM est_homes WHERE statuss = 'Aktivs' ORDER BY created_at DESC LIMIT 3";
$result = $savienojums->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['owner_data'] = null;
        $newestHomes[] = $row;
    }
}

if ($newestHomes !== []) {
    $ownerIds = array_filter(array_unique(array_column($newestHomes, 'ipasnieka_id')));
    if ($ownerIds !== []) {
        $placeholders = implode(',', array_fill(0, count($ownerIds), '?'));
        $ownerStmt = $savienojums->prepare("SELECT lietotaja_id, lietotajvards, profila_bilde, plans FROM est_lietotaji WHERE lietotaja_id IN ($placeholders)");
        if ($ownerStmt) {
            $ownerStmt->bind_param(str_repeat('i', count($ownerIds)), ...array_values($ownerIds));
            $ownerStmt->execute();
            $ownerRes = $ownerStmt->get_result();
            $owners = [];
            while ($o = $ownerRes->fetch_assoc()) {
                $owners[$o['lietotaja_id']] = $o;
            }
            $ownerStmt->close();

            foreach ($newestHomes as &$h) {
                if (isset($owners[$h['ipasnieka_id']])) {
                    $h['owner_data'] = $owners[$h['ipasnieka_id']];
                }
            }
        }
    }
}

$activeHomesCount = 0;
$resActive = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Aktivs'");
if ($resActive && $row = $resActive->fetch_row()) $activeHomesCount = (int)$row[0];

$totalUsersCount = 0;
$resUsers = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji");
if ($resUsers && $row = $resUsers->fetch_row()) $totalUsersCount = (int)$row[0];

$dealsCount = 0;
$resDeals = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Pardots'");
if ($resDeals && $row = $resDeals->fetch_row()) $dealsCount = (int)$row[0];

$homepageMapMarkers = [];
$hm = $savienojums->query(
    "SELECT id, nosaukums, pilseta, latitude, longitude FROM est_homes
     WHERE statuss = 'Aktivs'
       AND latitude IS NOT NULL
       AND longitude IS NOT NULL
     ORDER BY created_at DESC
     LIMIT 400"
);
if ($hm) {
    while ($row = $hm->fetch_assoc()) {
        $latRaw = $row['latitude'] ?? null;
        $lngRaw = $row['longitude'] ?? null;
        if ($latRaw === null || $lngRaw === null) {
            continue;
        }
        if (!is_numeric($latRaw) || !is_numeric($lngRaw)) {
            continue;
        }
        $lat = (float)$latRaw;
        $lng = (float)$lngRaw;
        if ($lat < 55.0 || $lat > 59.5 || $lng < 18.0 || $lng > 30.5) {
            continue;
        }
        $hid = (int)$row['id'];
        $homepageMapMarkers[] = [
            'id' => $hid,
            'title' => (string)($row['nosaukums'] ?? ''),
            'city' => (string)($row['pilseta'] ?? ''),
            'lat' => $lat,
            'lng' => $lng,
            'url' => main_route('property.show', ['id' => $hid]),
        ];
    }
}
?>

    <header class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-award"></i>
                Nr.1 Nekustamā īpašuma platforma Latvijā
            </div>
            <h1>Atrodi savu <span class="highlight">sapņu mājokli</span> viegli un ātri</h1>
            <p>Ērts un drošs veids, kā īrēt vai iegādāties nekustamo īpašumu. Tūkstošiem īpašumu gaida tevi!</p>
            
            <form class="search-bar" action="<?php echo main_route('property.list'); ?>" method="GET">
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" name="city" placeholder="Pilsēta vai rajons">
                </div>
                <div class="input-group">
                    <i class="fas fa-home"></i>
                    <select name="type">
                        <option value="">Visi tipi</option>
                        <option value="pardod">Pirkt</option>
                        <option value="istermina_ire">Īstermiņa īre</option>
                        <option value="ire">Īrēt</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fas fa-building"></i>
                    <select name="category">
                        <option value="">Visi veidi</option>
                        <option value="dzivoklis">Dzīvoklis</option>
                        <option value="apartaments">Apartaments</option>
                        <option value="maja">Māja</option>
                    </select>
                </div>
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    Meklēt
                </button>
            </form>
            
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="number"><?php echo number_format($activeHomesCount, 0, ',', ' '); ?><span>+</span></div>
                    <div class="label">Aktīvi sludinājumi</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($totalUsersCount, 0, ',', ' '); ?><span>+</span></div>
                    <div class="label">Apmierināti klienti</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($dealsCount, 0, ',', ' '); ?><span>+</span></div>
                    <div class="label">Veiksmīgi darījumi</div>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator">
            <span>Ritini uz leju</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </header>

    <section class="listings">
        <div class="container"> 
            <div class="section-header">
                <span class="section-label">Jaunākie piedāvājumi</span>
                <h2 class="section-title-new">Jaunākie īpašumi</h2>
                <p class="section-subtitle">Izpēti jaunākos piedāvāvumus un atrodi savu nākamo mājvietu.</p>
            </div>
            
            <div class="listing-grid">
                <?php if (!empty($newestHomes)): ?>
                    <?php foreach ($newestHomes as $home): 
                        $isRent = in_array((string)($home['veids'] ?? ''), ['ire', 'rent'], true);
                        $priceDisplay = $isRent 
                            ? number_format($home['cena'], 0, ',', ' ') . ' € / mēn'
                            : number_format($home['cena'], 0, ',', ' ') . ' €';
                        $badgeClass = $isRent ? 'rent' : 'sale';
                        $badgeText = $isRent ? 'Īrēšana' : 'Pārdod';
                        $image = $home['galvenais_attels'] ?: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=500&q=60';
                        $owner = $home['owner_data'];
                        $ownerName = $owner['lietotajvards'] ?? 'Īpašnieks';
                        $ownerPfp = userProfileImageUrl($owner['profila_bilde'] ?? '');
                        $ownerInitial = strtoupper(substr($ownerName, 0, 1));
                        $hasShield = in_array($owner['plans'] ?? '', ['Sudraba', 'Zelta'], true);
                        if (($home['veids'] ?? '') === 'istermina_ire') {
                            $priceDisplay = number_format($home['cena'], 0, ',', ' ') . ' € / nakti';
                            $badgeClass = 'short-rent';
                            $badgeText = 'Īstermiņa īre';
                        }
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($home['nosaukums']); ?>">
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                            <button class="favorite-btn" type="button" data-home-id="<?php echo (int)$home['id']; ?>"><i class="far fa-heart"></i></button>
                        </div>
                        <div class="card-details">
                            <h3><?php echo htmlspecialchars($home['nosaukums']); ?></h3>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['pilseta'] . ', ' . $home['atrasanas_vieta']); ?></p>
                            <div class="features">
                                <span><i class="fas fa-bed"></i> <?php echo (int)$home['gulamistabas']; ?> guļamist.</span>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo (int)$home['platiba']; ?> m²</span>
                                <span><i class="fas fa-bath"></i> <?php echo (int)$home['vannasistabas']; ?> vannas</span>
                            </div>

                            <div class="property-owner-bar">
                                <div class="property-owner-info">
                                    <?php if ($ownerPfp !== ''): ?>
                                        <img src="<?php echo htmlspecialchars($ownerPfp); ?>" alt="<?php echo htmlspecialchars($ownerName); ?>" class="owner-mini-pfp">
                                    <?php else: ?>
                                        <span class="owner-mini-initial"><?php echo htmlspecialchars($ownerInitial); ?></span>
                                    <?php endif; ?>
                                    <span class="owner-username">
                                        <?php echo htmlspecialchars($ownerName); ?>
                                        <?php if ($hasShield): ?>
                                            <i class="fas fa-shield-alt" style="color: #30b607; margin-left: 5px;" title="Uzticams īpašnieks"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="price-row">
                                <span class="price"><?php echo $priceDisplay; ?></span>
                                <a href="<?php echo main_route('property.show', ['id' => $home['id']]); ?>" class="btn-view">Skatīt <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1; color: #6b7a8f;">Nav pieejamu īpašumu.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="homepage-map-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Karte</span>
                <h2 class="section-title-new">Sludinājumu atrašanās vietas</h2>
                <p class="section-subtitle">Tikai sludinājumi, kuros īpašnieks norādījis precīzu atrašanās vietu kartē.</p>
            </div>
            <div id="homepage-osm-map" class="homepage-osm-map"></div>
            <p class="homepage-osm-attrib">Karte: © <a href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a> līdzdalībnieki</p>
        </div>
    </section>
    <script type="application/json" id="homepage-map-data"><?php echo json_encode(['markers' => $homepageMapMarkers], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const raw = document.getElementById('homepage-map-data');
            const mapEl = document.getElementById('homepage-osm-map');
            if (!raw || !mapEl) return;
            let payload;
            try { payload = JSON.parse(raw.textContent); } catch (e) { return; }
            const markers = Array.isArray(payload.markers) ? payload.markers : [];
            function esc(s) {
                const d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }
            function loadLeaflet(cb) {
                if (window.L) { cb(); return; }
                const lk = document.createElement('link');
                lk.rel = 'stylesheet';
                lk.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(lk);
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                s.onload = function() { cb(); };
                document.head.appendChild(s);
            }
            loadLeaflet(function() {
                const map = L.map(mapEl, { scrollWheelZoom: false }).setView([56.8796, 24.6032], 7);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);
                markers.forEach(function(m) {
                    if (typeof m.lat !== 'number' || typeof m.lng !== 'number') return;
                    const u = m.url ? String(m.url) : '';
                    const link = u ? '<br><a href="' + encodeURI(u) + '">Skatīt sludinājumu</a>' : '';
                    L.marker([m.lat, m.lng]).addTo(map).bindPopup('<strong>' + esc(m.title) + '</strong><br>' + esc(m.city) + link);
                });
                if (markers.length === 1) {
                    const m0 = markers[0];
                    if (typeof m0.lat === 'number' && typeof m0.lng === 'number') {
                        map.setView([m0.lat, m0.lng], 12);
                    }
                } else if (markers.length > 1) {
                    const pts = markers.filter(function(m) {
                        return typeof m.lat === 'number' && typeof m.lng === 'number';
                    }).map(function(m) { return [m.lat, m.lng]; });
                    if (pts.length > 1) {
                        map.fitBounds(L.latLngBounds(pts), { padding: [40, 40], maxZoom: 11 });
                    }
                }
                setTimeout(function() { map.invalidateSize(); }, 400);
            });
        });
    </script>

    <section class="features-section">
        <div class="container">
            <div class="feature-box">
                <i class="fas fa-shield-alt"></i>
                <h3>Droši darījumi</h3>
                <p>Mēs garantējam drošu vidi un pārbaudītus lietotājus. Katrs sludinājums tiek rūpīgi pārbaudīts.</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-search"></i>
                <h3>Ērta meklēšana</h3>
                <p>Plašas filtrēšanas iespējas un viegli lietojams interfeiss, lai atrastu tieši to, ko meklē.</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-headset"></i>
                <h3>Atbalsts 24/7</h3>
                <p>Mūsu profesionālā komanda ir gatava palīdzēt jebkurā laikā un atbildēt uz jautājumiem.</p>
            </div>
        </div>
    </section>

    <section class ="help-section">
    <h2>Vai ir kādi jautājumi, vai vajadzīga palīdzība?</h2>
        <p>Mēs atbildam uz visiem jūsu dotajiem jautājumiem un jums ir iespēja apskatīt biežāk uzdotos jautājumus</p>
        <a href="<?php echo main_route('faq'); ?>" class="btn-cta">
            <i class="fa-solid fa-question"></i>
                Palīdzības centrs
        </a>
    </section>
    
    <section class="cta-section">
        <h2>Gatavs sākt savu meklēšanu?</h2>
        <p>Pievienojies tūkstošiem apmierinātu klientu un atrodi savu ideālo mājokli jau šodien.</p>
        <a href="<?php echo main_route('property.list'); ?>" class="btn-cta">
            <i class="fas fa-search"></i>
            Sākt meklēšanu
        </a>
    </section>


    <?php include 'includes/footer.php'; ?>
