<?php
session_start();
require_once __DIR__ . '/../../routes/main.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';
require_once dirname(__DIR__, 2) . '/includes/latvia_city_coords.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plans'] ?? 'Nekads';
$canCreate = $isOwner && in_array($plan, ['Bezmaksas', 'Sudraba', 'Zelta'], true);

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

$detailMapLat = null;
$detailMapLng = null;
$hasDetailPin = false;
$latCol = $home['latitude'] ?? null;
$lngCol = $home['longitude'] ?? null;
if ($latCol !== null && $latCol !== '' && $lngCol !== null && $lngCol !== ''
        && is_numeric($latCol) && is_numeric($lngCol)) {
    $detailMapLat = (float)$latCol;
    $detailMapLng = (float)$lngCol;
    $hasDetailPin = true;
} else {
    $bc = latvia_city_coordinates((string)($home['pilseta'] ?? ''));
    $jj = map_home_jitter($bc[0], $bc[1], $homeId);
    $detailMapLat = $jj[0];
    $detailMapLng = $jj[1];
}

$viewStmt = $savienojums->prepare("UPDATE est_homes SET skatijumi = skatijumi + 1 WHERE id = ?");
if ($viewStmt) {
    $viewStmt->bind_param('i', $homeId);
    $viewStmt->execute();
    $viewStmt->close();
}

$ownerId = (int)($home['ipasnieka_id'] ?? 0);
$owner = null;
if ($ownerId > 0) {
    $ownStmt = $savienojums->prepare("SELECT lietotajvards, epasts, profila_bilde, plans FROM est_lietotaji WHERE lietotaja_id = ? LIMIT 1");
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
$bodyData = [
        'homes-api' => main_route('api.homes'),
        'home-id' => (int)$homeId,
        'home-type' => (string)($home['veids'] ?? ''),
        'detail-map-lat' => (string)$detailMapLat,
        'detail-map-lng' => (string)$detailMapLng,
        'detail-map-exact' => $hasDetailPin ? '1' : '0',
];
include __DIR__ . '/../../includes/header.php';

$viewerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isOwnerViewer = $viewerId > 0 && $viewerId === $ownerId;
$canApply = $viewerId > 0 && !$isOwnerViewer;
$applyHref = $canApply ? '#application-form' : ($viewerId <= 0 ? main_route('login') : '#');


$userPhone = '';
$userEmail = '';
if ($viewerId > 0) {
    $userStmt = $savienojums->prepare("SELECT telefons, epasts FROM est_lietotaji WHERE lietotaja_id = ? LIMIT 1");
    if ($userStmt) {
        $userStmt->bind_param('i', $viewerId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        $userPhone = $userData['telefons'] ?? '';
        $userEmail = $userData['epasts'] ?? '';
        $userStmt->close();
    }
}

$ownerName = trim((string)($owner['lietotajvards'] ?? ''));
$ownerEmail = trim((string)($owner['epasts'] ?? ''));
$ownerAvatarUrl = userProfileImageUrl($owner['profila_bilde'] ?? '');
$ownerInitial = strtoupper(substr($ownerName !== '' ? $ownerName : 'U', 0, 1));
$ownerPlan = (string)($owner['plans'] ?? '');
$hasShield = in_array($ownerPlan, ['Sudraba', 'Zelta'], true);

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
if ($home['kategorija'] === 'maja' && !empty($home['stavu_info'])) {
    $floorDisplay = htmlspecialchars($home['stavu_info']);
}

$type = (string)($home['veids'] ?? '');
$type = $type === 'rent' ? 'ire' : ($type === 'buy' ? 'pardod' : $type);
$badgeText = $type === 'ire' ? 'Īrēšana' : ($type === 'istermina_ire' ? 'Īstermiņa īre' : 'Pārdod');

$priceDisplay = in_array($type, ['ire', 'rent'], true)
        ? number_format($home['cena'], 0, ',', ' ') . ' € / mēn'
        : number_format($home['cena'], 0, ',', ' ') . ' €';

$pirtsPricePerDay = (float)($home['pirts_cena_diena'] ?? 0);
$ballaPricePerDay = (float)($home['balla_cena_diena'] ?? 0);
if ($type === 'istermina_ire') {
    $priceDisplay = number_format($home['cena'], 0, ',', ' ') . ' € / nakti';
}

$rentPrice = $home['ires_maksa'] ?: $home['cena'];
$utilitiesPrice = $home['komunalo_maksa'] ?: 0;
$totalPrice = $home['kopa_maksa'] ?: ($rentPrice + $utilitiesPrice);
?>
<?php
$from = $_GET['from'] ?? '';

$backHref = main_route('property.list');
$backText = 'Atpakaļ uz meklēšanu';

if ($from === 'my_listings') {
    $backHref = main_route('my.listings');
    $backText = 'Atpakaļ uz mani sludinājumi';
}

if ($from === 'admin_listings') {
    $backHref = admin_route('listings');
    $backText = 'Atpakaļ uz sludinājumu tabulu';
}

function fixDateTime($value) {
    if (!$value) return null;
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', trim($value));
    if (!$dt) {
        $dt = DateTime::createFromFormat('Y-m-d H:i', trim($value));
    }
    return $dt ? $dt->format('Y-m-d H:i:s') : null;
}

$startDate = fixDateTime($_POST['lt_start_date'] ?? '');
?>

    <div class="back-nav">
        <a href="<?php echo $backHref; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            <?php echo $backText; ?>
        </a>
    </div>

    <div class="property-wrapper">
        <main class="property-main">
            <div class="property-header">
                <h1><?php echo htmlspecialchars($home['nosaukums']); ?></h1>
                <div class="property-meta">
                    <span class="type-badge <?php echo $home['veids']; ?>">
                        <?php echo $badgeText; ?>
                    </span>
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['pilseta'] . ', ' . $home['atrasanas_vieta']); ?></span>
                    <span class="chip"><i class="fas fa-bed"></i> <?php echo $home['gulamistabas']; ?> guļamist.</span>
                    <span class="chip"><i class="fas fa-ruler-combined"></i> <?php echo $home['platiba']; ?> m²</span>
                    <?php if ($home['kategorija']): ?>
                        <span class="chip">
                        <i class="fas <?php echo $home['kategorija'] === 'maja' ? 'fa-home' : 'fa-building'; ?>"></i>
                        <?php
                        $catLabels = ['dzivoklis' => 'Dzīvoklis', 'maja' => 'Māja', 'apartaments' => 'Apartaments'];
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
                            <strong><?php echo $home['kategorija'] === 'maja' ? 'Stāvi' : 'Stāvs'; ?></strong>
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
                    <div id="property-detail-map" class="property-detail-osm-map" aria-label="Īpašuma karte"></div>
                    <?php if (!$hasDetailPin): ?>
                        <p class="muted small" style="margin-top:10px;">Aptuvena atrašanās vieta (pilsētas centrs ar nelielu nobīdi). Īpašnieks var precizēt pinu sludinājuma redakcijā.</p>
                    <?php endif; ?>
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
                <div class="agent-actions">
                    <?php if ($canApply): ?>
                        <a href="<?php echo $applyHref; ?>" class="agent-contact">
                            <i class="fas fa-envelope"></i> Izveidot pieteikumu
                        </a>
                        <button class="chat-icon-btn" onclick="startChatWithUser(<?php echo $ownerId; ?>, '<?php echo htmlspecialchars($ownerName, ENT_QUOTES); ?>')" title="Sazināties ar īpašnieku">
                            <i class="fas fa-comment"></i>
                        </button>
                    <?php elseif ($viewerId <= 0): ?>
                        <a href="<?php echo htmlspecialchars($applyHref, ENT_QUOTES); ?>" class="agent-contact">
                            <i class="fas fa-envelope"></i> Izveidot pieteikumu
                        </a>
                    <?php else: ?>
                        <a href="#" class="agent-contact" onclick="return false;" style="opacity:0.55; cursor:not-allowed;">
                            <i class="fas fa-envelope"></i> Izveidot pieteikumu
                        </a>
                        <button class="chat-icon-btn" onclick="startChatWithUser(<?php echo $ownerId; ?>, '<?php echo htmlspecialchars($ownerName, ENT_QUOTES); ?>')" title="Sazināties ar īpašnieku">
                            <i class="fas fa-comment"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-widget sidebar-price">
                <?php if (($home['veids'] ?? '') === 'ire'): ?>
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
                <?php else: ?>
                    <span class="price"><?php echo $priceDisplay; ?></span>
                    <?php if (($home['veids'] ?? '') === 'istermina_ire'): ?>
                        <div class="sc-wrap" id="sidebar-calendar" data-home-id="<?php echo (int)$homeId; ?>">
                            <div class="sc-header">
                                <button class="sc-nav" id="sc-prev" type="button" aria-label="Iepriekšējais mēnesis">&#8249;</button>
                                <span class="sc-title" id="sc-title"></span>
                                <button class="sc-nav" id="sc-next" type="button" aria-label="Nākamais mēnesis">&#8250;</button>
                            </div>
                            <div class="sc-grid" id="sc-grid"></div>
                            <div class="sc-legend">
                                <span class="sc-dot sc-dot--free"></span> Brīvs
                                <span class="sc-dot sc-dot--taken"></span> Aizņemts
                                <span class="sc-dot sc-dot--past"></span> Pagājis
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($canApply): ?>
                    <a href="<?php echo $applyHref; ?>" class="btn-primary">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php elseif ($viewerId <= 0): ?>
                    <a href="<?php echo htmlspecialchars($applyHref, ENT_QUOTES); ?>" class="btn-primary">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php else: ?>
                    <a href="#" class="btn-primary" onclick="return false;" style="opacity:0.55; cursor:not-allowed;">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($canApply): ?>
                <div id="application-form" class="settings-modal">

                    <div style="background: white; padding: 20px; margin: auto;" class="application-modal">
                        <div id="application-top">
                            <div class="application-top-top" style="display: flex; justify-content: space-between">
                                <h2>Pieteikums</h2>
                                <a href="#"><i class="fa-solid fa-x"></i></a>
                            </div>
                            <p style="font-size: 15px">Izveidot pieteikumu sludinājumam</p>
                        </div>
                        <div class="application-input">
                            <div id="application-alert" style="display:none; margin: 0 0 12px 0; padding: 10px 12px; border-radius: 10px; font-weight: 600;"></div>

                            <?php if (($home['veids'] ?? '') === 'ire'): ?>

                                <div class="application-input-group">
                                    <h4>Vārds un uzvārds</h4>
                                    <input type="text" name="lt_full_name" placeholder="..." maxlength="50" pattern="[A-Za-zĀ-ž\s]+" oninput="this.value=this.value.replace(/[^A-Za-zĀ-ž\s]/g,'')" title="Lūdzu ievadiet tikai burtus un atstarpes">
                                </div>
                                <div class="application-input-group">
                                    <h4>E-pasts</h4>
                                    <input type="email" name="lt_email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                                </div>

                                <div class="application-input-group">
                                    <h4>Telefona nr.</h4>
                                    <input type="tel" name="lt_phone" value="<?php echo htmlspecialchars($userPhone); ?>" readonly>
                                </div>

                                <div class="application-input-group">
                                    <h4>īres periods mēnešos</h4>
                                    <input type="number" name="lt_rent_months" id="lt_rent_months" min="1" max="99" placeholder="Piem. 6" oninput="if(this.value.length>2)this.value=this.value.slice(0,2);if(parseInt(this.value)>99)this.value=99;">
                                    <label class="application-checkbox"><input type="checkbox" id="rent_unknown" name="lt_rent_unknown" value="1"> Pagaidām nav zināms</label>
                                </div>

                                <div class="application-input-group">
                                    <h4>Sākuma datums</h4>
                                    <input type="datetime-local" name="lt_start_date" id="lt_start_date">
                                </div>

                                <div class="application-input-group">
                                    <h4>Papildu komentāri</h4>
                                    <textarea name="lt_comment" rows="4" maxlength="300" placeholder="..."></textarea>
                                </div>

                                <div class="application-input-group">
                                    <button class="btn-submit">Nosūtīt pieteikumu</button>
                                </div>

                            <?php elseif (($home['veids'] ?? '') === 'istermina_ire'): ?>

                                <div class="application-input-group">
                                    <h4>Vārds un uzvārds</h4>
                                    <input type="text" name="st_full_name" placeholder="..." maxlength="50" pattern="[A-Za-zĀ-ž\s]+" oninput="this.value=this.value.replace(/[^A-Za-zĀ-ž\s]/g,'')" title="Lūdzu ievadiet tikai burtus un atstarpes">
                                </div>

                                <div class="application-input-group">
                                    <h4>E-pasts</h4>
                                    <input type="email" name="st_email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                                </div>

                                <div class="application-input-group">
                                    <h4>Telefona nr.</h4>
                                    <input type="tel" name="st_phone" value="<?php echo htmlspecialchars($userPhone); ?>" readonly>
                                </div>

                                <div class="application-input-group">
                                    <h4>Sākuma datums</h4>
                                    <input type="datetime-local" name="st_start_date" id="st_start_date">
                                </div>

                                <div class="application-input-group">
                                    <h4>Beigu datums</h4>
                                    <input type="datetime-local" name="st_end_date" id="st_end_date">
                                </div>

                                <div class="application-input-group">
                                    <h4>Komentāri</h4>
                                    <textarea name="st_comment" rows="4" maxlength="300" placeholder="..."></textarea>
                                </div>

                                <div class="application-input-group">
                                    <button class="btn-submit">Nosūtīt pieteikumu</button>
                                </div>

                            <?php else: ?>

                            <div class="application-input-group">
                                <h4>Vārds un uzvārds</h4>
                                <input type="text" name="sale_full_name" placeholder="..." maxlength="50" pattern="[A-Za-zĀ-ž\s]+" oninput="this.value=this.value.replace(/[^A-Za-zĀ-ž\s]/g,'')" title="Lūdzu ievadiet tikai burtus un atstarpes">
                            </div>

                            <div class="application-input-group">
                                <h4>E-pasts</h4>
                                <input type="email" name="sale_email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                            </div>

                            <div class="application-input-group">
                                <h4>Telefona nr.</h4>
                                <input type="tel" name="sale_phone" value="<?php echo htmlspecialchars($userPhone); ?>" readonly>
                            </div>

                            <div class="application-input-group">
                                <h4>Piedāvātā summa</h4>
                                <input type="number" name="sale_offer" placeholder="..." max="9999999" oninput="if(this.value.length>7)this.value=this.value.slice(0,7);">
                            </div>

                            <div class="application-input-group">
                                <h4>Finansēšanas veids</h4>
                                <div style="max-width: 200px;">
                                    <select id="pay-method" name="pay-method"
                                            style="padding: 5px; font-size: 14px; border-radius: 4px; width: 100%;">
                                        <option value="cash">Skaidra nauda</option>
                                        <option value="mortgage">Hipotēka</option>
                                    </select>
                                </div>
                            </div>

                            <div class="application-input-group">
                                <h4>Papildu komentārs</h4>
                                <input type="text" name="sale_comment" maxlength="300" placeholder="...">
                            </div>

                            <div class="application-input-group">
                                <button class="btn-submit">Nosūtīt pieteikumu</button>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

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


        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

            const ltStart = document.getElementById('lt_start_date');
            const stStart = document.getElementById('st_start_date');
            const stEnd = document.getElementById('st_end_date');

            if (ltStart) ltStart.min = localNow;
            if (stStart) stStart.min = localNow;
            if (stEnd) stEnd.min = localNow;

            if (stStart && stEnd) {
                stStart.addEventListener('change', () => {
                    stEnd.min = stStart.value;
                });
            }

            const calendar = document.getElementById('sidebar-calendar');
            if (!calendar) return;

            const grid = document.getElementById('sc-grid');
            const title = document.getElementById('sc-title');
            const prev = document.getElementById('sc-prev');
            const next = document.getElementById('sc-next');
            if (!grid || !title || !prev || !next) return;

            const homeId = calendar.dataset.homeId;
            const apiBase = document.body.getAttribute('data-homes-api') || '';

            let current = new Date();
            let bookingRanges = [];

            const pad2 = (n) => String(n).padStart(2, '0');
            const formatDateKey = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;

            function isDayTaken(key, ranges) {
                const ts = new Date(key + 'T12:00:00').getTime();
                for (const r of ranges) {
                    if (!r.from || !r.to) continue;
                    const a = new Date(String(r.from).replace(' ', 'T')).getTime();
                    const b = new Date(String(r.to).replace(' ', 'T')).getTime();
                    if (ts >= a && ts < b) return true;
                }
                return false;
            }

            async function loadTaken() {
                try {
                    const year = current.getFullYear();
                    const month = current.getMonth();
                    const monthKey = `${year}-${pad2(month + 1)}`;
                    bookingRanges = [];
                    if (apiBase && homeId) {
                        const url = new URL(apiBase, window.location.href);
                        url.searchParams.set('action', 'availability');
                        url.searchParams.set('home_id', String(homeId));
                        url.searchParams.set('month', monthKey);
                        const res = await fetch(url.toString(), { credentials: 'same-origin' });
                        const data = await res.json().catch(() => null);
                        if (res.ok && data && data.ok === true && Array.isArray(data.ranges)) {
                            bookingRanges = data.ranges;
                        }
                    }
                } catch (e) {
                    console.error(e);
                    bookingRanges = [];
                }
                render();
            }

            function render() {
                const year = current.getFullYear();
                const month = current.getMonth();

                const first = new Date(year, month, 1);
                const last = new Date(year, month + 1, 0);

                const days = last.getDate();
                const startDay = (first.getDay() + 6) % 7;

                title.textContent = first.toLocaleString('lv-LV', {
                    month: 'long',
                    year: 'numeric'
                });

                grid.innerHTML = '';

                const weekdays = ['P','O','T','C','Pk','S','Sv'];
                weekdays.forEach(d => {
                    const el = document.createElement('div');
                    el.className = 'sc-cell sc-cell--name';
                    el.textContent = d;
                    grid.appendChild(el);
                });

                for (let i = 0; i < startDay; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'sc-cell sc-cell--empty';
                    grid.appendChild(empty);
                }

                const today = new Date();
                today.setHours(0, 0, 0, 0);

                for (let d = 1; d <= days; d++) {
                    const date = new Date(year, month, d);
                    const key = formatDateKey(date);

                    const cell = document.createElement('div');
                    cell.classList.add('sc-cell');

                    if (date < today) {
                        cell.classList.add('sc-cell--past');
                    } else if (isDayTaken(key, bookingRanges)) {
                        cell.classList.add('sc-cell--taken');
                    } else {
                        cell.classList.add('sc-cell--free');
                    }

                    cell.textContent = d;
                    grid.appendChild(cell);
                }
            }

            prev.addEventListener('click', () => {
                current.setMonth(current.getMonth() - 1);
                loadTaken();
            });

            next.addEventListener('click', () => {
                current.setMonth(current.getMonth() + 1);
                loadTaken();
            });

            loadTaken();
        });
    </script>

    <script>
    (function () {
        var mapEl = document.getElementById('property-detail-map');
        if (!mapEl) return;
        var body = document.body;
        var lat = parseFloat(body.getAttribute('data-detail-map-lat') || '');
        var lng = parseFloat(body.getAttribute('data-detail-map-lng') || '');
        if (!isFinite(lat) || !isFinite(lng)) return;
        var exact = body.getAttribute('data-detail-map-exact') === '1';
        var mapInstance = null;
        function loadLeaflet(cb) {
            if (window.L) { cb(); return; }
            var lk = document.createElement('link');
            lk.rel = 'stylesheet';
            lk.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(lk);
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            s.onload = function () { cb(); };
            document.head.appendChild(s);
        }
        window.__homeestPropertyMapInit = function () {
            if (mapInstance) {
                setTimeout(function () { mapInstance.invalidateSize(); }, 150);
                return;
            }
            loadLeaflet(function () {
                if (!mapEl || mapInstance) return;
                var zoom = exact ? 14 : 11;
                mapInstance = L.map(mapEl, { scrollWheelZoom: false }).setView([lat, lng], zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(mapInstance);
                L.marker([lat, lng]).addTo(mapInstance);
                setTimeout(function () { mapInstance.invalidateSize(); }, 250);
            });
        };
    })();
    </script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>