<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$help_messages = [];


if (!$savienojums || $savienojums->connect_error) {
    error_log("Database connection error: " . ($savienojums->connect_error ?? 'Connection not established'));
    die("Database connection error. Please try again later.");
}

$help_query = "SELECT id, tema, jautajuma_apraksts, pievienotais_fails, statuss, atbilde, created_at
                FROM est_palidziba
                WHERE lietotaja_id = $user_id
                ORDER BY created_at DESC";
$help_result = $savienojums->query($help_query);
if ($help_result) {
    while ($row = $help_result->fetch_assoc()) {
        $help_messages[] = $row;
    }
} else {
    error_log("Help messages query failed: " . $savienojums->error);
}


$applications = [];
$check_table = $savienojums->query("SHOW TABLES LIKE 'est_pieteikumi'");
if ($check_table && $check_table->num_rows > 0) {
    $app_query = "SELECT p.id, p.sludinajuma_id, p.sludinajuma_veids, p.lietotaja_id, 
                         p.vards_uzvards, p.epasts, p.telefons, p.komentars, p.statuss, 
                         p.ires_menesi, p.nav_zinams, p.ires_sakuma_datums, p.sakuma_datums, 
                         p.beigu_datums, p.piedavata_summa, p.finansesanas_veids, p.created_at,
                         h.cena AS home_cena, h.pirts_cena_diena, h.balla_cena_diena
                  FROM est_pieteikumi p
                  LEFT JOIN est_homes h ON h.id = p.sludinajuma_id
                  WHERE p.lietotaja_id = $user_id
                  ORDER BY p.created_at DESC";
    
    $app_result = $savienojums->query($app_query);
    if ($app_result) {
        while ($row = $app_result->fetch_assoc()) {
            $applications[] = $row;
        }
    } else {
        error_log("Applications query failed: " . $savienojums->error);
    }
} else {
    error_log("Table est_pieteikumi does not exist");
}

$pageTitle = 'Mani ziņojumi - HomeEstate';
$extraStyles = ['user', 'myapplications', 'faq'];
$bodyClass = 'myapplications-page';
$navbarClass = 'navbar--hero';
include __DIR__ . '/../includes/header.php';
?>

<header class="myapplications-hero">
    <div class="myapplications-hero__inner">
        <p class="badge-pill"><i class="fas fa-folder-open"></i> Panelis</p>
        <h1>Mani <span>pieteikumi</span></h1>
        <p>Šeit varat apskatīt savus pieteikumus un palīdzības centra ziņojumus</p>
    </div>
</header>

<div class="container">

    <div class="applications-section">
        <div class="section-header">
            <h2>Īpašumu pieteikumi</h2>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>Jums vēl nav pieteikumu</h3>
                <p>Ja vēlaties pieteikties īpašumam, apmeklējiet īpašumu lapu un nosūtiet pieteikumu.</p>
                <a href="<?php echo main_route('property.list'); ?>" class="btn btn-primary">Apskatīt īpašumus</a>
            </div>
        <?php else: ?>
            <div class="apps-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="app-card" 
                         data-app-status="<?php echo htmlspecialchars((string)($app['statuss'] ?? '')); ?>"
                         data-app-vards="<?php echo htmlspecialchars((string)($app['vards_uzvards'] ?? '')); ?>"
                         data-app-email="<?php echo htmlspecialchars((string)($app['epasts'] ?? '')); ?>"
                         data-app-phone="<?php echo htmlspecialchars((string)($app['telefons'] ?? '')); ?>"
                         data-app-comment="<?php echo htmlspecialchars((string)($app['komentars'] ?? '')); ?>"
                         data-app-veids="<?php echo htmlspecialchars((string)($app['sludinajuma_veids'] ?? '')); ?>"
                         data-app-ires-menesi="<?php echo htmlspecialchars((string)($app['ires_menesi'] ?? '')); ?>"
                         data-app-nav-zinams="<?php echo htmlspecialchars((string)($app['nav_zinams'] ?? '')); ?>"
                         data-app-ires-sakuma-datums="<?php echo htmlspecialchars((string)($app['ires_sakuma_datums'] ?? '')); ?>"
                         data-app-sakuma-datums="<?php echo htmlspecialchars((string)($app['sakuma_datums'] ?? '')); ?>"
                         data-app-beigu-datums="<?php echo htmlspecialchars((string)($app['beigu_datums'] ?? '')); ?>"
                         data-app-piedavata-summa="<?php echo htmlspecialchars((string)($app['piedavata_summa'] ?? '')); ?>"
                         data-app-finansesanas-veids="<?php echo htmlspecialchars((string)($app['finansesanas_veids'] ?? '')); ?>"
                         data-app-home-cena="<?php echo htmlspecialchars((string)($app['home_cena'] ?? '0')); ?>"
                         data-app-pirts-cena="<?php echo htmlspecialchars((string)($app['pirts_cena_diena'] ?? '0')); ?>"
                         data-app-balla-cena="<?php echo htmlspecialchars((string)($app['balla_cena_diena'] ?? '0')); ?>">
                        <div class="app-top">
                            <div class="app-name">Pieteikums</div>
                            <div class="app-status status-<?php echo htmlspecialchars((string)($app['statuss'] ?? '')); ?>">
                                <?php
                                $status_text = [
                                    'Jauns' => 'Gaida apskati',
                                    'Apstiprinats' => 'Apstiprināts',
                                    'Rezervets' => 'Rezervēts',
                                    'Noraidits' => 'Noraidīts',
                                    'Apstiprināts' => 'Apstiprināts',
                                    'Noraidīts' => 'Noraidīts',
                                    'approved' => 'Apstiprināts',
                                    'rejected' => 'Noraidīts',
                                    'pending' => 'Gaida apskati'
                                ];
                                echo $status_text[$app['statuss']] ?? $app['statuss'];
                                ?>
                            </div>
                        </div>
                        
                        <div class="app-meta">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string)($app['epasts'] ?? '')); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars((string)($app['telefons'] ?? '')); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></span>
                        </div>

                        <div class="app-details">
                            <p><strong>Sludinājuma veids:</strong> <?php echo htmlspecialchars($app['sludinajuma_veids']); ?></p>
                            <p><strong>Vārds un uzvārds:</strong> <?php echo htmlspecialchars($app['vards_uzvards']); ?></p>
                            <?php if ($app['sludinajuma_veids'] === 'istermina_ire' && !empty($app['piedavata_summa'])): ?>
                                <p><strong>Aprēķinātā kopsumma:</strong> <?php echo htmlspecialchars(number_format((float)$app['piedavata_summa'], 0, ',', ' ')); ?> €</p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($app['komentars'])): ?>
                            <div class="app-comment">
                                "<?php echo nl2br(htmlspecialchars((string)$app['komentars'])); ?>"
                            </div>
                        <?php endif; ?>


                        <?php $appLocked = in_array($app['statuss'], ['Apstiprinats', 'Rezervets', 'Apstiprināts', 'approved'], true); ?>
                        <div class="app-actions">
                            <button class="btn-action btn-edit" <?php echo $appLocked ? 'disabled title="Apstiprinātu pieteikumu nevar rediģēt"' : 'onclick="editApplication(' . $app['id'] . ')"'; ?>>
                                <i class="fas fa-edit"></i> Rediģēt
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteApplication(<?php echo $app['id']; ?>)">
                                <i class="fas fa-trash"></i> Dzēst
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="help-section">
        <div class="section-header">
            <h2>Palīdzības centrs</h2>
        </div>
        
        <?php if (empty($help_messages)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope"></i>
                <h3>Jūs vēl neesat nosūtījis ziņojumus</h3>
                <p>Ja jums ir jautājumi, varat sazināties ar mūsu atbalsta komandu.</p>
                <a href="/faq" class="btn btn-primary">Kontaktēties ar atbalstu</a>
            </div>
        <?php else: ?>
            <div class="apps-grid">
                <?php foreach ($help_messages as $msg): ?>
                    <div class="app-card"
                         data-msg-status="<?php echo htmlspecialchars((string)($msg['statuss'] ?? '')); ?>"
                         data-msg-tema="<?php echo htmlspecialchars((string)($msg['tema'] ?? '')); ?>"
                         data-msg-apraksts="<?php echo htmlspecialchars((string)($msg['jautajuma_apraksts'] ?? '')); ?>"
                         data-msg-fails="<?php echo htmlspecialchars((string)($msg['pievienotais_fails'] ?? '')); ?>">
                        <div class="app-top">
                            <div class="app-name"><?php echo htmlspecialchars($msg['tema']); ?></div>
                            <div class="app-status status-<?php echo htmlspecialchars($msg['statuss']); ?>">
                                <?php
                                $status_text = [
                                    'Iesūtīts' => 'Gaida atbildi',
                                    'Atbildēts' => 'Atbildēts',
                                    'Aizvērts' => 'Aizvērts'
                                ];
                                echo $status_text[$msg['statuss']] ?? $msg['statuss'];
                                ?>
                            </div>
                        </div>
                        
                        <div class="app-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></span>
                            <span><i class="fas fa-envelope"></i> Palīdzības ziņojums</span>
                        </div>

                        <div class="app-details">
                            <p><?php echo nl2br(htmlspecialchars($msg['jautajuma_apraksts'])); ?></p>
                        </div>

                        <?php if ($msg['atbilde']): ?>
                            <div class="app-comment">
                                <strong><i class="fas fa-reply"></i> Atbilde:</strong><br>
                                <?php echo nl2br(htmlspecialchars($msg['atbilde'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php $msgLocked = in_array($msg['statuss'], ['Atbildēts', 'Aizvērts'], true); ?>
                        <div class="app-actions">
                            <button class="btn-action btn-edit" <?php echo $msgLocked ? 'disabled title="Atbildētu ziņojumu nevar rediģēt"' : 'onclick="editHelpMessage(' . $msg['id'] . ')"'; ?>>
                                <i class="fas fa-edit"></i> Rediģēt
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteHelpMessage(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-trash"></i> Dzēst
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Rediģēt</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editId">
                <input type="hidden" id="editType">
                
                <div id="applicationFields" style="display: none;">
                    <div class="form-group">
                        <label for="editStatus">Statuss</label>
                        <select id="editStatus" class="form-control" disabled>
                            <option value="Jauns">Gaida apskati</option>
                            <option value="Apstiprinats">Apstiprināts</option>
                            <option value="Noraidits">Noraidīts</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editFullName">Vārds un uzvārds</label>
                        <input type="text" id="editFullName" class="form-control" required maxlength="50" pattern="[A-Za-zĀ-ž\s]+" oninput="this.value=this.value.replace(/[^A-Za-zĀ-ž\s]/g,'')" title="Lūdzu ievadiet tikai burtus un atstarpes">
                    </div>
                    <div class="form-group">
                        <label for="editEmail">E-pasts</label>
                        <input type="email" id="editEmail" class="form-control" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="editPhone">Telefona nr.</label>
                        <input type="tel" id="editPhone" class="form-control" required maxlength="15" oninput="this.value=this.value.replace(/[^0-9+\s]/g,'')" title="Lūdzu ievadiet tikai ciparus, atstarpes un + zīmi">
                    </div>
                    <div id="editRentFields" style="display: none;">
                        <div class="form-group">
                            <label for="editRentMonths">Īres periods mēnešos</label>
                            <input type="number" id="editRentMonths" class="form-control" min="1" max="99" oninput="if(this.value.length>2)this.value=this.value.slice(0,2);if(parseInt(this.value)>99)this.value=99;">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" id="editRentUnknown" value="1"> Pagaidām nav zināms
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="editIresSakumaDatums">Sākuma datums</label>
                            <input type="datetime-local" id="editIresSakumaDatums" class="form-control">
                        </div>
                    </div>
                    <div id="editShortRentFields" style="display: none;">
                        <div class="form-group">
                            <label for="editSakumaDatums">Sākuma datums</label>
                            <input type="datetime-local" id="editSakumaDatums" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="editBeiguDatums">Beigu datums</label>
                            <input type="datetime-local" id="editBeiguDatums" class="form-control">
                        </div>
                        <div class="form-group" id="editPirtsGroup" style="display: none;">
                            <label for="editPirtsDienas">Pirts (<span id="editPirtsPriceLabel" style="color:var(--accent)">0</span> € / dienā)</label>
                            <input type="number" id="editPirtsDienas" class="form-control" min="0">
                        </div>
                        <div class="form-group" id="editBallaGroup" style="display: none;">
                            <label for="editBallaDienas">Baļļa (<span id="editBallaPriceLabel" style="color:var(--accent)">0</span> € / dienā)</label>
                            <input type="number" id="editBallaDienas" class="form-control" min="0">
                        </div>
                        <div class="form-group" id="editTotalGroup" style="background:rgba(var(--accent-rgb,99,102,241),0.07);border:1px solid rgba(var(--accent-rgb,99,102,241),0.18);border-radius:10px;padding:14px 16px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-weight:600;font-size:0.95rem;">Aprēķinātā kopsumma</span>
                                <span id="editTotalVal" style="font-size:1.2rem;font-weight:700;color:var(--accent);">—</span>
                            </div>
                        </div>
                    </div>
                    <div id="editSaleFields" style="display: none;">
                        <div class="form-group">
                            <label for="editPiedavataSumma">Piedāvātā summa</label>
                            <input type="number" id="editPiedavataSumma" class="form-control" max="9999999" oninput="if(this.value.length>7)this.value=this.value.slice(0,7);">
                        </div>
                        <div class="form-group">
                            <label for="editFinansesanasVeids">Finansēšanas veids</label>
                            <select id="editFinansesanasVeids" class="form-control">
                                <option value="cash">Skaidra nauda</option>
                                <option value="mortgage">Hipotēka</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editComment">Komentārs</label>
                        <textarea id="editComment" class="form-control" rows="4" maxlength="300"></textarea>
                    </div>
                </div>
                
                <div id="helpFields" style="display: none;">
                    <div class="form-group">
                        <label for="editTema">Tēma</label>
                        <select id="editTema" class="form-control" required>
                            <option value="" disabled selected>Izvēlieties tēmu...</option>
                            <option value="Maksājumi">Maksājumi</option>
                            <option value="Rezervācijas">Rezervācijas</option>
                            <option value="Profils">Profils</option>
                            <option value="Mans sludinājums">Mans sludinājums</option>
                            <option value="Cits">Cits</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editApraksts">Apraksts</label>
                        <textarea id="editApraksts" class="form-control" rows="4" required maxlength="500"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Esošie attēli</label>
                        <div id="existingImagesContainer" class="palidziba-upload-previews"></div>
                    </div>
                    <div class="form-group">
                        <label>Pievienot jaunus attēlus <span style="color:var(--gray-400);font-weight:500;">(Maks. 3 kopā)</span></label>
                        <div class="palidziba-upload-zone" id="helpUploadZone">
                            <input type="file" id="editHelpFiles" accept="image/jpeg,image/png" multiple>
                            <div class="palidziba-upload-zone__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p>Noklikšķiniet vai velciet attēlus šeit</p>
                            <span>JPG, PNG &bull; maks. 3 attēli &bull; maks. 5 MB katrs</span>
                        </div>
                        <div id="newImagesPreviewContainer" class="palidziba-upload-previews"></div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Atcelt</button>
                    <button type="submit" class="btn btn-primary">Saglabāt</button>
                </div>
            </form>
        </div>
    </div>
</div>





<script>
function toggleInputs(containerId, isEnabled) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const inputs = container.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.disabled = !isEnabled;
    });
}

function formatDateTimeLocal(dtStr) {
    if (!dtStr) return '';
    const clean = dtStr.trim();
    if (clean.length === 10) {
        return clean + 'T00:00';
    }
    return clean.replace(' ', 'T').substring(0, 16);
}

function parseExtrasFromComment(comment) {
    var pirts = 0;
    var balla = 0;
    var cleanComment = comment || '';
    var pirtsMatch = cleanComment.match(/Pirts:\s*(\d+)\s*dien(as|u)/i);
    if (pirtsMatch) {
        pirts = parseInt(pirtsMatch[1]) || 0;
    }
    var ballaMatch = cleanComment.match(/Baļļa:\s*(\d+)\s*dien(as|u)/i);
    if (ballaMatch) {
        balla = parseInt(ballaMatch[1]) || 0;
    }
    var lines = cleanComment.split('\n');
    if (lines.length > 0 && (lines[0].indexOf('Pirts:') !== -1 || lines[0].indexOf('Baļļa:') !== -1)) {
        lines.shift();
        cleanComment = lines.join('\n');
    }
    return { pirts: pirts, balla: balla, comment: cleanComment };
}

function updateEditTotal() {
    var startEl = document.getElementById('editSakumaDatums');
    var endEl = document.getElementById('editBeiguDatums');
    var pirtsEl = document.getElementById('editPirtsDienas');
    var ballaEl = document.getElementById('editBallaDienas');
    var totalEl = document.getElementById('editTotalVal');
    var priceNight = parseFloat(window.currentEditHomeCena) || 0;
    var pirtsDay = parseFloat(window.currentEditPirtsCena) || 0;
    var ballaDay = parseFloat(window.currentEditBallaCena) || 0;
    var nights = 0;
    var maxDays = 0;
    if (startEl && endEl && startEl.value && endEl.value) {
        var s = new Date(startEl.value.split('T')[0]);
        var e = new Date(endEl.value.split('T')[0]);
        var diff = Math.round((e - s) / 86400000);
        if (diff >= 0) {
            nights = diff;
            maxDays = diff + 1;
        }
    }
    if (pirtsEl) {
        pirtsEl.max = maxDays;
        var pVal = parseInt(pirtsEl.value) || 0;
        if (pVal > maxDays) {
            pirtsEl.value = maxDays;
        }
    }
    if (ballaEl) {
        ballaEl.max = maxDays;
        var bVal = parseInt(ballaEl.value) || 0;
        if (bVal > maxDays) {
            ballaEl.value = maxDays;
        }
    }
    var pirtsDias = parseInt(pirtsEl ? pirtsEl.value : '0') || 0;
    var ballaDias = parseInt(ballaEl ? ballaEl.value : '0') || 0;
    if (pirtsDias < 0) pirtsDias = 0;
    if (ballaDias < 0) ballaDias = 0;
    var total = (nights * priceNight) + (pirtsDias * pirtsDay) + (ballaDias * ballaDay);
    if (totalEl) {
        totalEl.textContent = total > 0 ? total.toLocaleString('lv-LV') + ' €' : '—';
    }
    window.currentEditTotal = total;
}

function handleEditDateChange() {
    var startEl = document.getElementById('editSakumaDatums');
    var endEl = document.getElementById('editBeiguDatums');
    var pirtsEl = document.getElementById('editPirtsDienas');
    var ballaEl = document.getElementById('editBallaDienas');
    var maxDays = 0;
    if (startEl && endEl && startEl.value && endEl.value) {
        var s = new Date(startEl.value.split('T')[0]);
        var e = new Date(endEl.value.split('T')[0]);
        var diff = Math.round((e - s) / 86400000);
        if (diff >= 0) {
            maxDays = diff + 1;
        }
    }
    if (pirtsEl) {
        var pVal = parseInt(pirtsEl.value) || 0;
        if (pVal > maxDays) {
            pirtsEl.value = '';
        }
    }
    if (ballaEl) {
        var bVal = parseInt(ballaEl.value) || 0;
        if (bVal > maxDays) {
            ballaEl.value = '';
        }
    }
    updateEditTotal();
}

function editApplication(id) {
    const appCard = document.querySelector(`[onclick*="editApplication(${id})"]`).closest('.app-card');
    if (!appCard) {
        console.error('Application card not found');
        return;
    }

    document.getElementById('modalTitle').textContent = 'Rediģēt pieteikumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'application';
    document.getElementById('applicationFields').style.display = 'block';
    document.getElementById('helpFields').style.display = 'none';

    toggleInputs('applicationFields', true);
    document.getElementById('editStatus').disabled = true;
    toggleInputs('helpFields', false);

    const raw = (appCard.getAttribute('data-app-status') || '').trim();
    const statusSelect = document.getElementById('editStatus');
    if (statusSelect && raw) {
        statusSelect.value = ['Jauns', 'Apstiprinats', 'Noraidits', 'Rezervets'].includes(raw) ? raw : 'Jauns';
    }

    const vards = (appCard.getAttribute('data-app-vards') || '').trim();
    const email = (appCard.getAttribute('data-app-email') || '').trim();
    const phone = (appCard.getAttribute('data-app-phone') || '').trim();
    const comment = (appCard.getAttribute('data-app-comment') || '').trim();
    const veids = (appCard.getAttribute('data-app-veids') || '').trim();
    const iresMenesi = (appCard.getAttribute('data-app-ires-menesi') || '').trim();
    const navZinams = (appCard.getAttribute('data-app-nav-zinams') || '').trim();
    const iresSakumaDatums = (appCard.getAttribute('data-app-ires-sakuma-datums') || '').trim();
    const sakumaDatums = (appCard.getAttribute('data-app-sakuma-datums') || '').trim();
    const beiguDatums = (appCard.getAttribute('data-app-beigu-datums') || '').trim();
    const piedavataSumma = (appCard.getAttribute('data-app-piedavata-summa') || '').trim();
    const finansesanasVeids = (appCard.getAttribute('data-app-finansesanas-veids') || '').trim();

    var parsedComment = comment;
    var pirtsVal = 0;
    var ballaVal = 0;
    if (veids === 'istermina_ire') {
        var parsed = parseExtrasFromComment(comment);
        parsedComment = parsed.comment;
        pirtsVal = parsed.pirts;
        ballaVal = parsed.balla;
    }

    document.getElementById('editFullName').value = vards;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editComment').value = parsedComment;

    document.getElementById('editRentFields').style.display = 'none';
    document.getElementById('editShortRentFields').style.display = 'none';
    document.getElementById('editSaleFields').style.display = 'none';

    toggleInputs('editRentFields', false);
    toggleInputs('editShortRentFields', false);
    toggleInputs('editSaleFields', false);

    if (veids === 'ire' || veids === 'rent') {
        document.getElementById('editRentFields').style.display = 'block';
        toggleInputs('editRentFields', true);

        document.getElementById('editRentMonths').value = iresMenesi;
        document.getElementById('editRentUnknown').checked = (navZinams === '1');
        document.getElementById('editIresSakumaDatums').value = formatDateTimeLocal(iresSakumaDatums);
    } else if (veids === 'istermina_ire') {
        document.getElementById('editShortRentFields').style.display = 'block';
        toggleInputs('editShortRentFields', true);

        document.getElementById('editSakumaDatums').value = formatDateTimeLocal(sakumaDatums);
        document.getElementById('editBeiguDatums').value = formatDateTimeLocal(beiguDatums);

        window.currentEditHomeCena = parseFloat(appCard.getAttribute('data-app-home-cena') || '0') || 0;
        window.currentEditPirtsCena = parseFloat(appCard.getAttribute('data-app-pirts-cena') || '0') || 0;
        window.currentEditBallaCena = parseFloat(appCard.getAttribute('data-app-balla-cena') || '0') || 0;

        var editPirtsGroup = document.getElementById('editPirtsGroup');
        var editBallaGroup = document.getElementById('editBallaGroup');

        if (window.currentEditPirtsCena > 0) {
            editPirtsGroup.style.display = 'block';
            document.getElementById('editPirtsPriceLabel').textContent = window.currentEditPirtsCena;
            document.getElementById('editPirtsDienas').value = pirtsVal > 0 ? pirtsVal : '';
        } else {
            editPirtsGroup.style.display = 'none';
            document.getElementById('editPirtsDienas').value = '';
        }

        if (window.currentEditBallaCena > 0) {
            editBallaGroup.style.display = 'block';
            document.getElementById('editBallaPriceLabel').textContent = window.currentEditBallaCena;
            document.getElementById('editBallaDienas').value = ballaVal > 0 ? ballaVal : '';
        } else {
            editBallaGroup.style.display = 'none';
            document.getElementById('editBallaDienas').value = '';
        }

        updateEditTotal();
    } else {
        document.getElementById('editSaleFields').style.display = 'block';
        toggleInputs('editSaleFields', true);

        document.getElementById('editPiedavataSumma').value = piedavataSumma;
        document.getElementById('editFinansesanasVeids').value = finansesanasVeids || 'cash';
    }

    document.getElementById('editModal').style.display = 'flex';
}

function editHelpMessage(id) {
    const msgCard = document.querySelector(`[onclick*="editHelpMessage(${id})"]`).closest('.app-card');
    if (!msgCard) {
        console.error('Help message card not found');
        return;
    }

    const themeElement = msgCard.querySelector('.app-name');
    const aprakstsElement = msgCard.querySelector('.app-details p');

    document.getElementById('modalTitle').textContent = 'Rediģēt ziņojumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'help';
    document.getElementById('applicationFields').style.display = 'none';
    document.getElementById('helpFields').style.display = 'block';

    toggleInputs('applicationFields', false);
    toggleInputs('helpFields', true);

    if (themeElement) {
        const themeText = themeElement.textContent.trim();
        const themeField = document.getElementById('editTema');
        if (themeField) {
            themeField.value = themeText;
        }
    }

    if (aprakstsElement) {
        const aprakstsText = aprakstsElement.textContent.trim();
        const aprakstsField = document.getElementById('editApraksts');
        if (aprakstsField) {
            aprakstsField.value = aprakstsText;
        }
    }

    const failsAttr = msgCard.getAttribute('data-msg-fails') || '';
    const container = document.getElementById('existingImagesContainer');
    container.innerHTML = '';
    
    if (failsAttr.trim() !== '') {
        const images = failsAttr.split(',').map(s => s.trim()).filter(Boolean);
        const assetBaseUrl = '<?php echo app_absolute_url(""); ?>';
        images.forEach(img => {
            const item = document.createElement('div');
            item.className = 'palidziba-upload-preview';
            item.setAttribute('data-path', img);
            
            const imgTag = document.createElement('img');
            imgTag.src = assetBaseUrl + img;
            imgTag.style.cursor = 'pointer';
            imgTag.onclick = function() { window.open(this.src, '_blank'); };
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'palidziba-upload-preview__remove';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                item.remove();
            };
            
            item.appendChild(imgTag);
            item.appendChild(removeBtn);
            container.appendChild(item);
        });
    }

    document.getElementById('editHelpFiles').value = '';
    const newContainer = document.getElementById('newImagesPreviewContainer');
    if (newContainer) newContainer.innerHTML = '';
    window.newHelpFiles = [];
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteApplication(id) {
    if (confirm('Vai tiešām vēlaties dzēst šo pieteikumu?')) {
        fetch('<?php echo asset_path("api/delete_application.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Kļūda dzēšot pieteikumu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda dzēšot pieteikumu');
        });
    }
}

function deleteHelpMessage(id) {
    if (confirm('Vai tiešām vēlaties dzēst šo ziņojumu?')) {
        fetch('<?php echo asset_path("api/delete_help_message.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Kļūda dzēšot ziņojumu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda dzēšot ziņojumu');
        });
    }
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const id = document.getElementById('editId').value;
    const type = document.getElementById('editType').value;

    let data = { id: id };

    if (type === 'application') {
        data.status = document.getElementById('editStatus').value;
        data.vards_uzvards = document.getElementById('editFullName').value;
        data.epasts = document.getElementById('editEmail').value;
        data.telefons = document.getElementById('editPhone').value;
        data.komentars = document.getElementById('editComment').value;

        const rentMonthsEl = document.getElementById('editRentMonths');
        if (rentMonthsEl && !rentMonthsEl.disabled) {
            data.ires_menesi = rentMonthsEl.value;
            data.nav_zinams = document.getElementById('editRentUnknown').checked ? 1 : 0;
            data.ires_sakuma_datums = document.getElementById('editIresSakumaDatums').value;
        }

        const sakumaDatumsEl = document.getElementById('editSakumaDatums');
        if (sakumaDatumsEl && !sakumaDatumsEl.disabled) {
            data.sakuma_datums = sakumaDatumsEl.value;
            data.beigu_datums = document.getElementById('editBeiguDatums').value;

            var pirtsEl = document.getElementById('editPirtsDienas');
            var ballaEl = document.getElementById('editBallaDienas');
            var pirtsDienas = parseInt(pirtsEl ? pirtsEl.value : '0') || 0;
            var ballaDienas = parseInt(ballaEl ? ballaEl.value : '0') || 0;
            var rawComment = document.getElementById('editComment').value.trim();
            var extraParts = [];
            if (pirtsDienas > 0) extraParts.push(`Pirts: ${pirtsDienas} dien${pirtsDienas === 1 ? 'u' : 'as'}`);
            if (ballaDienas > 0) extraParts.push(`Baļļa: ${ballaDienas} dien${ballaDienas === 1 ? 'u' : 'as'}`);
            var extrasStr = extraParts.join(', ');
            var finalComment = extrasStr ? (extrasStr + (rawComment ? '\n' + rawComment : '')) : rawComment;
            data.komentars = finalComment;
            data.piedavata_summa = window.currentEditTotal || 0;
        }

        const piedavataSummaEl = document.getElementById('editPiedavataSumma');
        if (piedavataSummaEl && !piedavataSummaEl.disabled) {
            data.piedavata_summa = piedavataSummaEl.value;
            data.finansesanas_veids = document.getElementById('editFinansesanasVeids').value;
        }

        fetch('<?php echo asset_path("api/update_application.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showPageAlert('Pieteikums veiksmīgi atjaunināts', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('Kļūda atjauninot pieteikumu: ' + (result.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda atjauninot pieteikumu: ' + error.message);
        });
    } else if (type === 'help') {
        const remainingImages = Array.from(document.querySelectorAll('#existingImagesContainer .palidziba-upload-preview'))
            .map(item => item.getAttribute('data-path'))
            .filter(Boolean);
        const fileInput = document.getElementById('editHelpFiles');
        const fileCount = fileInput ? fileInput.files.length : 0;

        if (remainingImages.length + fileCount > 3) {
            alert('Kopā var pievienot ne vairāk kā 3 attēlus.');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('tema', document.getElementById('editTema').value);
        formData.append('apraksts', document.getElementById('editApraksts').value);
        formData.append('kept_images', remainingImages.join(','));

        if (fileCount > 0) {
            for (let i = 0; i < fileCount; i++) {
                formData.append('pievienotais_fails[]', fileInput.files[i]);
            }
        }

        fetch('<?php echo asset_path("api/update_help_message.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showPageAlert('Ziņojums veiksmīgi atjaunināts', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('Kļūda atjauninot ziņojumu: ' + (result.message || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda atjauninot ziņojumu: ' + error.message);
        });
    }
});

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

window.newHelpFiles = [];
const helpFileInput = document.getElementById('editHelpFiles');
const newPreviewContainer = document.getElementById('newImagesPreviewContainer');

const helpUploadZone = document.getElementById('helpUploadZone');

if (helpFileInput) {
    helpFileInput.addEventListener('change', function(e) {
        const existingCount = document.querySelectorAll('#existingImagesContainer .palidziba-upload-preview').length;
        Array.from(this.files).forEach(f => {
            if (existingCount + window.newHelpFiles.length < 3) {
                const isDuplicate = window.newHelpFiles.some(existing => existing.name === f.name && existing.size === f.size);
                if (!isDuplicate) {
                    window.newHelpFiles.push(f);
                }
            }
        });
        renderNewHelpPreviews();
        syncHelpFileInput();
    });
}

if (helpUploadZone) {
    helpUploadZone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
    helpUploadZone.addEventListener('dragleave', function() { this.classList.remove('drag-over'); });
    helpUploadZone.addEventListener('drop', function(e) {
        e.preventDefault(); this.classList.remove('drag-over');
        const existingCount = document.querySelectorAll('#existingImagesContainer .palidziba-upload-preview').length;
        Array.from(e.dataTransfer.files).forEach(f => {
            if (f.type.startsWith('image/') && existingCount + window.newHelpFiles.length < 3) {
                const isDuplicate = window.newHelpFiles.some(existing => existing.name === f.name && existing.size === f.size);
                if (!isDuplicate) {
                    window.newHelpFiles.push(f);
                }
            }
        });
        renderNewHelpPreviews();
        syncHelpFileInput();
    });
}

function renderNewHelpPreviews() {
    if (!newPreviewContainer) return;
    newPreviewContainer.innerHTML = '';
    
    window.newHelpFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'palidziba-upload-preview';
            
            const imgTag = document.createElement('img');
            imgTag.src = e.target.result;
            imgTag.style.cursor = 'pointer';
            imgTag.onclick = function() { window.open(this.src, '_blank'); };
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'palidziba-upload-preview__remove';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                window.newHelpFiles.splice(index, 1);
                syncHelpFileInput();
                renderNewHelpPreviews();
            };
            
            item.appendChild(imgTag);
            item.appendChild(removeBtn);
            newPreviewContainer.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
}

function syncHelpFileInput() {
    if (!helpFileInput) return;
    const dt = new DataTransfer();
    window.newHelpFiles.forEach(f => dt.items.add(f));
    helpFileInput.files = dt.files;
}

window.newHelpFiles = [];
var editStart = document.getElementById('editSakumaDatums');
var editEnd = document.getElementById('editBeiguDatums');
var editPirts = document.getElementById('editPirtsDienas');
var editBalla = document.getElementById('editBallaDienas');
if (editStart) editStart.addEventListener('change', handleEditDateChange);
if (editEnd) editEnd.addEventListener('change', handleEditDateChange);
if (editPirts) editPirts.addEventListener('input', updateEditTotal);
if (editBalla) editBalla.addEventListener('input', updateEditTotal);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
