<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php');
}
require $configPath;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . admin_route('dashboard'));
    exit;
}

$errors = [];
$success = '';
$form_type = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    
    $checkStmt = $savienojums->prepare("SELECT loma FROM est_admin WHERE admin_id = ?");
    $checkStmt->bind_param('i', $delId);
    $checkStmt->execute();
    $targetRole = $checkStmt->get_result()->fetch_assoc()['loma'] ?? '';
    $checkStmt->close();

    if ($targetRole === 'moderator') {
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
    } else {
        $errors[] = 'Administratorus nevar dzēst.';
    }
}

if (isset($_POST['create_mod'])) {
    $form_type = 'create';
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'moderator';
    
    if (preg_match('/[0-9]/', $newUsername)) {
        $errors[] = "Lietotājvārds nevar saturēt skaitļus";
    }
    if (strlen($newUsername) < 5) {
        $errors[] = "Lietotājvārdam jābūt vismaz 5 simbolus garam";
    } elseif (strlen($newUsername) > 25) {
        $errors[] = "Lietotājvārds nevar būt garāks par 25 simboliem";
    }

    if (strpos($newEmail, '@') === false) {
        $errors[] = "E-pastam jāsatur @ simbols";
    }

    if (strlen($newPassword) < 8) {
        $errors[] = "Parolei jābūt vismaz 8 simbolus garai";
    }
    if (!preg_match('/[0-9]/', $newPassword)) {
        $errors[] = "Parolei jāsatur vismaz viens skaitlis";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
        $errors[] = "Parolei jāsatur vismaz viens simbols";
    }
    if (!preg_match('/[a-zA-Z]/', $newPassword)) {
        $errors[] = "Parolei jāsatur vismaz viens burts";
    }

    if ($newUsername === '' || $newEmail === '' || $newPassword === '') {
        $errors[] = 'Aizpildi visus obligātos laukus.';
    }

    if (empty($errors)) {
        $chk = $savienojums->prepare("SELECT admin_id FROM est_admin WHERE lietotajvards=? OR epasts=? LIMIT 1");
        $chk->bind_param('ss', $newUsername, $newEmail);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau eksistē.';
        } else {
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
    $form_type = 'edit';
    $editId = (int)($_POST['edit_id'] ?? 0);
    $editUsername = trim($_POST['edit_username'] ?? '');
    $editEmail = trim($_POST['edit_email'] ?? '');
    $editRole = $_POST['edit_role'] ?? 'moderator';
    $editPassword = $_POST['edit_password'] ?? '';
    
    if ($editId > 0 && $editUsername !== '' && $editEmail !== '') {
        $checkStmt = $savienojums->prepare("SELECT loma FROM est_admin WHERE admin_id = ?");
        $checkStmt->bind_param('i', $editId);
        $checkStmt->execute();
        $targetRole = $checkStmt->get_result()->fetch_assoc()['loma'] ?? '';
        $checkStmt->close();

        if ($targetRole === 'moderator' || $editId == $_SESSION['user_id']) {
            if ($editPassword !== '') {
                if (strlen($editPassword) < 8) {
                    $errors[] = "Parolei jābūt vismaz 8 simbolus garai";
                }
                if (!preg_match('/[0-9]/', $editPassword)) {
                    $errors[] = "Parolei jāsatur vismaz viens skaitlis";
                }
                if (!preg_match('/[^a-zA-Z0-9]/', $editPassword)) {
                    $errors[] = "Parolei jāsatur vismaz viens simbols";
                }
                if (!preg_match('/[a-zA-Z]/', $editPassword)) {
                    $errors[] = "Parolei jāsatur vismaz viens burts";
                }
            }

            if (empty($errors)) {
                $chk = $savienojums->prepare("SELECT admin_id FROM est_admin WHERE (lietotajvards=? OR epasts=?) AND admin_id != ? LIMIT 1");
                $chk->bind_param('ssi', $editUsername, $editEmail, $editId);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    $errors[] = 'Lietotājvārds vai e-pasts jau aizņemts.';
                } else {
                    if ($editPassword !== '') {
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
        } else {
            $errors[] = 'Jums nav tiesību rediģēt citus administratorus.';
        }
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
    <link rel="stylesheet" href="../css/admin.css">
</head>
<?php
$autoModal = '';
$autoModalData = null;
if (!empty($errors)) {
    if ($form_type === 'create') {
        $autoModal = 'createModal';
    } elseif ($form_type === 'edit') {
        $autoModal = 'edit';
        $autoModalData = [
            'admin_id' => $_POST['edit_id'] ?? 0,
            'lietotajvards' => $_POST['edit_username'] ?? '',
            'epasts' => $_POST['edit_email'] ?? '',
            'loma' => $_POST['edit_role'] ?? 'moderator'
        ];
    }
}
?>
<body <?php echo $autoModal ? 'data-auto-modal="' . $autoModal . '"' : ''; ?> <?php echo $autoModalData ? 'data-auto-modal-data=\'' . htmlspecialchars(json_encode($autoModalData), ENT_QUOTES) . '\'' : ''; ?>>
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
        <?php if ($errors && $form_type === ''): ?>
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
                <h3>Sistēmas administratori un moderatori</h3>
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
                                    <?php if ($m['loma'] === 'moderator' || $m['admin_id'] == $_SESSION['user_id']): ?>
                                        <button class="btn-sm edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($m)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($m['loma'] === 'moderator'): ?>
                                        <a href="?delete=<?php echo $m['admin_id']; ?>" class="btn-sm delete" onclick="return confirm('Vai tiešām dzēst šo administratoru?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($m['loma'] === 'admin' && $m['admin_id'] != $_SESSION['user_id']): ?>
                                        <i class="fas fa-lock" style="color: #94a3b8; padding: 8px;" title="Cits administrators"></i>
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
            <?php if ($errors && $form_type === 'create'): ?>
                <div class="alert error" style="margin: 10px 20px 0;"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>
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
            <?php if ($errors && $form_type === 'edit'): ?>
                <div class="alert error" style="margin: 10px 20px 0;"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>
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
    <script src="../script.js"></script>
</body>
</html>
