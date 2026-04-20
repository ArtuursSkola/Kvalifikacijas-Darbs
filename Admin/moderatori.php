<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php');
}
require $configPath;

// Stingra piekļuves kontrole, tikai admin loma var piekļūt šai lapai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . admin_route('dashboard'));
    exit;
}

$errors = [];
$success = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $savienojums->prepare("DELETE FROM est_admin WHERE admin_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $success = 'Moderators dzēsts.';
        } else {
            $errors[] = 'Neizdevās dzēst: ' . $stmt->error;
        }
        $stmt->close();
    }
}
//Jauna moderatora izveide
if (isset($_POST['create_mod'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'moderator';
    
    if ($newUsername === '' || $newEmail === '' || $newPassword === '') {
        $errors[] = 'Aizpildi visus obligātos laukus.';
    } else {
        $chk = $savienojums->prepare("SELECT admin_id FROM est_admin WHERE lietotajvards=? OR epasts=? LIMIT 1");
        $chk->bind_param('ss', $newUsername, $newEmail);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau eksistē.';
        } else {
            // Pārbaude, vai parole jau ir šifrēta, bet jauna lietotāja gadījumā vienmēr šifrējam
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $ins = $savienojums->prepare("INSERT INTO est_admin (lietotajvards, epasts, parole, loma) VALUES (?, ?, ?, ?)");
            $ins->bind_param('ssss', $newUsername, $newEmail, $hash, $newRole);
            if ($ins->execute()) {
                $success = 'Moderators izveidots.';
            } else {
                $errors[] = 'Neizdevās izveidot: ' . $ins->error;
            }
            $ins->close();
        }
        $chk->close();
    }
}

if (isset($_POST['edit_mod'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $editUsername = trim($_POST['edit_username'] ?? '');
    $editEmail = trim($_POST['edit_email'] ?? '');
    $editRole = $_POST['edit_role'] ?? 'moderator';
    $editPassword = $_POST['edit_password'] ?? '';
    
    if ($editId > 0 && $editUsername !== '' && $editEmail !== '') {
        $chk = $savienojums->prepare("SELECT admin_id FROM est_admin WHERE (lietotajvards=? OR epasts=?) AND admin_id != ? LIMIT 1");
        $chk->bind_param('ssi', $editUsername, $editEmail, $editId);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau aizņemts.';
        } else {
            if ($editPassword !== '') {
                // Ja parole tiek mainīta, šifrējam to
                $hash = password_hash($editPassword, PASSWORD_DEFAULT);
                $upd = $savienojums->prepare("UPDATE est_admin SET lietotajvards=?, epasts=?, parole=?, loma=? WHERE admin_id=?");
                $upd->bind_param('ssssi', $editUsername, $editEmail, $hash, $editRole, $editId);
            } else {
                $upd = $savienojums->prepare("UPDATE est_admin SET lietotajvards=?, epasts=?, loma=? WHERE admin_id=?");
                $upd->bind_param('sssi', $editUsername, $editEmail, $editRole, $editId);
            }
            if ($upd->execute()) {
                $success = 'Informācija atjaunināta.';
            } else {
                $errors[] = 'Neizdevās atjaunināt: ' . $upd->error;
            }
            $upd->close();
        }
        $chk->close();
    }
}

$mods = [];
$res = $savienojums->query("SELECT admin_id, lietotajvards, epasts, loma, created_at FROM est_admin ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) {
    $mods[] = $row;
}

$totalMods = count($mods);
$adminCount = $savienojums->query("SELECT COUNT(*) FROM est_admin WHERE loma='admin'")->fetch_row()[0];
$moderatorCount = $savienojums->query("SELECT COUNT(*) FROM est_admin WHERE loma='moderator'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderatori - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1d2733 0%, #2c3e50 100%); padding: 24px 0; overflow-y: auto; }
        .sidebar-logo { color: #fff; font-size: 1.5rem; font-weight: 700; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo span { color: #30b607; }
        .sidebar-menu { list-style: none; padding: 16px 0; }
        .sidebar-menu li a { display: block; padding: 14px 24px; color: rgba(255,255,255,0.7); text-decoration: none; font-weight: 500; border-left: 3px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(255,255,255,0.08); color: #fff; border-left-color: #30b607; }
        .sidebar-menu li a i { margin-right: 12px; width: 20px; text-align: center; }
        .sidebar-user { position: absolute; bottom: 0; left: 0; right: 0; padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); display: flex; align-items: center; gap: 12px; color: #fff; }
        .sidebar-user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #30b607; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .sidebar-user-name { font-weight: 600; }
        .sidebar-user-role { font-size: 0.8rem; color: rgba(255,255,255,0.6); }
        .main-content { margin-left: 260px; padding: 24px 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h1 { font-size: 1.8rem; color: #1d2733; }
        .page-header h1 i { margin-right: 10px; }
        .btn-add { background: #30b607; color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; font-family: inherit; }
        .stats-row { display: flex; gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 16px 24px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 14px; flex: 1; }
        .stat-card i { font-size: 1.5rem; color: #30b607; }
        .stat-card .val { font-size: 1.4rem; font-weight: 700; color: #1d2733; }
        .stat-card .lbl { font-size: 0.8rem; color: #6b7a8f; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 16px; }
        .alert.success { background: #e8f8ed; color: #1f7a35; border: 1px solid #c5e8d0; }
        .alert.error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        .panel { background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); overflow: hidden; }
        .panel-header { padding: 18px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .panel-header h3 { font-size: 1.05rem; color: #1d2733; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #6b7a8f; font-size: 0.8rem; text-transform: uppercase; }
        tbody tr:hover { background: #f8fafc; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge.red { background: rgba(231,76,60,0.12); color: #e74c3c; }
        .badge.blue { background: rgba(52,152,219,0.12); color: #3498db; }
        .actions { display: flex; gap: 6px; }
        .btn-sm { padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-sm.edit { background: rgba(52,152,219,0.12); color: #3498db; }
        .btn-sm.delete { background: rgba(231,76,60,0.12); color: #e74c3c; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 16px; width: 500px; max-width: 95%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #1d2733; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7a8f; }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #1d2733; }
        .form-group input, .form-group select { width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; font-family: inherit; }
        .modal-footer { padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
        .btn-primary { background: #30b607; color: #fff; }
        .btn-secondary { background: #edf2f7; color: #1d2733; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 16px; } .stats-row { flex-direction: column; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">Home<span>Estate</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo admin_route('dashboard'); ?>"><i class="fas fa-home"></i> Pārskats</a></li>
            <li><a href="<?php echo admin_route('users'); ?>"><i class="fas fa-users"></i> Lietotāji</a></li>
            <li><a href="<?php echo admin_route('moderators'); ?>" class="active"><i class="fas fa-user-shield"></i> Moderatori</a></li>
            <li><a href="<?php echo admin_route('listings'); ?>"><i class="fas fa-building"></i> Sludinājumi</a></li>
            <li><a href="#"><i class="fas fa-shopping-cart"></i> Pirkumi</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> Statistika</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Iestatījumi</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <div>
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-shield"></i> Administrācijas personāls</h1>
            <button class="btn-add" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Jauns moderators</button>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-users-cog"></i>
                <div>
                    <div class="val"><?php echo $totalMods; ?></div>
                    <div class="lbl">Kopā personāls</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-shield"></i>
                <div>
                    <div class="val"><?php echo $adminCount; ?></div>
                    <div class="lbl">Administratori</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-edit"></i>
                <div>
                    <div class="val"><?php echo $moderatorCount; ?></div>
                    <div class="lbl">Moderatori</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Sistēmas administratori (est_admin)</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lietotājvārds</th>
                            <th>E-pasts</th>
                            <th>Loma</th>
                            <th>Izveidots</th>
                            <th>Darbības</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mods as $m): ?>
                            <tr>
                                <td>#<?php echo $m['admin_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($m['lietotajvards']); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['epasts']); ?></td>
                                <td>
                                    <span class="badge <?php echo $m['loma'] === 'admin' ? 'red' : 'blue'; ?>">
                                        <?php echo ucfirst($m['loma']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($m['created_at'])); ?></td>
                                <td class="actions">
                                    <button class="btn-sm edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($m['admin_id'] != $_SESSION['user_id']): // Neļaut dzēst sevi ?>
                                        <a href="?delete=<?php echo $m['admin_id']; ?>" class="btn-sm delete" onclick="return confirm('Vai tiešām dzēst šo administratoru?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="createModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Pievienot personālu</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Lietotājvārds</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label>E-pasts</label>
                        <input type="email" name="new_email" required>
                    </div>
                    <div class="form-group">
                        <label>Parole</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Loma</label>
                        <select name="new_role">
                            <option value="moderator">Moderators</option>
                            <option value="admin">Administrators</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Atcelt</button>
                    <button type="submit" name="create_mod" class="btn btn-primary">Izveidot</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Rediģēt personālu</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Lietotājvārds</label>
                        <input type="text" name="edit_username" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label>E-pasts</label>
                        <input type="email" name="edit_email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Jauna parole (atstāj tukšu, ja nemaini)</label>
                        <input type="password" name="edit_password">
                    </div>
                    <div class="form-group">
                        <label>Loma</label>
                        <select name="edit_role" id="edit_role">
                            <option value="moderator">Moderators</option>
                            <option value="admin">Administrators</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Atcelt</button>
                    <button type="submit" name="edit_mod" class="btn btn-primary">Saglabāt izmaiņas</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.admin_id;
            document.getElementById('edit_username').value = data.lietotajvards;
            document.getElementById('edit_email').value = data.epasts;
            document.getElementById('edit_role').value = data.loma;
            openModal('editModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
