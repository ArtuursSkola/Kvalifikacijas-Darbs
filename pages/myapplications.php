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
                         p.beigu_datums, p.piedavata_summa, p.finansesanas_veids, p.created_at
                  FROM est_pieteikumi p
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
$extraStyles = ['user', 'myapplications'];
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
                         data-app-finansesanas-veids="<?php echo htmlspecialchars((string)($app['finansesanas_veids'] ?? '')); ?>">
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
                        <input type="text" id="editFullName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">E-pasts</label>
                        <input type="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editPhone">Telefona nr.</label>
                        <input type="tel" id="editPhone" class="form-control" required>
                    </div>
                    <div id="editRentFields" style="display: none;">
                        <div class="form-group">
                            <label for="editRentMonths">Īres periods mēnešos</label>
                            <input type="number" id="editRentMonths" class="form-control" min="1" max="99">
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
                    </div>
                    <div id="editSaleFields" style="display: none;">
                        <div class="form-group">
                            <label for="editPiedavataSumma">Piedāvātā summa</label>
                            <input type="number" id="editPiedavataSumma" class="form-control" step="0.01">
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
                        <textarea id="editComment" class="form-control" rows="4"></textarea>
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
                        <textarea id="editApraksts" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Esošie attēli</label>
                        <div id="existingImagesContainer" class="existing-images-list"></div>
                    </div>
                    <div class="form-group">
                        <label for="editHelpFiles">Pievienot jaunus attēlus (Maks. 3 kopā)</label>
                        <input type="file" id="editHelpFiles" class="form-control" accept="image/jpeg,image/png" multiple>
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

    document.getElementById('editFullName').value = vards;
    document.getElementById('editEmail').value = email;
    document.getElementById('editPhone').value = phone;
    document.getElementById('editComment').value = comment;

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
        const assetBaseUrl = '<?php echo asset_path(""); ?>';
        images.forEach(img => {
            const item = document.createElement('div');
            item.className = 'existing-img-item';
            item.setAttribute('data-path', img);
            
            const imgTag = document.createElement('img');
            imgTag.src = assetBaseUrl + img;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-img-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = function() {
                item.remove();
            };
            
            item.appendChild(imgTag);
            item.appendChild(removeBtn);
            container.appendChild(item);
        });
    }

    document.getElementById('editHelpFiles').value = '';
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
        const remainingImages = Array.from(document.querySelectorAll('#existingImagesContainer .existing-img-item'))
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
