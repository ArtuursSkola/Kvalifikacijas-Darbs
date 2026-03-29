<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

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

// Handle delete action (admin only)
if ($_SESSION['role'] === 'admin' && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $savienojums->prepare("DELETE FROM est_lietotaji WHERE lietotaja_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $success = 'Lietotājs dzēsts.';
        } else {
            $errors[] = 'Neizdevās dzēst: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle create new user
if ($_SESSION['role'] === 'admin' && isset($_POST['create_user'])) {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newEmail = trim($_POST['new_email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $newRole = $_POST['new_role'] ?? 'lietotajs';
    $newPlan = $_POST['new_plan'] ?? '';
    
    $allowedRoles = ['lietotajs', 'ipasnieks'];
    if (!in_array($newRole, $allowedRoles, true)) $newRole = 'lietotajs';
    
    if ($newUsername === '' || $newEmail === '' || $newPassword === '') {
        $errors[] = 'Aizpildi visus obligātos laukus.';
    } else {
        $chk = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE lietotajvards=? OR epasts=? LIMIT 1");
        $chk->bind_param('ss', $newUsername, $newEmail);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau eksistē.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $ins = $savienojums->prepare("INSERT INTO est_lietotaji (lietotajvards, epasts, parole, loma, plan) VALUES (?, ?, ?, ?, ?)");
            $planVal = $newPlan !== '' ? $newPlan : null;
            $ins->bind_param('sssss', $newUsername, $newEmail, $hash, $newRole, $planVal);
            if ($ins->execute()) {
                $success = 'Lietotājs izveidots.';
            } else {
                $errors[] = 'Neizdevās izveidot: ' . $ins->error;
            }
            $ins->close();
        }
        $chk->close();
    }
}

// Handle full user edit
if ($_SESSION['role'] === 'admin' && isset($_POST['edit_user'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $editUsername = trim($_POST['edit_username'] ?? '');
    $editEmail = trim($_POST['edit_email'] ?? '');
    $editRole = $_POST['edit_role'] ?? 'lietotajs';
    $editPlan = $_POST['edit_plan'] ?? '';
    $editPassword = $_POST['edit_password'] ?? '';
    
    $allowedRoles = ['lietotajs', 'ipasnieks'];
    if (!in_array($editRole, $allowedRoles, true)) $editRole = 'lietotajs';
    
    if ($editId > 0 && $editUsername !== '' && $editEmail !== '') {
        $chk = $savienojums->prepare("SELECT lietotaja_id FROM est_lietotaji WHERE (lietotajvards=? OR epasts=?) AND lietotaja_id != ? LIMIT 1");
        $chk->bind_param('ssi', $editUsername, $editEmail, $editId);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Lietotājvārds vai e-pasts jau aizņemts.';
        } else {
            $planVal = $editPlan !== '' ? $editPlan : null;
            if ($editPassword !== '') {
                $hash = password_hash($editPassword, PASSWORD_DEFAULT);
                $upd = $savienojums->prepare("UPDATE est_lietotaji SET lietotajvards=?, epasts=?, parole=?, loma=?, plan=? WHERE lietotaja_id=?");
                $upd->bind_param('sssssi', $editUsername, $editEmail, $hash, $editRole, $planVal, $editId);
            } else {
                $upd = $savienojums->prepare("UPDATE est_lietotaji SET lietotajvards=?, epasts=?, loma=?, plan=? WHERE lietotaja_id=?");
                $upd->bind_param('ssssi', $editUsername, $editEmail, $editRole, $planVal, $editId);
            }
            if ($upd->execute()) {
                $success = 'Lietotājs atjaunināts.';
            } else {
                $errors[] = 'Neizdevās atjaunināt: ' . $upd->error;
            }
            $upd->close();
        }
        $chk->close();
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search
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

// Get total count
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

// Get users
$users = [];
$sql = "SELECT lietotaja_id, lietotajvards, epasts, loma, plan, created_at FROM est_lietotaji $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
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

// Stats
$totalCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji")->fetch_row()[0];
$ownersCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji WHERE loma='ipasnieks'")->fetch_row()[0];
$todayCount = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lietotāji - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1d2733 0%, #2c3e50 100%);
            padding: 24px 0;
            overflow-y: auto;
        }
        .sidebar-logo {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo span { color: #30b607; }
        .sidebar-menu { list-style: none; padding: 16px 0; }
        .sidebar-menu li a {
            display: block;
            padding: 14px 24px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border-left-color: #30b607;
        }
        .sidebar-menu li a i { margin-right: 12px; width: 20px; text-align: center; }
        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
        }
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #30b607;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .sidebar-user-name { font-weight: 600; }
        .sidebar-user-role { font-size: 0.8rem; color: rgba(255,255,255,0.6); }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 24px 32px;
        }
        
        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            color: #1d2733;
        }
        .page-header h1 i { margin-right: 10px; }
        .btn-add {
            background: #30b607;
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        
        /* Stats Row */
        .stats-row {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-card i { font-size: 1.5rem; color: #30b607; }
        .stat-card .val { font-size: 1.4rem; font-weight: 700; color: #1d2733; }
        .stat-card .lbl { font-size: 0.8rem; color: #6b7a8f; }
        
        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .alert.success { background: #e8f8ed; color: #1f7a35; border: 1px solid #c5e8d0; }
        .alert.error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        
        /* Table Panel */
        .panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .panel-header h3 { font-size: 1.05rem; color: #1d2733; }
        .search-box {
            display: flex;
            gap: 8px;
        }
        .search-box input {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 220px;
        }
        .search-box button {
            padding: 10px 16px;
            background: #30b607;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        /* Table */
        .table-container { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #6b7a8f;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.green { background: rgba(48,182,7,0.12); color: #30b607; }
        .badge.blue { background: rgba(52,152,219,0.12); color: #3498db; }
        .badge.orange { background: rgba(243,156,18,0.12); color: #f39c12; }
        .badge.gray { background: #edf2f7; color: #6b7a8f; }
        
        /* Actions */
        .actions { display: flex; gap: 6px; }
        .btn-sm {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-sm.edit { background: rgba(52,152,219,0.12); color: #3498db; }
        .btn-sm.delete { background: rgba(231,76,60,0.12); color: #e74c3c; }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            padding: 18px;
        }
        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
        }
        .pagination a { background: #f8fafc; color: #1d2733; border: 1px solid #e2e8f0; }
        .pagination span.current { background: #30b607; color: #fff; }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #fff;
            border-radius: 16px;
            width: 500px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { color: #1d2733; }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7a8f;
        }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1d2733;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; }
        .btn-primary { background: #30b607; color: #fff; }
        .btn-secondary { background: #edf2f7; color: #1d2733; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 16px; }
            .stats-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">Home<span>Estate</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo admin_route('dashboard'); ?>"><i class="fas fa-home"></i> Pārskats</a></li>
            <li><a href="<?php echo admin_route('users'); ?>" class="active"><i class="fas fa-users"></i> Lietotāji</a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo admin_route('moderators'); ?>"><i class="fas fa-user-shield"></i> Moderatori</a></li>
            <?php endif; ?>
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
            <h1><i class="fas fa-users"></i> Lietotāji</h1>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <button class="btn-add" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Jauns lietotājs</button>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
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
                            <th>Reģistrēts</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <th>Darbības</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" style="text-align:center;color:#6b7a8f;padding:40px;">Nav lietotāju</td></tr>
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
                                        <?php if (!empty($u['plan'])): ?>
                                            <span class="badge <?php echo $u['plan'] === 'Gold' ? 'orange' : 'blue'; ?>"><?php echo htmlspecialchars($u['plan']); ?></span>
                                        <?php else: ?>
                                            <span class="badge gray">—</span>
                                        <?php endif; ?>
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

    <!-- Create User Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Jauns lietotājs</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
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
                                <option value="">Nav</option>
                                <option value="Silver">Silver</option>
                                <option value="Gold">Gold</option>
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

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Rediģēt lietotāju</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
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
                                <option value="">Nav</option>
                                <option value="Silver">Silver</option>
                                <option value="Gold">Gold</option>
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

    <script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
    function openEditModal(user) {
        document.getElementById('edit_id').value = user.lietotaja_id;
        document.getElementById('edit_username').value = user.lietotajvards;
        document.getElementById('edit_email').value = user.epasts;
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_role').value = user.loma || 'lietotajs';
        document.getElementById('edit_plan').value = user.plan || '';
        openModal('editModal');
    }
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
    </script>
</body>
</html>
