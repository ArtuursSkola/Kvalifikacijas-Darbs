<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';

if (!isset($_SESSION['user_id'])) {
    main_redirect('login');
}

$user_id = (int)$_SESSION['user_id'];
$help_messages = [];

$help_result = $savienojums->query(
        "SELECT id, tema, jautajuma_apraksts, statuss, atbilde, created_at
     FROM est_palidziba
     WHERE lietotaja_id = $user_id
     ORDER BY created_at DESC"
);
if ($help_result) {
    while ($row = $help_result->fetch_assoc()) $help_messages[] = $row;
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
            <div class="applications-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <h3>Pieteikums #<?php echo htmlspecialchars($app['id']); ?></h3>
                            <span class="status-badge status-<?php echo htmlspecialchars($app['status']); ?>">
                                <?php
                                $status_text = [
                                    'pending' => 'Gaida apskati',
                                    'approved' => 'Apstiprināts',
                                    'rejected' => 'Noraidīts'
                                ];
                                echo $status_text[$app['status']] ?? $app['status'];
                                ?>
                            </span>
                        </div>
                        <div class="application-details">
                            <p><strong>Izveidots:</strong> <?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></p>
                            <?php if ($app['updated_at']): ?>
                                <p><strong>Atjaunināts:</strong> <?php echo date('d.m.Y H:i', strtotime($app['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="application-actions">
                            <button class="btn btn-sm btn-primary" onclick="editApplication(<?php echo $app['id']; ?>)">
                                <i class="fas fa-edit"></i> Rediģēt
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteApplication(<?php echo $app['id']; ?>)">
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
            <div class="help-messages-grid">
                <?php foreach ($help_messages as $msg): ?>
                    <div class="help-message-card">
                        <div class="message-header">
                            <h3><?php echo htmlspecialchars($msg['tema']); ?></h3>
                            <span class="status-badge status-<?php echo htmlspecialchars($msg['statuss']); ?>">
                                <?php
                                $status_text = [
                                    'Iesūtīts' => 'Gaida atbildi',
                                    'Atbildēts' => 'Atbildēts',
                                    'Aizvērts' => 'Aizvērts'
                                ];
                                echo $status_text[$msg['statuss']] ?? $msg['statuss'];
                                ?>
                            </span>
                        </div>
                        <div class="message-content">
                            <p><?php echo nl2br(htmlspecialchars($msg['jautajuma_apraksts'])); ?></p>
                        </div>
                        <?php if ($msg['atbilde']): ?>
                            <div class="message-reply">
                                <h4><i class="fas fa-reply"></i> Atbilde:</h4>
                                <p><?php echo nl2br(htmlspecialchars($msg['atbilde'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="message-details">
                            <p><strong>Nosūtīts:</strong> <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></p>
                            <?php if ($msg['updated_at']): ?>
                                <p><strong>Atjaunināts:</strong> <?php echo date('d.m.Y H:i', strtotime($msg['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="message-actions">
                            <button class="btn btn-sm btn-primary" onclick="editHelpMessage(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-edit"></i> Rediģēt
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteHelpMessage(<?php echo $msg['id']; ?>)">
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
                            <option value="pending">Gaida apskati</option>
                            <option value="approved">Apstiprināts</option>
                            <option value="rejected">Noraidīts</option>
                        </select>
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

<style>
.applications-section, .help-section {
    margin-bottom: 40px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

.section-header h2 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin: 0;
}

.applications-grid, .help-messages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.application-card, .help-message-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.application-card:hover, .help-message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.application-header, .message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.application-header h3, .message-header h3 {
    color: #2c3e50;
    font-size: 1.1rem;
    margin: 0;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved, .status-answered {
    background: #d4edda;
    color: #155724;
}

.status-rejected, .status-closed {
    background: #f8d7da;
    color: #721c24;
}

.application-details, .message-details {
    margin-bottom: 15px;
}

.application-details p, .message-details p {
    margin: 5px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.message-content {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid var(--accent);
}

.message-reply {
    margin-bottom: 15px;
    padding: 15px;
    background: #e8f5e8;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}

.message-reply h4 {
    color: #155724;
    margin: 0 0 10px 0;
    font-size: 0.9rem;
}

.application-actions, .message-actions {
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed #dee2e6;
}

.empty-state i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #495057;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 20px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #495057;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 1rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--accent);
    color: #fff;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}
</style>

<script>
function editApplication(id) {
    document.getElementById('modalTitle').textContent = 'Rediģēt pieteikumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'application';
    document.getElementById('applicationFields').style.display = 'block';
    document.getElementById('helpFields').style.display = 'none';
    document.getElementById('editModal').style.display = 'flex';
}

function editHelpMessage(id) {
    document.getElementById('modalTitle').textContent = 'Rediģēt ziņojumu';
    document.getElementById('editId').value = id;
    document.getElementById('editType').value = 'help';
    document.getElementById('applicationFields').style.display = 'none';
    document.getElementById('helpFields').style.display = 'block';
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
