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

$help_query = "SELECT id, tema, jautajuma_apraksts, statuss, atbilde, created_at
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
                         p.created_at
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
                    <div class="app-card" data-app-status="<?php echo htmlspecialchars((string)($app['statuss'] ?? '')); ?>">
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
                    <div class="app-card">
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
                        <select id="editStatus" class="form-control">
                            <option value="Jauns">Gaida apskati</option>
                            <option value="Apstiprinats">Apstiprināts</option>
                            <option value="Noraidits">Noraidīts</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">E-pasts</label>
                        <input type="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editPhone">Telefona nr.</label>
                        <input type="tel" id="editPhone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editComment">Komentārs</label>
                        <textarea id="editComment" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                
                <div id="helpFields" style="display: none;">
                    <div class="form-group">
                        <label for="editTema">Tēma</label>
                        <input type="text" id="editTema" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editApraksts">Apraksts</label>
                        <textarea id="editApraksts" class="form-control" rows="4" required></textarea>
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
function editApplication(id) {

    const appCard = document.querySelector(`[onclick*="editApplication(${id})"]`).closest('.app-card');
    if (!appCard) {
        console.error('Application card not found');
        return;
    }

    const emailElement = appCard.querySelector('.app-meta span:nth-child(1)');
    const phoneElement = appCard.querySelector('.app-meta span:nth-child(2)');
    const dateElement = appCard.querySelector('.app-meta span:nth-child(3)');
    const commentElement = appCard.querySelector('.app-comment');
    const detailsElement = appCard.querySelector('.app-details');
    

    document.getElementById('modalTitle').textContent = 'Rediģēt pieteikumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'application';
    document.getElementById('applicationFields').style.display = 'block';
    document.getElementById('helpFields').style.display = 'none';
    

    const raw = (appCard.getAttribute('data-app-status') || '').trim();
    const statusSelect = document.getElementById('editStatus');
    if (statusSelect && raw) {
        statusSelect.value = ['Jauns', 'Apstiprinats', 'Noraidits', 'Rezervets'].includes(raw) ? raw : 'Jauns';
    }
    

    if (emailElement) {
        const emailText = emailElement.textContent.trim();

        const emailField = document.getElementById('editEmail');
        if (emailField) {
            emailField.value = emailText;
        }
    }
    
    if (phoneElement) {
        const phoneText = phoneElement.textContent.trim();

        const phoneField = document.getElementById('editPhone');
        if (phoneField) {
            phoneField.value = phoneText;
        }
    }
    
    if (commentElement) {
        const commentText = commentElement.textContent.trim();

        const commentField = document.getElementById('editComment');
        if (commentField) {
            commentField.value = commentText;
        }
    }
    document.getElementById('editModal').style.display = 'flex';
}

function editHelpMessage(id) {

    const msgCard = document.querySelector(`[onclick*="editHelpMessage(${id})"]`).closest('.app-card');
    if (!msgCard) {
        console.error('Help message card not found');
        return;
    }
    

    const statusElement = msgCard.querySelector('.app-status');
    const dateElement = msgCard.querySelector('.app-meta span:nth-child(1)');
    const themeElement = msgCard.querySelector('.app-name');
    const aprakstsElement = msgCard.querySelector('.app-details p');
    const atbildeElement = msgCard.querySelector('.app-comment');
    

    document.getElementById('modalTitle').textContent = 'Rediģēt ziņojumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'help';
    document.getElementById('applicationFields').style.display = 'none';
    document.getElementById('helpFields').style.display = 'block';
    

    if (statusElement) {
        const statusText = statusElement.textContent.trim();
        const statusSelect = document.getElementById('editStatus');
        if (statusSelect) {

            Array.from(statusSelect.options).forEach(option => {
                if (option.textContent.trim() === statusText) {
                    statusSelect.value = option.value;
                }
            });
        }
    }
    
    if (dateElement) {
        const dateText = dateElement.textContent.trim();

    }
    
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
    
    if (atbildeElement) {
        const atbildeText = atbildeElement.textContent.trim();
        const atbildeField = document.getElementById('editAtbilde');
        if (atbildeField) {
            atbildeField.value = atbildeText;
        }
    }
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function deleteApplication(id) {
    if (confirm('Vai tiešām vēlaties dzēst šo pieteikumu?')) {
        fetch('/api/delete_application', {
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
        fetch('/api/delete_help_message', {
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
        fetch('/api/update_application', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Kļūda atjauninot pieteikumu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda atjauninot pieteikumu');
        });
    } else if (type === 'help') {
        data.tema = document.getElementById('editTema').value;
        data.apraksts = document.getElementById('editApraksts').value;
        fetch('/api/update_help_message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Kļūda atjauninot ziņojumu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Kļūda atjauninot ziņojumu');
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
