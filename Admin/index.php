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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_2fa') {
    $configFile = dirname(__DIR__) . '/config/2fa_status.json';
    $currentState = true;
    if (file_exists($configFile)) {
        $data = json_decode(file_get_contents($configFile), true);
        if (isset($data['enabled'])) {
            $currentState = (bool)$data['enabled'];
        }
    }
    file_put_contents($configFile, json_encode(['enabled' => !$currentState]));
    header('Location: ' . admin_route('dashboard'));
    exit;
}

$twoFaConfigFile = dirname(__DIR__) . '/config/2fa_status.json';
$twoFaEnabled = true;
if (file_exists($twoFaConfigFile)) {
    $data = json_decode(file_get_contents($twoFaConfigFile), true);
    if (isset($data['enabled'])) {
        $twoFaEnabled = (bool)$data['enabled'];
    }
}

function fetchCount(mysqli $conn, string $sql): int {
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_row()) {
        return (int)$row[0];
    }
    return 0;
}

// Statistika
$todayUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE DATE(created_at) = CURDATE()");
$todayHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes WHERE DATE(created_at) = CURDATE()");
$totalUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji");
$totalHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes");
$totalAdmins = fetchCount($savienojums, "SELECT COUNT(*) FROM est_admin");
$activeHomes = fetchCount($savienojums, "SELECT COUNT(*) FROM est_homes WHERE statuss = 'Aktivs'");
$ownersCount = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE loma = 'ipasnieks'");
$weekUsers = fetchCount($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");

// Jauni lietotaji
$recentUsers = [];
$ruRes = $savienojums->query("SELECT lietotajvards, epasts, loma, created_at FROM est_lietotaji ORDER BY created_at DESC LIMIT 5");
if ($ruRes) {
    while ($row = $ruRes->fetch_assoc()) {
        $recentUsers[] = $row;
    }
}

// Jaunāki sludinājumi
$recentHomes = [];
$rhRes = $savienojums->query("SELECT id, nosaukums, pilseta, veids, statuss, cena, created_at FROM est_homes ORDER BY created_at DESC LIMIT 5");
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
    <link rel="stylesheet" href="../css/admin.css">
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
            <li><a href="<?php echo admin_route('palidziba'); ?>"><i class="fas fa-headset"></i> Palīdzības centrs</a></li>
            <li><a href="<?php echo admin_route('subscription_dashboard'); ?>"><i class="fas fa-shopping-cart"></i> Abonementi</a></li>
            <li><a href="<?php echo admin_route('statistics'); ?>"><i class="fas fa-chart-bar"></i> Statistika</a></li>
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
                <button type="button" class="btn-icon admin-burger" aria-label="Atvērt navigāciju"><i class="fas fa-bars"></i></button>
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
                                        <td><strong><?php echo htmlspecialchars($h['nosaukums']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($h['pilseta']); ?></td>
                                        <td class="price">€<?php echo number_format($h['cena'], 0, ',', ' '); ?></td>
                                        <td>
                                            <?php
                                                $statusClass = ['Aktivs' => 'green', 'Melnraksts' => 'gray', 'Noraidīts' => 'red', 'Pardots' => 'blue'];
                                                $label = $h['statuss'];
                                                if ($h['statuss'] === 'Aktivs') $label = 'Aktīvs';
                                            ?>
                                            <span class="badge <?php echo $statusClass[$h['statuss']] ?? 'gray'; ?>">
                                                <?php echo htmlspecialchars($label); ?>
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
                <a href="<?php echo admin_route('moderators'); ?>" class="quick-action">
                    <i class="fas fa-user-plus"></i>
                    <span>Pievienot admin</span>
                </a>
                <a href="<?php echo main_route('home'); ?>" class="quick-action">
                    <i class="fas fa-globe"></i>
                    <span>Publiskā lapa</span>
                </a>
                <form method="POST" action="<?php echo admin_route('dashboard'); ?>" style="margin:0; padding:0; display:flex;">
                    <input type="hidden" name="action" value="toggle_2fa">
                    <button type="submit" class="quick-action" style="border:none; cursor:pointer; font:inherit; width:100%;">
                        <i class="fas <?php echo $twoFaEnabled ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="font-size: 24px; color: <?php echo $twoFaEnabled ? '#30b607' : '#e74c3c'; ?>"></i>
                        <span>2FA: <?php echo $twoFaEnabled ? 'Ieslēgts' : 'Izslēgts'; ?></span>
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script src="../script.js"></script>
</body>
</html>
