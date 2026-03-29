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

function fetchCount(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_row()) {
        return (int)$row[0];
    }
    return 0;
}

// Stats
$todayUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE DATE(created_at) = CURDATE()");
$todayHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes WHERE DATE(created_at) = CURDATE()");
$totalUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji");
$totalHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes");
$totalAdmins = fetchCount($savienojums, "SELECT COUNT(*) FROM est_admin");
$activeHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes WHERE status = 'active'");
$ownersCount = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE loma = 'ipasnieks'");
$weekUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");

// Recent users
$recentUsers = [];
$ruRes = $savienojums->query("SELECT lietotajvards, epasts, loma, created_at FROM est_lietotaji ORDER BY created_at DESC LIMIT 5");
if ($ruRes) {
    while ($row = $ruRes->fetch_assoc()) {
        $recentUsers[] = $row;
    }
}

// Recent listings
$recentHomes = [];
$rhRes = $savienojums->query("SELECT id, title, city, type, status, price, created_at FROM est_homes ORDER BY created_at DESC LIMIT 5");
if ($rhRes) {
    while ($row = $rhRes->fetch_assoc()) {
        $recentHomes[] = $row;
    }
}

$dayNames = ['Svētdiena','Pirmdiena','Otrdiena','Trešdiena','Ceturtdiena','Piektdiena','Sestdiena'];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin panelis - HomeEstate</title>
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
            z-index: 100;
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
            transition: all 0.2s;
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
        .sidebar-user-role { font-size: 0.8rem; color: rgba(255,255,255,0.6); text-transform: capitalize; }
        
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
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            color: #1d2733;
        }
        .page-header h1 i { margin-right: 10px; color: #30b607; }
        .header-date {
            color: #6b7a8f;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7a8f;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            background: rgba(48,182,7,0.1);
            border-color: #30b607;
            color: #30b607;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            flex: 1;
            min-width: 160px;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-card.green::before { background: #30b607; }
        .stat-card.blue::before { background: #3498db; }
        .stat-card.orange::before { background: #f39c12; }
        .stat-card.purple::before { background: #9b59b6; }
        .stat-card.teal::before { background: #1abc9c; }
        .stat-card.red::before { background: #e74c3c; }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        .stat-card.green .stat-icon { background: rgba(48,182,7,0.1); color: #30b607; }
        .stat-card.blue .stat-icon { background: rgba(52,152,219,0.1); color: #3498db; }
        .stat-card.orange .stat-icon { background: rgba(243,156,18,0.1); color: #f39c12; }
        .stat-card.purple .stat-icon { background: rgba(155,89,182,0.1); color: #9b59b6; }
        .stat-card.teal .stat-icon { background: rgba(26,188,156,0.1); color: #1abc9c; }
        .stat-card.red .stat-icon { background: rgba(231,76,60,0.1); color: #e74c3c; }
        .stat-label {
            font-size: 0.8rem;
            color: #6b7a8f;
            margin-bottom: 4px;
            font-weight: 500;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1d2733;
        }
        .stat-sub {
            font-size: 0.75rem;
            color: #6b7a8f;
            margin-top: 6px;
        }
        
        /* Content Grid */
        .content-grid {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }
        .content-grid .panel {
            flex: 1;
        }
        
        /* Panels */
        .panel {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-header h3 {
            font-size: 1rem;
            color: #1d2733;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-header h3 i { color: #30b607; }
        .panel-link {
            color: #30b607;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .panel-link:hover { text-decoration: underline; }
        
        /* Tables */
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
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge.green { background: rgba(48,182,7,0.1); color: #30b607; }
        .badge.blue { background: rgba(52,152,219,0.1); color: #3498db; }
        .badge.orange { background: rgba(243,156,18,0.1); color: #f39c12; }
        .badge.gray { background: #edf2f7; color: #6b7a8f; }
        .price { font-weight: 600; color: #30b607; }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 12px;
            padding: 20px;
        }
        .quick-action {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px 16px;
            border-radius: 12px;
            background: #f8fafc;
            text-decoration: none;
            color: #1d2733;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .quick-action:hover {
            background: rgba(48,182,7,0.08);
            border-color: #30b607;
            transform: translateY(-2px);
        }
        .quick-action i {
            font-size: 1.6rem;
            color: #30b607;
        }
        .quick-action span {
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
        }
        
        @media (max-width: 1100px) {
            .content-grid { flex-direction: column; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 16px; }
            .stats-grid { flex-direction: column; }
            .quick-actions { flex-wrap: wrap; }
            .quick-action { min-width: calc(50% - 6px); }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">Home<span>Estate</span></div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo admin_route('dashboard'); ?>" class="active"><i class="fas fa-home"></i> Pārskats</a></li>
            <li><a href="<?php echo admin_route('users'); ?>"><i class="fas fa-users"></i> Lietotāji</a></li>
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
            <div>
                <h1><i class="fas fa-tachometer-alt"></i> Pārskats</h1>
                <div class="header-date">
                    <i class="far fa-calendar-alt"></i> 
                    <?php echo date('d.m.Y'); ?> — <?php echo $dayNames[date('w')]; ?>
                </div>
            </div>
            <div class="header-actions">
                <a href="<?php echo main_route('home'); ?>" class="btn-icon" title="Publiskā lapa"><i class="fas fa-globe"></i></a>
                <a href="<?php echo main_route('logout'); ?>" class="btn-icon" title="Iziet"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-label">Konti šodien</div>
                <div class="stat-value"><?php echo $todayUsers; ?></div>
                <div class="stat-sub">Kopā: <?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-building"></i></div>
                <div class="stat-label">Sludinājumi šodien</div>
                <div class="stat-value"><?php echo $todayHomes; ?></div>
                <div class="stat-sub">Kopā: <?php echo $totalHomes; ?></div>
            </div>
            <div class="stat-card teal">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Aktīvi sludinājumi</div>
                <div class="stat-value"><?php echo $activeHomes; ?></div>
                <div class="stat-sub">Publicēti</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-label">Īpašnieki</div>
                <div class="stat-value"><?php echo $ownersCount; ?></div>
                <div class="stat-sub">Ar lomu</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-label">Šonedēļ konti</div>
                <div class="stat-value"><?php echo $weekUsers; ?></div>
                <div class="stat-sub">Pēdējās 7 dienās</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                <div class="stat-label">Administratori</div>
                <div class="stat-value"><?php echo $totalAdmins; ?></div>
                <div class="stat-sub">Admin & Mod</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-users"></i> Jaunākie lietotāji</h3>
                    <a href="<?php echo admin_route('users'); ?>" class="panel-link">Skatīt visus →</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Vārds</th>
                                <th>E-pasts</th>
                                <th>Loma</th>
                                <th>Datums</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr><td colspan="4" style="text-align:center;color:#6b7a8f;padding:30px;">Nav datu</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $u): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($u['lietotajvards']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['epasts']); ?></td>
                                        <td>
                                            <?php 
                                                $roleBadge = 'gray';
                                                if ($u['loma'] === 'ipasnieks') $roleBadge = 'green';
                                            ?>
                                            <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($u['loma']); ?></span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-building"></i> Jaunākie sludinājumi</h3>
                    <a href="<?php echo admin_route('listings'); ?>" class="panel-link">Skatīt visus →</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nosaukums</th>
                                <th>Pilsēta</th>
                                <th>Cena</th>
                                <th>Statuss</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentHomes)): ?>
                                <tr><td colspan="4" style="text-align:center;color:#6b7a8f;padding:30px;">Nav datu</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentHomes as $h): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($h['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($h['city']); ?></td>
                                        <td class="price">€<?php echo number_format($h['price'], 0, ',', ' '); ?></td>
                                        <td>
                                            <span class="badge <?php echo $h['status'] === 'active' ? 'green' : 'gray'; ?>">
                                                <?php echo $h['status'] === 'active' ? 'Aktīvs' : ucfirst($h['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-bolt"></i> Ātrās darbības</h3>
            </div>
            <div class="quick-actions">
                <a href="<?php echo admin_route('users'); ?>" class="quick-action">
                    <i class="fas fa-users"></i>
                    <span>Pārvaldīt lietotājus</span>
                </a>
                <a href="<?php echo admin_route('listings'); ?>" class="quick-action">
                    <i class="fas fa-building"></i>
                    <span>Pārvaldīt sludinājumus</span>
                </a>
                <a href="<?php echo admin_route('register'); ?>" class="quick-action">
                    <i class="fas fa-user-plus"></i>
                    <span>Pievienot admin</span>
                </a>
                <a href="<?php echo main_route('home'); ?>" class="quick-action">
                    <i class="fas fa-globe"></i>
                    <span>Publiskā lapa</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>
