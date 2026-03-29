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

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $savienojums->prepare("DELETE FROM est_homes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $delId);
        if ($stmt->execute()) {
            $success = 'Sludinājums dzēsts.';
        }
        $stmt->close();
    }
}

// Handle approve (change draft/melnraksts to active)
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $approveId = (int)$_GET['approve'];
    $stmt = $savienojums->prepare("UPDATE est_homes SET status = 'active' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $approveId);
        if ($stmt->execute()) {
            $success = 'Sludinājums apstiprināts un tagad ir aktīvs!';
        }
        $stmt->close();
    }
}

// Handle reject (set to rejected status)
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $rejectId = (int)$_GET['reject'];
    $stmt = $savienojums->prepare("UPDATE est_homes SET status = 'rejected' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $rejectId);
        if ($stmt->execute()) {
            $success = 'Sludinājums noraidīts.';
        }
        $stmt->close();
    }
}

// Handle create
if (isset($_POST['create_listing'])) {
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $type = $_POST['type'] ?? 'rent';
    $price = (float)($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $description = trim($_POST['description'] ?? '');
    
    if ($title !== '' && $city !== '') {
        $stmt = $savienojums->prepare("INSERT INTO est_homes (title, city, type, price, status, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssdss', $title, $city, $type, $price, $status, $description);
        if ($stmt->execute()) {
            $success = 'Sludinājums izveidots.';
        }
        $stmt->close();
    }
}

// Handle edit
if (isset($_POST['edit_listing'])) {
    $id = (int)($_POST['listing_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $type = $_POST['type'] ?? 'rent';
    $price = (float)($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $description = trim($_POST['description'] ?? '');
    
    if ($id > 0 && $title !== '') {
        $stmt = $savienojums->prepare("UPDATE est_homes SET title=?, city=?, type=?, price=?, status=?, description=? WHERE id=?");
        $stmt->bind_param('sssdssi', $title, $city, $type, $price, $status, $description, $id);
        if ($stmt->execute()) {
            $success = 'Sludinājums atjaunināts.';
        }
        $stmt->close();
    }
}

// Pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$whereConditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $whereConditions[] = "(title LIKE ? OR city LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}
if ($filterType !== '') {
    $whereConditions[] = "type = ?";
    $params[] = $filterType;
    $types .= 's';
}
if ($filterStatus !== '') {
    $whereConditions[] = "status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count
$countSql = "SELECT COUNT(*) FROM est_homes $whereClause";
if ($params) {
    $stmt = $savienojums->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalHomes = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $totalHomes = $savienojums->query($countSql)->fetch_row()[0];
}
$totalPages = max(1, ceil($totalHomes / $perPage));

// Get listings
$homes = [];
$sql = "SELECT h.*, u.lietotajvards as owner_name FROM est_homes h LEFT JOIN est_lietotaji u ON h.owner_id = u.lietotaja_id $whereClause ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
if ($params) {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $homes[] = $row;
}
$stmt->close();

// Stats
$totalCount = $savienojums->query("SELECT COUNT(*) FROM est_homes")->fetch_row()[0];
$activeCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='active'")->fetch_row()[0];
$draftCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='draft' OR status='melnraksts' OR status='' OR status IS NULL")->fetch_row()[0];
$pendingCount = $draftCount; // Drafts awaiting approval
$rentCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE type='rent'")->fetch_row()[0];
$buyCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE type='buy'")->fetch_row()[0];
$rejectedCount = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status='rejected'")->fetch_row()[0];

function buildUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    unset($params['delete']);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sludinājumi - Admin</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
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
            flex-wrap: wrap;
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
        .stat-card.blue i { color: #3498db; }
        .stat-card.orange i { color: #f39c12; }
        .stat-card.green i { color: #27ae60; }
        .stat-card.gray i { color: #6b7a8f; }
        .stat-card.purple i { color: #9b59b6; }
        .stat-card.red i { color: #e74c3c; }
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
        .filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters input, .filters select {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
        }
        .filters input { width: 160px; }
        .filters button {
            padding: 8px 14px;
            background: #30b607;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .filters .clear {
            color: #e74c3c;
            text-decoration: none;
            font-size: 0.85rem;
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
        .price { font-weight: 600; color: #30b607; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.blue { background: rgba(52,152,219,0.12); color: #3498db; }
        .badge.orange { background: rgba(243,156,18,0.12); color: #f39c12; }
        .badge.green { background: rgba(39,174,96,0.12); color: #27ae60; }
        .badge.gray { background: #edf2f7; color: #6b7a8f; }
        .badge.red { background: rgba(231,76,60,0.12); color: #e74c3c; }
        
        /* Actions */
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-sm {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        .btn-sm.edit { background: rgba(52,152,219,0.12); color: #3498db; }
        .btn-sm.view { background: rgba(39,174,96,0.12); color: #27ae60; }
        .btn-sm.delete { background: rgba(231,76,60,0.12); color: #e74c3c; }
        .btn-sm.approve { background: rgba(39,174,96,0.15); color: #27ae60; }
        .btn-sm.reject { background: rgba(231,76,60,0.15); color: #e74c3c; }
        .btn-sm:hover { transform: scale(1.1); }
        .btn-sm.approve:hover { background: #27ae60; color: #fff; }
        .btn-sm.reject:hover { background: #e74c3c; color: #fff; }
        
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
            width: 600px;
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
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
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
            <li><a href="<?php echo admin_route('users'); ?>"><i class="fas fa-users"></i> Lietotāji</a></li>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo admin_route('moderators'); ?>"><i class="fas fa-user-shield"></i> Moderatori</a></li>
            <?php endif; ?>
            <li><a href="<?php echo admin_route('listings'); ?>" class="active"><i class="fas fa-building"></i> Sludinājumi</a></li>
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
            <h1><i class="fas fa-building"></i> Sludinājumi</h1>
            <button class="btn-add" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Jauns sludinājums</button>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <div>
                    <div class="val"><?php echo $totalCount; ?></div>
                    <div class="lbl">Kopā</div>
                </div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-check-circle"></i>
                <div>
                    <div class="val"><?php echo $activeCount; ?></div>
                    <div class="lbl">Aktīvi</div>
                </div>
            </div>
            <div class="stat-card purple">
                <i class="fas fa-clock"></i>
                <div>
                    <div class="val"><?php echo $draftCount; ?></div>
                    <div class="lbl">Gaida apstiprinājumu</div>
                </div>
            </div>
            <div class="stat-card blue">
                <i class="fas fa-key"></i>
                <div>
                    <div class="val"><?php echo $rentCount; ?></div>
                    <div class="lbl">Īre</div>
                </div>
            </div>
            <div class="stat-card orange">
                <i class="fas fa-tag"></i>
                <div>
                    <div class="val"><?php echo $buyCount; ?></div>
                    <div class="lbl">Pārdošana</div>
                </div>
            </div>
            <?php if ($rejectedCount > 0): ?>
            <div class="stat-card red">
                <i class="fas fa-ban"></i>
                <div>
                    <div class="val"><?php echo $rejectedCount; ?></div>
                    <div class="lbl">Noraidīti</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3>Visi sludinājumi (<?php echo $totalHomes; ?>)</h3>
                <form class="filters" method="GET">
                    <input type="text" name="search" placeholder="Meklēt..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="type">
                        <option value="">Visi tipi</option>
                        <option value="rent" <?php echo $filterType === 'rent' ? 'selected' : ''; ?>>Īre</option>
                        <option value="buy" <?php echo $filterType === 'buy' ? 'selected' : ''; ?>>Pārdošana</option>
                    </select>
                    <select name="status">
                        <option value="">Visi statusi</option>
                        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Aktīvs</option>
                        <option value="draft" <?php echo $filterStatus === 'draft' ? 'selected' : ''; ?>>Melnraksts</option>
                        <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Neaktīvs</option>
                        <option value="sold" <?php echo $filterStatus === 'sold' ? 'selected' : ''; ?>>Pārdots</option>
                    </select>
                    <button type="submit"><i class="fas fa-filter"></i></button>
                    <?php if ($search || $filterType || $filterStatus): ?>
                        <a href="<?php echo admin_route('listings'); ?>" class="clear">Notīrīt</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nosaukums</th>
                            <th>Pilsēta</th>
                            <th>Tips</th>
                            <th>Cena</th>
                            <th>Īpašnieks</th>
                            <th>Statuss</th>
                            <th>Datums</th>
                            <th>Darbības</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($homes)): ?>
                            <tr><td colspan="9" style="text-align:center;color:#6b7a8f;padding:40px;">Nav sludinājumu</td></tr>
                        <?php else: ?>
                            <?php foreach ($homes as $h): ?>
                                <tr>
                                    <td><?php echo $h['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($h['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($h['city']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $h['type'] === 'rent' ? 'blue' : 'orange'; ?>">
                                            <?php echo $h['type'] === 'rent' ? 'Īre' : 'Pārdošana'; ?>
                                        </span>
                                    </td>
                                    <td class="price">€<?php echo number_format($h['price'], 0, ',', ' '); ?></td>
                                    <td><?php echo htmlspecialchars($h['owner_name'] ?? '—'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = ['active' => 'green', 'draft' => 'gray', 'melnraksts' => 'gray', 'inactive' => 'red', 'sold' => 'blue', 'rejected' => 'red'];
                                        $statusLabel = ['active' => 'Aktīvs', 'draft' => 'Gaida apstiprinājumu', 'melnraksts' => 'Gaida apstiprinājumu', 'inactive' => 'Neaktīvs', 'sold' => 'Pārdots', 'rejected' => 'Noraidīts'];
                                        $st = $h['status'] ?: 'draft';
                                        ?>
                                        <span class="badge <?php echo $statusClass[$st] ?? 'gray'; ?>">
                                            <?php echo $statusLabel[$st] ?? $st; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $h['created_at'] ? date('d.m.Y', strtotime($h['created_at'])) : '—'; ?></td>
                                    <td>
                                        <div class="actions">
                                            <?php if (in_array($h['status'], ['draft', 'melnraksts', '', null])): ?>
                                                <a href="<?php echo buildUrl(['approve' => $h['id']]); ?>" class="btn-sm approve" onclick="return confirm('Apstiprināt šo sludinājumu?')" title="Apstiprināt"><i class="fas fa-check"></i></a>
                                                <a href="<?php echo buildUrl(['reject' => $h['id']]); ?>" class="btn-sm reject" onclick="return confirm('Noraidīt šo sludinājumu?')" title="Noraidīt"><i class="fas fa-times"></i></a>
                                            <?php endif; ?>
                                            <button class="btn-sm edit" onclick='openEditModal(<?php echo json_encode($h); ?>)' title="Rediģēt"><i class="fas fa-edit"></i></button>
                                            <a href="<?php echo main_route('property.show', ['id' => $h['id']]); ?>" class="btn-sm view" target="_blank" title="Skatīt"><i class="fas fa-eye"></i></a>
                                            <a href="<?php echo buildUrl(['delete' => $h['id']]); ?>" class="btn-sm delete" onclick="return confirm('Vai tiešām dzēst?')" title="Dzēst"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo buildUrl(['page' => $page + 1]); ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Create Modal -->
    <div class="modal-overlay" id="createModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Jauns sludinājums</h3>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nosaukums *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilsēta *</label>
                            <input type="text" name="city" required>
                        </div>
                        <div class="form-group">
                            <label>Cena (€)</label>
                            <input type="number" name="price" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tips</label>
                            <select name="type">
                                <option value="rent">Īre</option>
                                <option value="buy">Pārdošana</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statuss</label>
                            <select name="status">
                                <option value="draft">Melnraksts</option>
                                <option value="active">Aktīvs</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Apraksts</label>
                        <textarea name="description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Atcelt</button>
                    <button type="submit" name="create_listing" class="btn btn-primary">Izveidot</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Rediģēt sludinājumu</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="listing_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nosaukums *</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilsēta *</label>
                            <input type="text" name="city" id="edit_city" required>
                        </div>
                        <div class="form-group">
                            <label>Cena (€)</label>
                            <input type="number" name="price" id="edit_price" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tips</label>
                            <select name="type" id="edit_type">
                                <option value="rent">Īre</option>
                                <option value="buy">Pārdošana</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statuss</label>
                            <select name="status" id="edit_status">
                                <option value="draft">Melnraksts</option>
                                <option value="active">Aktīvs</option>
                                <option value="inactive">Neaktīvs</option>
                                <option value="sold">Pārdots</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Apraksts</label>
                        <textarea name="description" id="edit_description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Atcelt</button>
                    <button type="submit" name="edit_listing" class="btn btn-primary">Saglabāt</button>
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
    function openEditModal(listing) {
        document.getElementById('edit_id').value = listing.id;
        document.getElementById('edit_title').value = listing.title || '';
        document.getElementById('edit_city').value = listing.city || '';
        document.getElementById('edit_price').value = listing.price || '';
        document.getElementById('edit_type').value = listing.type || 'rent';
        document.getElementById('edit_status').value = listing.status || 'draft';
        document.getElementById('edit_description').value = listing.description || '';
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
