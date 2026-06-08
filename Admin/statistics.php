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

function fetchCountPrepared(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_row() : null;
    $stmt->close();
    return $row ? (int)$row[0] : 0;
}

function fetchAllPrepared(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return $rows;
}

function periodStartString(int $days): string
{
    $now = new DateTimeImmutable('now');
    return $now->sub(new DateInterval('P' . $days . 'D'))->format('Y-m-d H:i:s');
}

$periodDefs = [
    'week' => ['days' => 7, 'label' => 'Nedēļa', 'title' => 'Pēdējās 7 dienas'],
    'month' => ['days' => 30, 'label' => 'Mēnesis', 'title' => 'Pēdējās 30 dienas'],
    'year' => ['days' => 365, 'label' => 'Gads', 'title' => 'Pēdējās 365 dienas'],
];

$applicationsToday = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_pieteikumi WHERE DATE(created_at) = CURDATE()");
$helpToday = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_palidziba WHERE DATE(created_at) = CURDATE()");
$listingsToday = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE DATE(created_at) = CURDATE()");

$labelMap = [
    'ire' => 'Īre',
    'rent' => 'Īre',
    'pardod' => 'Pārdošana',
    'istermina_ire' => 'Īstermiņa īre'
];

$periodMetrics = [];
$periodAppTypes = [];
$periodShortTop = [];

foreach ($periodDefs as $key => $def) {
    $startStr = periodStartString((int)$def['days']);

    $periodMetrics[$key] = [
        'purchase' => fetchCountPrepared(
            $savienojums,
            "SELECT COUNT(*) FROM est_pieteikumi WHERE created_at >= ? AND statuss = 'Apstiprinats' AND sludinajuma_veids = 'pardod'",
            "s",
            [$startStr]
        ),
        'rent' => fetchCountPrepared(
            $savienojums,
            "SELECT COUNT(*) FROM est_pieteikumi WHERE created_at >= ? AND statuss = 'Apstiprinats' AND sludinajuma_veids IN ('ire','rent')",
            "s",
            [$startStr]
        ),
        'short_res' => fetchCountPrepared(
            $savienojums,
            "SELECT COUNT(*) FROM est_pieteikumi WHERE created_at >= ? AND statuss = 'Rezervets' AND sludinajuma_veids = 'istermina_ire'",
            "s",
            [$startStr]
        ),
        'applications' => fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_pieteikumi WHERE created_at >= ?", "s", [$startStr]),
        'help' => fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_palidziba WHERE created_at >= ?", "s", [$startStr]),
        'listings' => fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE created_at >= ?", "s", [$startStr]),
        'users' => fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_lietotaji WHERE created_at >= ?", "s", [$startStr]),
        'title' => (string)$def['title']
    ];

    $appRows = fetchAllPrepared(
        $savienojums,
        "SELECT sludinajuma_veids, COUNT(*) as cnt FROM est_pieteikumi WHERE created_at >= ? GROUP BY sludinajuma_veids ORDER BY cnt DESC",
        "s",
        [$startStr]
    );
    $appLabels = [];
    $appValues = [];
    foreach ($appRows as $row) {
        $k = (string)($row['sludinajuma_veids'] ?? '');
        $appLabels[] = $labelMap[$k] ?? ($k !== '' ? $k : '—');
        $appValues[] = (int)($row['cnt'] ?? 0);
    }
    $periodAppTypes[$key] = ['labels' => $appLabels, 'values' => $appValues, 'title' => (string)$def['title']];

    $shortRows = fetchAllPrepared(
        $savienojums,
        "SELECT 
            h.id,
            h.nosaukums,
            h.skatijumi,
            COUNT(p.id) as reservations_period
         FROM est_homes h
         LEFT JOIN est_pieteikumi p
            ON p.sludinajuma_id = h.id
           AND p.statuss = 'Rezervets'
           AND p.created_at >= ?
         WHERE h.veids = 'istermina_ire'
         GROUP BY h.id
         ORDER BY reservations_period DESC, h.skatijumi DESC
         LIMIT 10",
        "s",
        [$startStr]
    );
    $shortLabels = [];
    $shortValues = [];
    foreach (array_slice($shortRows, 0, 6) as $row) {
        $shortLabels[] = (string)($row['nosaukums'] ?? ('ID ' . (string)($row['id'] ?? '')));
        $shortValues[] = (int)($row['reservations_period'] ?? 0);
    }
    $periodShortTop[$key] = ['labels' => $shortLabels, 'values' => $shortValues, 'title' => (string)$def['title']];
}

$totalHomes = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes");
$activeHomes = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE statuss='Aktivs'");
$pendingHomes = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE statuss='Melnraksts' OR statuss='' OR statuss IS NULL");
$rejectedHomes = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE statuss='Noraidīts'");
$soldHomes = fetchCountPrepared($savienojums, "SELECT COUNT(*) FROM est_homes WHERE statuss='Pardots'");

$tableStartStr = periodStartString(30);
$popularShort = fetchAllPrepared(
    $savienojums,
    "SELECT 
        h.id,
        h.nosaukums,
        h.pilseta,
        h.cena,
        h.skatijumi,
        COUNT(p.id) as reservations_total,
        SUM(CASE WHEN p.created_at >= ? THEN 1 ELSE 0 END) as reservations_period
     FROM est_homes h
     LEFT JOIN est_pieteikumi p
        ON p.sludinajuma_id = h.id
       AND p.statuss = 'Rezervets'
     WHERE h.veids = 'istermina_ire'
     GROUP BY h.id
     ORDER BY reservations_total DESC, h.skatijumi DESC
     LIMIT 10",
    "s",
    [$tableStartStr]
);

$recentApplications = fetchAllPrepared(
    $savienojums,
    "SELECT p.id, p.sludinajuma_id, p.sludinajuma_veids, p.vards_uzvards, p.statuss, p.created_at, h.nosaukums, h.pilseta
     FROM est_pieteikumi p
     LEFT JOIN est_homes h ON h.id = p.sludinajuma_id
     ORDER BY p.created_at DESC
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistika - Admin</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1d2733;
            margin: 10px 0 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .charts-section-title i { color: #30b607; }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .chart-toggle-group {
            display: inline-flex;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
        }
        .chart-toggle {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .chart-toggle:hover { color: #1d2733; }
        .chart-toggle.active {
            background: #111827;
            color: #fff;
            box-shadow: 0 6px 16px rgba(17, 24, 39, 0.18);
        }
        .chart-wrap {
            position: relative;
            height: 320px;
        }
        .chart-wrap.sm {
            height: 280px;
        }
        .chart-note {
            color: #6b7a8f;
            font-size: .9rem;
            margin-top: 10px;
        }
        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
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
            <li><a href="<?php echo admin_route('listings'); ?>"><i class="fas fa-building"></i> Sludinājumi</a></li>
            <li><a href="<?php echo admin_route('palidziba'); ?>"><i class="fas fa-headset"></i> Palīdzības centrs</a></li>
            <li><a href="<?php echo admin_route('subscription_dashboard'); ?>"><i class="fas fa-shopping-cart"></i> Abonementi</a></li>
            <li><a href="<?php echo admin_route('statistics'); ?>" class="active"><i class="fas fa-chart-bar"></i> Statistika</a></li>
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
                <h1><i class="fas fa-chart-bar"></i> Statistika</h1>
                <div class="header-date"><i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?></div>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-icon admin-burger" aria-label="Atvērt navigāciju"><i class="fas fa-bars"></i></button>
                <a href="<?php echo main_route('home'); ?>" class="btn-icon" title="Publiskā lapa"><i class="fas fa-globe"></i></a>
                <a href="<?php echo main_route('logout'); ?>" class="btn-icon" title="Iziet"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <div class="charts-section-title"><i class="fas fa-chart-line"></i> Kopsavilkums</div>
        <div class="stats-row">
            <div class="stat-card blue">
                <i class="fas fa-file-signature"></i>
                <div>
                    <div class="val"><?php echo $applicationsToday; ?></div>
                    <div class="lbl">Pieteikumi šodien</div>
                </div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-headset"></i>
                <div>
                    <div class="val"><?php echo $helpToday; ?></div>
                    <div class="lbl">Palīdzība šodien</div>
                </div>
            </div>
            <div class="stat-card orange">
                <i class="fas fa-building"></i>
                <div>
                    <div class="val"><?php echo $listingsToday; ?></div>
                    <div class="lbl">Sludinājumi šodien</div>
                </div>
            </div>
            <div class="stat-card teal">
                <i class="fas fa-check-circle"></i>
                <div>
                    <div class="val"><?php echo $activeHomes; ?></div>
                    <div class="lbl">Aktīvi sludinājumi</div>
                </div>
            </div>
        </div>

        <div class="charts-section-title"><i class="fas fa-chart-bar"></i> Grafiki</div>
        <div class="charts-grid" style="margin-bottom:18px;">
            <div class="panel">
                <div class="panel-header chart-header">
                    <h3><i class="fas fa-chart-bar"></i> Darījumi un aktivitāte</h3>
                    <div class="chart-toggle-group" data-chart="periodBar">
                        <button type="button" class="chart-toggle active" data-period="week">Nedēļa</button>
                        <button type="button" class="chart-toggle" data-period="month">Mēnesis</button>
                        <button type="button" class="chart-toggle" data-period="year">Gads</button>
                    </div>
                </div>
                <div class="chart-wrap">
                    <canvas id="periodBar"></canvas>
                </div>
                <div class="chart-note" id="periodBarNote"><?php echo htmlspecialchars($periodDefs['week']['title']); ?></div>
            </div>
            <div class="panel">
                <div class="panel-header chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Sludinājumu statusi</h3>
                </div>
                <div class="chart-wrap">
                    <canvas id="statusPie"></canvas>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-star"></i> Populārākie īstermiņa sludinājumi</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Sludinājums</th>
                            <th>Pilsēta</th>
                            <th>Skatījumi</th>
                            <th>Rezervācijas (kopā)</th>
                            <th>Rezervācijas (periods)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popularShort)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#6b7a8f;padding:30px;">Nav datu</td></tr>
                        <?php else: ?>
                            <?php foreach ($popularShort as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars((string)($row['nosaukums'] ?? '—')); ?></strong></td>
                                    <td><?php echo htmlspecialchars((string)($row['pilseta'] ?? '—')); ?></td>
                                    <td><?php echo (int)($row['skatijumi'] ?? 0); ?></td>
                                    <td><?php echo (int)($row['reservations_total'] ?? 0); ?></td>
                                    <td><?php echo (int)($row['reservations_period'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-clock"></i> Jaunākie pieteikumi</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sludinājums</th>
                            <th>Tips</th>
                            <th>Statuss</th>
                            <th>Lietotājs</th>
                            <th>Datums</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentApplications)): ?>
                            <tr><td colspan="6" style="text-align:center;color:#6b7a8f;padding:30px;">Nav datu</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentApplications as $row): ?>
                                <tr>
                                    <td><?php echo (int)($row['id'] ?? 0); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)($row['nosaukums'] ?? '—')); ?></strong>
                                        <div style="color:#6b7a8f;font-size:.85rem;"><?php echo htmlspecialchars((string)($row['pilseta'] ?? '')); ?></div>
                                    </td>
                                    <td><span class="badge blue"><?php echo htmlspecialchars((string)($row['sludinajuma_veids'] ?? '—')); ?></span></td>
                                    <td><span class="badge gray"><?php echo htmlspecialchars((string)($row['statuss'] ?? '—')); ?></span></td>
                                    <td><?php echo htmlspecialchars((string)($row['vards_uzvards'] ?? '—')); ?></td>
                                    <td><?php echo !empty($row['created_at']) ? date('d.m.Y H:i', strtotime((string)$row['created_at'])) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="charts-section-title"><i class="fas fa-chart-pie"></i> Papildus</div>
        <div class="charts-grid" style="margin-bottom:18px;">
            <div class="panel">
                <div class="panel-header chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Pieteikumi pēc tipa</h3>
                    <div class="chart-toggle-group" data-chart="applicationsPie">
                        <button type="button" class="chart-toggle active" data-period="week">Nedēļa</button>
                        <button type="button" class="chart-toggle" data-period="month">Mēnesis</button>
                        <button type="button" class="chart-toggle" data-period="year">Gads</button>
                    </div>
                </div>
                <div class="chart-wrap sm">
                    <canvas id="applicationsPie"></canvas>
                </div>
                <div class="chart-note" id="applicationsPieNote"><?php echo htmlspecialchars($periodDefs['week']['title']); ?></div>
            </div>
            <div class="panel">
                <div class="panel-header chart-header">
                    <h3><i class="fas fa-star"></i> Top īstermiņa rezervācijas</h3>
                    <div class="chart-toggle-group" data-chart="shortTopBar">
                        <button type="button" class="chart-toggle active" data-period="week">Nedēļa</button>
                        <button type="button" class="chart-toggle" data-period="month">Mēnesis</button>
                        <button type="button" class="chart-toggle" data-period="year">Gads</button>
                    </div>
                </div>
                <div class="chart-wrap sm">
                    <canvas id="shortTopBar"></canvas>
                </div>
                <div class="chart-note" id="shortTopBarNote"><?php echo htmlspecialchars($periodDefs['week']['title']); ?></div>
            </div>
        </div>
        <script>
            (function () {
                Chart.defaults.font.family = 'Poppins, system-ui, -apple-system, Segoe UI, sans-serif';
                Chart.defaults.color = '#334155';

                const statusLabels = <?php echo json_encode(['Aktīvi', 'Gaida', 'Noraidīti', 'Pārdoti']); ?>;
                const statusValues = <?php echo json_encode([$activeHomes, $pendingHomes, $rejectedHomes, $soldHomes]); ?>;

                const periodTitles = <?php
                    $tmp = [];
                    foreach ($periodDefs as $k => $d) $tmp[$k] = $d['title'];
                    echo json_encode($tmp);
                ?>;

                const periodBarData = <?php
                    $tmp = [];
                    foreach ($periodMetrics as $k => $m) {
                        $tmp[$k] = [
                            (int)($m['purchase'] ?? 0),
                            (int)($m['rent'] ?? 0),
                            (int)($m['short_res'] ?? 0),
                            (int)($m['applications'] ?? 0),
                            (int)($m['help'] ?? 0),
                            (int)($m['listings'] ?? 0),
                            (int)($m['users'] ?? 0),
                        ];
                    }
                    echo json_encode($tmp);
                ?>;

                const appTypeSeries = <?php echo json_encode($periodAppTypes); ?>;
                const shortTopSeries = <?php echo json_encode($periodShortTop); ?>;

                const periodLabels = ['Pārdošana', 'Īre', 'Īstermiņa rezervācijas', 'Pieteikumi', 'Palīdzība', 'Sludinājumi', 'Jauni konti'];

                const toInt = (n) => Number.isFinite(Number(n)) ? Number(n) : 0;
                const setNote = (id, period) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = periodTitles[period] || '';
                };
                const setActive = (group, period) => {
                    group.querySelectorAll('.chart-toggle').forEach(b => b.classList.toggle('active', b.getAttribute('data-period') === period));
                };
                const percentTooltip = (ctx) => {
                    const data = ctx.dataset.data || [];
                    const total = data.reduce((a, b) => a + toInt(b), 0) || 1;
                    const val = toInt(ctx.raw);
                    const pct = Math.round((val / total) * 100);
                    return `${val} (${pct}%)`;
                };

                const barEl = document.getElementById('periodBar');
                let periodChart = null;
                if (barEl) {
                    const ctx = barEl.getContext('2d');
                    const grad = ctx.createLinearGradient(0, 0, 0, 320);
                    grad.addColorStop(0, 'rgba(37, 99, 235, 0.92)');
                    grad.addColorStop(1, 'rgba(37, 99, 235, 0.18)');
                    periodChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: periodLabels,
                            datasets: [{
                                label: 'Skaits',
                                data: periodBarData.week || [],
                                backgroundColor: grad,
                                borderColor: 'rgba(37, 99, 235, 0.95)',
                                borderWidth: 1,
                                borderRadius: 10,
                                maxBarThickness: 44
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { backgroundColor: 'rgba(17, 24, 39, 0.92)', padding: 12, displayColors: false }
                            },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.22)' } },
                                x: { ticks: { maxRotation: 0, autoSkip: false }, grid: { display: false } }
                            }
                        }
                    });
                }

                const pieEl = document.getElementById('statusPie');
                if (pieEl) {
                    new Chart(pieEl.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                data: statusValues,
                                backgroundColor: ['rgba(34, 197, 94, 0.92)', 'rgba(147, 51, 234, 0.85)', 'rgba(239, 68, 68, 0.9)', 'rgba(59, 130, 246, 0.9)'],
                                borderColor: 'rgba(255, 255, 255, 0.9)',
                                borderWidth: 2,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '55%',
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 16 } },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.92)',
                                    padding: 12,
                                    callbacks: { label: percentTooltip }
                                }
                            }
                        }
                    });
                }

                const appPieEl = document.getElementById('applicationsPie');
                let appTypeChart = null;
                if (appPieEl) {
                    appTypeChart = new Chart(appPieEl.getContext('2d'), {
                        type: 'pie',
                        data: {
                            labels: (appTypeSeries.week && appTypeSeries.week.labels) ? appTypeSeries.week.labels : [],
                            datasets: [{
                                data: (appTypeSeries.week && appTypeSeries.week.values) ? appTypeSeries.week.values : [],
                                backgroundColor: ['rgba(59, 130, 246, 0.9)', 'rgba(20, 184, 166, 0.88)', 'rgba(245, 158, 11, 0.88)', 'rgba(147, 51, 234, 0.85)', 'rgba(239, 68, 68, 0.86)', 'rgba(100, 116, 139, 0.85)'],
                                borderColor: 'rgba(255, 255, 255, 0.9)',
                                borderWidth: 2,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, padding: 16 } },
                                tooltip: {
                                    backgroundColor: 'rgba(17, 24, 39, 0.92)',
                                    padding: 12,
                                    callbacks: { label: percentTooltip }
                                }
                            }
                        }
                    });
                }

                const shortEl = document.getElementById('shortTopBar');
                let shortChart = null;
                if (shortEl) {
                    const ctx = shortEl.getContext('2d');
                    const grad = ctx.createLinearGradient(0, 0, 320, 0);
                    grad.addColorStop(0, 'rgba(245, 158, 11, 0.94)');
                    grad.addColorStop(1, 'rgba(245, 158, 11, 0.18)');
                    shortChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: (shortTopSeries.week && shortTopSeries.week.labels) ? shortTopSeries.week.labels : [],
                            datasets: [{
                                label: 'Rezervācijas (periodā)',
                                data: (shortTopSeries.week && shortTopSeries.week.values) ? shortTopSeries.week.values : [],
                                backgroundColor: grad,
                                borderColor: 'rgba(245, 158, 11, 0.95)',
                                borderWidth: 1,
                                borderRadius: 10,
                                maxBarThickness: 26
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { backgroundColor: 'rgba(17, 24, 39, 0.92)', padding: 12, displayColors: false }
                            },
                            scales: {
                                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148, 163, 184, 0.22)' } },
                                y: {
                                    ticks: {
                                        autoSkip: false,
                                        callback: function(value) {
                                            const label = this.getLabelForValue(value);
                                            return (label && label.length > 24) ? label.slice(0, 24) + '…' : label;
                                        }
                                    },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                const setChartPeriod = (chartId, period) => {
                    if (chartId === 'periodBar' && periodChart) {
                        periodChart.data.datasets[0].data = periodBarData[period] || [];
                        periodChart.update();
                        setNote('periodBarNote', period);
                        return;
                    }
                    if (chartId === 'applicationsPie' && appTypeChart) {
                        const s = appTypeSeries[period] || { labels: [], values: [] };
                        appTypeChart.data.labels = s.labels || [];
                        appTypeChart.data.datasets[0].data = s.values || [];
                        appTypeChart.update();
                        setNote('applicationsPieNote', period);
                        return;
                    }
                    if (chartId === 'shortTopBar' && shortChart) {
                        const s = shortTopSeries[period] || { labels: [], values: [] };
                        shortChart.data.labels = s.labels || [];
                        shortChart.data.datasets[0].data = s.values || [];
                        shortChart.update();
                        setNote('shortTopBarNote', period);
                        return;
                    }
                };

                document.querySelectorAll('.chart-toggle-group').forEach(group => {
                    group.addEventListener('click', (e) => {
                        const btn = e.target.closest('.chart-toggle');
                        if (!btn) return;
                        const chartId = group.getAttribute('data-chart');
                        const period = btn.getAttribute('data-period');
                        if (!chartId || !period) return;
                        setActive(group, period);
                        setChartPeriod(chartId, period);
                    });
                });
            })();
        </script>
    </main>
    <script src="../script.js"></script>
</body>
</html>
