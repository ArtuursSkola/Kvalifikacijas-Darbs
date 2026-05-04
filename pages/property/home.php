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
$bodyData = [
    'homes-api' => main_route('api.homes'),
    'home-id' => (int)$homeId,
    'home-type' => (string)($home['veids'] ?? ''),
];
include __DIR__ . '/../../includes/header.php';

$viewerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$isOwnerViewer = $viewerId > 0 && $viewerId === $ownerId;
$canApply = $viewerId > 0 && !$isOwnerViewer;
$applyHref = $canApply ? '#application-form' : ($viewerId <= 0 ? main_route('login') : '#');

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
                <?php if ($canApply): ?>
                    <a href="<?php echo $applyHref; ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php elseif ($viewerId <= 0): ?>
                    <a href="<?php echo htmlspecialchars($applyHref, ENT_QUOTES); ?>" class="agent-contact">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php else: ?>
                    <a href="#" class="agent-contact" onclick="return false;" style="opacity:0.55; cursor:not-allowed;">
                        <i class="fas fa-envelope"></i> Izveidot pieteikumu
                    </a>
                <?php endif; ?>
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
                        <div id="sidebar-calendar" class="sidebar-calendar" data-home-id="<?php echo (int)$homeId; ?>"></div>
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
                            <input type="text" name="lt_full_name" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>E-pasts</h4>
                            <input type="email" name="lt_email" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>Telefona nr.</h4>
                            <input type="tel" name="lt_phone" placeholder="+371 ">
                        </div>

                        <div class="application-input-group">
                            <h4>īres periods mēnešos</h4>
                            <input type="number" name="lt_rent_months" min="1" placeholder="Piem. 6">
                        <label class="application-checkbox">
                            <input type="checkbox" name="lt_rent_unknown" value="1">
                            Pagaidām nav zināms
                        </label>
                        </div>

                        <div class="application-input-group">
                            <h4>Sākuma datums</h4>
                            <input type="date" name="lt_start_date">
                        </div>

                        <div class="application-input-group">
                            <h4>Papildu komentāri</h4>
                            <textarea name="lt_comment" rows="4" placeholder="..."></textarea>
                        </div>

                        <div class="application-input-group">
                            <button class="btn-submit">Nosūtīt pieteikumu</button>
                        </div>

                        <?php elseif (($home['veids'] ?? '') === 'istermina_ire'): ?>

                        <div class="application-input-group">
                         <h4>Vārds un uzvārds</h4>
                         <input type="text" name="st_full_name" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>E-pasts</h4>
                            <input type="email" name="st_email" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>Telefona nr.</h4>
                            <input type="tel" name="st_phone" placeholder="+371 ">
                        </div>

                        <div class="application-input-group">
                            <h4>Sākuma datums</h4>
                            <input type="date" name="st_start_date">
                        </div>

                        <div class="application-input-group">
                            <h4>Beigu datums</h4>
                            <input type="date" name="st_end_date">
                        </div>

                        <div class="application-input-group">
                            <h4>Komentāri</h4>
                            <textarea name="st_comment" rows="4" placeholder="..."></textarea>
                        </div>

                        <div class="application-input-group">
                            <button class="btn-submit">Nosūtīt pieteikumu</button>
                        </div>

                        <?php else: ?>

                        <div class="application-input-group">
                            <h4>Vārds un uzvārds</h4>
                            <input type="text" name="sale_full_name" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>E-pasts</h4>
                            <input type="email" name="sale_email" placeholder="...">
                        </div>

                        <div class="application-input-group">
                            <h4>Telefona nr.</h4>
                            <input type="tel" name="sale_phone" placeholder="+371 ">
                        </div>

                        <div class="application-input-group">
                            <h4>Piedāvātā summa</h4>
                            <input type="number" name="sale_offer" placeholder="...">
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
                            <input type="text" name="sale_comment" placeholder="...">
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

        (function () {
            const api = document.body.getAttribute('data-homes-api') || '';
            const homeId = parseInt(document.body.getAttribute('data-home-id') || '0', 10);
            const homeType = (document.body.getAttribute('data-home-type') || '').trim();
            const cal = document.getElementById('sidebar-calendar');
            const modal = document.getElementById('application-form');
            if (!api || !homeId) return;

            (async function () {
                if (!cal || homeType !== 'istermina_ire') return;
                const pad2 = (n) => String(n).padStart(2, '0');
                const now = new Date();
                const y = now.getFullYear();
                const m = now.getMonth() + 1;
                const monthKey = `${y}-${pad2(m)}`;

                const monthStart = new Date(y, m - 1, 1);
                const monthEnd = new Date(y, m, 0);
                const startDow = (monthStart.getDay() + 6) % 7;
                const daysInMonth = monthEnd.getDate();
                const dateKey = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;

                const cells = [];
                for (let i = 0; i < startDow; i++) {
                    const d = new Date(y, m - 1, 1 - (startDow - i));
                    cells.push({ day: d.getDate(), key: dateKey(d), out: true });
                }
                for (let day = 1; day <= daysInMonth; day++) {
                    const d = new Date(y, m - 1, day);
                    cells.push({ day, key: dateKey(d), out: false });
                }
                const total = Math.ceil(cells.length / 7) * 7;
                for (let i = 1; cells.length < total; i++) {
                    const d = new Date(y, m - 1, daysInMonth + i);
                    cells.push({ day: d.getDate(), key: dateKey(d), out: true });
                }

                cal.innerHTML = `<div class="sidebar-calendar__grid">${cells.map(c => `<div class="sidebar-day${c.out ? ' is-out' : ''}" data-date="${c.key}">${c.day}</div>`).join('')}</div>`;

                const isTaken = (key, ranges) => {
                    const ts = new Date(key + 'T00:00:00').getTime();
                    for (const r of ranges) {
                        if (!r.from || !r.to) continue;
                        const a = new Date(r.from + 'T00:00:00').getTime();
                        const b = new Date(r.to + 'T00:00:00').getTime();
                        if (ts >= a && ts < b) return true;
                    }
                    return false;
                };

                try {
                    const url = new URL(api, window.location.href);
                    url.searchParams.set('action', 'availability');
                    url.searchParams.set('home_id', String(homeId));
                    url.searchParams.set('month', monthKey);
                    const res = await fetch(url.toString(), { credentials: 'same-origin' });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.ok !== true) return;
                    const ranges = Array.isArray(data.ranges) ? data.ranges : [];
                    cal.querySelectorAll('.sidebar-day').forEach(d => {
                        const k = d.getAttribute('data-date') || '';
                        if (k && isTaken(k, ranges)) d.classList.add('is-taken');
                    });
                } catch (_) {
                }
            })();

            if (!modal) return;

            const alertBox = document.getElementById('application-alert');
            const showAlert = (msg, ok) => {
                if (!alertBox) return;
                alertBox.style.display = 'block';
                alertBox.style.border = ok ? '1px solid rgba(48,182,7,0.35)' : '1px solid rgba(231,76,60,0.35)';
                alertBox.style.background = ok ? 'rgba(48,182,7,0.08)' : 'rgba(231,76,60,0.08)';
                alertBox.style.color = ok ? '#1f7a1f' : '#b02014';
                alertBox.textContent = msg;
            };

            const submitBtn = modal.querySelector('.btn-submit');
            if (!submitBtn) return;

            submitBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (alertBox) alertBox.style.display = 'none';

                const fd = new FormData();
                fd.set('action', 'pieteikums_create');
                fd.set('home_id', String(homeId));

                const pick = (name) => {
                    const el = modal.querySelector(`[name="${name}"]`);
                    return el ? (el.value || '').trim() : '';
                };

                let vards = '';
                let epasts = '';
                let telefons = '';
                let komentars = '';

                if (homeType === 'ire') {
                    vards = pick('lt_full_name');
                    epasts = pick('lt_email');
                    telefons = pick('lt_phone');
                    komentars = (modal.querySelector('[name="lt_comment"]')?.value || '').trim();
                    fd.set('ires_menesi', pick('lt_rent_months'));
                    fd.set('nav_zinams', modal.querySelector('[name="lt_rent_unknown"]')?.checked ? '1' : '0');
                    fd.set('ires_sakuma_datums', pick('lt_start_date'));
                } else if (homeType === 'istermina_ire') {
                    vards = pick('st_full_name');
                    epasts = pick('st_email');
                    telefons = pick('st_phone');
                    komentars = (modal.querySelector('[name="st_comment"]')?.value || '').trim();
                    fd.set('sakuma_datums', pick('st_start_date'));
                    fd.set('beigu_datums', pick('st_end_date'));
                } else {
                    vards = pick('sale_full_name');
                    epasts = pick('sale_email');
                    telefons = pick('sale_phone');
                    komentars = pick('sale_comment');
                    fd.set('piedavata_summa', pick('sale_offer'));
                    fd.set('finansesanas_veids', (document.getElementById('pay-method')?.value || '').trim());
                }

                fd.set('vards_uzvards', vards);
                fd.set('epasts', epasts);
                fd.set('telefons', telefons);
                fd.set('komentars', komentars);

                try {
                    const res = await fetch(api, { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.ok !== true) {
                        showAlert((data && data.error) ? data.error : 'Neizdevās nosūtīt pieteikumu.', false);
                        return;
                    }
                    showAlert('Pieteikums nosūtīts.', true);
                    setTimeout(() => { window.location.hash = '#'; }, 700);
                } catch (_) {
                    showAlert('Neizdevās nosūtīt pieteikumu.', false);
                }
            });
        })();
    </script>
    <script src="<?php echo asset_path('script.js'); ?>"></script>
</body>
</html>
