<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';
require_once __DIR__ . '/../includes/popup_system.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php');
}
require $configPath;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','moderator'], true)) {
    header('Location: ' . admin_route('login'));
    exit;
}

$errors = [];
$success = '';
$form_type = '';


if ($_SESSION['role'] === 'admin' && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $savienojums->prepare("DELETE FROM est_lietotaji WHERE lietotaja_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $success = 'Lietotājs dzēsts.';
            $_SESSION['admin_success'] = 'delete_user';
            showSuccessPopup('Lietotājs veiksmīgi dzēsts!');
        } else {
            $errors[] = 'Neizdevās dzēst: ' . $stmt->error;
        }
        $stmt->close();
    }
}


if ($_SESSION['role'] === 'admin' && isset($_POST['create_user'])) {
    $form_type = 'create';
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'lietotajs';
    $newPlan = $_POST['new_plan'] ?? 'Nekads';
    
    $allowedRoles = ['lietotajs', 'ipasnieks'];
    if (!in_array($newRole, $allowedRoles, true)) $newRole = 'lietotajs';
    
    if ($newUsername === '' || $newEmail === '' || $newPassword === '') {
        $errors[] = 'Aizpildi visus obligātos laukus.';
    } else {
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
    }

    if (empty($errors)) {
        $chk = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE lietotajvards=? OR epasts=? LIMIT 1");
        $chk->bind_param('ss', $newUsername, $newEmail);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau eksistē.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $allowedPlans = ['Nekads', 'Bezmaksas', 'Sudraba', 'Zelta'];
            if (!in_array($newPlan, $allowedPlans, true)) $newPlan = 'Nekads';
            $ins = $savienojums->prepare("INSERT INTO est_lietotaji (lietotajvards, epasts, parole, loma, plans) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param('sssss', $newUsername, $newEmail, $hash, $newRole, $newPlan);
            if ($ins->execute()) {
                $success = 'Lietotājs izveidots.';
                $_SESSION['admin_success'] = 'create_user';
                showSuccessPopup('Lietotājs veiksmīgi izveidots!');
            } else {
                $errors[] = 'Neizdevās izveidot: ' . $ins->error;
            }
            $ins->close();
        }
        $chk->close();
    }
}


if ($_SESSION['role'] === 'admin' && isset($_POST['edit_user'])) {
    $form_type = 'edit';
    $editId = (int)($_POST['edit_id'] ?? 0);
    $editUsername = trim($_POST['edit_username'] ?? '');
    $editEmail = trim($_POST['edit_email'] ?? '');
    $editRole = $_POST['edit_role'] ?? 'lietotajs';
    $editPlan = $_POST['edit_plan'] ?? 'Nekads';
    $editPassword = $_POST['edit_password'] ?? '';
    
    $allowedRoles = ['lietotajs', 'ipasnieks'];
    if (!in_array($editRole, $allowedRoles, true)) $editRole = 'lietotajs';
    
    if ($editId > 0 && $editUsername !== '' && $editEmail !== '') {
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
            $chk = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE (lietotajvards=? OR epasts=?) AND lietotaja_id != ? LIMIT 1");
            $chk->bind_param('ssi', $editUsername, $editEmail, $editId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $errors[] = 'Lietotājvārds vai e-pasts jau aizņemts.';
            } else {
                $allowedPlans = ['Nekads', 'Bezmaksas', 'Sudraba', 'Zelta'];
                if (!in_array($editPlan, $allowedPlans, true)) $editPlan = 'Nekads';
                if ($editPassword !== '') {
                    $hash = password_hash($editPassword, PASSWORD_DEFAULT);
                    $upd = $savienojums->prepare("UPDATE est_lietotaji SET lietotajvards=?, epasts=?, parole=?, loma=?, plans=? WHERE lietotaja_id=?");
                    $upd->bind_param('sssssi', $editUsername, $editEmail, $hash, $editRole, $editPlan, $editId);
                } else {
                    $upd = $savienojums->prepare("UPDATE est_lietotaji SET lietotajvards=?, epasts=?, loma=?, plans=? WHERE lietotaja_id=?");
                    $upd->bind_param('ssssi', $editUsername, $editEmail, $editRole, $editPlan, $editId);
                }
                if ($upd->execute()) {
                    $success = 'Lietotājs atjaunināts.';
                    $_SESSION['admin_success'] = 'edit_user';
                    showSuccessPopup('Izmaiņas tika saglabātas!');
                } else {
                    $errors[] = 'Neizdevās atjaunināt: ' . $upd->error;
                }
                $upd->close();
            }
            $chk->close();
        }
    }
}


$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;


$search = trim($_GET['search'] ?? '');
$whereClause = '';
$params = [];
$types = '';

if ($search !== '') {
    $whereClause = "WHERE lietotajvards LIKE ? OR epasts LIKE ?";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam];
    $types = 'ss';
}


$countSql = "SELECT COUNT(*) FROM est_lietotaji $whereClause";
$totalUsers = 0;
if ($search !== '') {
    $stmt = $savienojums->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $totalUsers = $res->fetch_row()[0];
    $stmt->close();
} else {
    $res = $savienojums->query($countSql);
    $totalUsers = $res->fetch_row()[0];
}

$totalPages = max(1, ceil($totalUsers / $perPage));


$users = [];
$sql = "SELECT lietotaja_id, lietotajvards, epasts, loma, plans, plana_beigas, created_at FROM est_lietotaji $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
if ($search !== '') {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param($types . 'ii', ...[...$params, $perPage, $offset]);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
}
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();


$totalCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji")->fetch_row()[0];
$ownersCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji WHERE loma='ipasnieks'")->fetch_row()[0];
$todayCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];

if (isset($_SESSION['admin_success'])) {
    $successType = $_SESSION['admin_success'];
    unset($_SESSION['admin_success']);
    $popupMessage = '';
    if ($successType === 'delete_user') {
        $popupMessage = 'Lietotājs veiksmīgi dzēsts';
    } elseif ($successType === 'create_user') {
        $popupMessage = 'Lietotājs veiksmīgi izveidots';
    } elseif ($successType === 'edit_user') {
        $popupMessage = 'Lietotājs veiksmīgi atjaunināts';
    }
    if ($popupMessage !== '') {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showPageAlert('$popupMessage', 'success'); });</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lietotāji - Admin</title>
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
            'lietotaja_id' => $_POST['edit_id'] ?? 0,
            'lietotajvards' => $_POST['edit_username'] ?? '',
            'epasts' => $_POST['edit_email'] ?? '',
            'loma' => $_POST['edit_role'] ?? 'lietotajs',
            'plans' => $_POST['edit_plan'] ?? 'Nekads'
        ];
    }
}
?>
<body <?php echo $autoModal ? 'data-auto-modal="' . $autoModal . '"' : ''; ?> <?php echo $autoModalData ? 'data-auto-modal-data=\'' . htmlspecialchars(json_encode($autoModalData), ENT_QUOTES) . '\'' : ''; ?>>
    <aside class="sidebar">
        <div class="sidebar-logo">Home<span>Estate</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo admin_route('dashboard'); ?>"><i class="fas fa-home"></i> Pārskats</a></li>
            <li><a href="<?php echo admin_route('users'); ?>" class="active"><i class="fas fa-users"></i> Lietotāji</a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo admin_route('moderators'); ?>"><i class="fas fa-user-shield"></i> Moderatori</a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_route('listings'); ?>"><i class="fas fa-building"></i> Sludinājumi</a></li>
            <li><a href="<?php echo admin_route('palidziba'); ?>"><i class="fas fa-headset"></i> Palīdzības centrs</a></li>
            <li><a href="<?php echo admin_route('subscription_dashboard'); ?>"><i class="fas fa-shopping-cart"></i> Abonementi</a></li>
            <li><a href="<?php echo admin_route('subscription_dashboard'); ?>"><i class="fas fa-chart-bar"></i> Statistika</a></li>
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
            <h1><i class="fas fa-users"></i> Lietotāji</h1>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <button class="btn-add" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Jauns lietotājs</button>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors && $form_type === ''): ?>
            <div class="alert error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div>
                    <div class="val"><?php echo $totalCount; ?></div>
                    <div class="lbl">Kopā lietotāji</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-tie"></i>
                <div>
                    <div class="val"><?php echo $ownersCount; ?></div>
                    <div class="lbl">Īpašnieki</div>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-plus"></i>
                <div>
                    <div class="val"><?php echo $todayCount; ?></div>
                    <div class="lbl">Šodien</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Visi lietotāji (<?php echo $totalUsers; ?>)</h3>
                <form class="search-box" method="GET">
                    <input type="text" name="search" placeholder="Meklēt..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lietotājvārds</th>
                            <th>E-pasts</th>
                            <th>Loma</th>
                            <th>Plāns</th>
                            <th>Termiņš</th>
                            <th>Reģistrēts</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <th>Darbības</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="8" style="text-align:center;color:#6b7a8f;padding:40px;">Nav lietotāju</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['lietotaja_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['lietotajvards']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['epasts']); ?></td>
                                    <td>
                                        <?php 
                                            $roleBadge = 'gray';
                                            if ($u['loma'] === 'ipasnieks') $roleBadge = 'green';
                                            elseif ($u['loma'] === 'admin') $roleBadge = 'blue';
                                        ?>
                                        <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($u['loma'] ?? 'lietotajs'); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['plans']) && $u['plans'] !== 'Nekads'): ?>
                                            <span class="badge <?php echo $u['plans'] === 'Zelta' ? 'orange' : 'blue'; ?>"><?php echo htmlspecialchars($u['plans']); ?></span>
                                        <?php else: ?>
                                            <span class="badge gray">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $noPlan = empty($u['plans']) || in_array($u['plans'], ['Nekads', 'Bezmaksas'], true);
                                        if ($noPlan || empty($u['plana_beigas'])) {
                                            echo '<span class="badge gray">—</span>';
                                        } else {
                                            $expires = strtotime($u['plana_beigas']);
                                            $now = time();
                                            if ($expires > $now) {
                                                $days = ceil(($expires - $now) / 86400);
                                                echo '<span style="font-weight:600;color:#30b607;">' . $days . ' dienas</span>';
                                            } else {
                                                echo '<span style="color:#e74c3c;font-weight:600;">Beidzies</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $u['created_at'] ? date('d.m.Y H:i', strtotime($u['created_at'])) : '—'; ?></td>
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <td>
                                            <div class="actions">
                                                <button type="button" class="btn-sm edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)"><i class="fas fa-edit"></i></button>
                                                <a href="?delete=<?php echo $u['lietotaja_id']; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="btn-sm delete" onclick="return confirm('Vai tiešām dzēst?')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Jauns lietotājs</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <?php if ($errors && $form_type === 'create'): ?>
                <div class="alert error" style="margin: 10px 20px 0;"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Lietotājvārds *</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label>E-pasts *</label>
                        <input type="email" name="new_email" required>
                    </div>
                    <div class="form-group">
                        <label>Parole *</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Loma</label>
                            <select name="new_role">
                                <option value="lietotajs">Lietotājs</option>
                                <option value="ipasnieks">Īpašnieks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Plāns</label>
                            <select name="new_plan">
                                <option value="Nekads">Nekads</option>
                                <option value="Bezmaksas">Bezmaksas</option>
                                <option value="Sudraba">Sudraba</option>
                                <option value="Zelta">Zelta</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Atcelt</button>
                    <button type="submit" name="create_user" class="btn btn-primary">Izveidot</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Rediģēt lietotāju</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <?php if ($errors && $form_type === 'edit'): ?>
                <div class="alert error" style="margin: 10px 20px 0;"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Lietotājvārds *</label>
                        <input type="text" name="edit_username" id="edit_username" required>
                    </div>
                    <div class="form-group">
                        <label>E-pasts *</label>
                        <input type="email" name="edit_email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Jauna parole (atstāj tukšu, ja nemainīt)</label>
                        <input type="password" name="edit_password" id="edit_password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Loma</label>
                            <select name="edit_role" id="edit_role">
                                <option value="lietotajs">Lietotājs</option>
                                <option value="ipasnieks">Īpašnieks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Plāns</label>
                            <select name="edit_plan" id="edit_plan">
                                <option value="Nekads">Nekads</option>
                                <option value="Bezmaksas">Bezmaksas</option>
                                <option value="Sudraba">Sudraba</option>
                                <option value="Zelta">Zelta</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Atcelt</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Saglabāt</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>
