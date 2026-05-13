<?php
session_start();
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../includes/account.php';

require_once __DIR__ . '/../routes/admin.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'moderator'], true)) {
    header('Location: ' . admin_route('login'));
    exit;
}


$revenueStmt = $savienojums->prepare("
    SELECT 
        DATE_FORMAT(nopirkts_at, '%Y-%m') as month,
        SUM(maksa) as monthly_revenue,
        COUNT(*) as transaction_count,
        plana_vards
    FROM est_plana_pirkums 
    WHERE maksajuma_statuss = 'succeeded'
    GROUP BY DATE_FORMAT(nopirkts_at, '%Y-%m'), plana_vards
    ORDER BY month DESC, plana_vards
");
$revenueStmt->execute();
$revenueData = $revenueStmt->get_result();


$activeStmt = $savienojums->prepare("
    SELECT 
        plans,
        COUNT(*) as active_count,
        SUM(CASE 
            WHEN plans = 'Sudraba' THEN 29.99
            WHEN plans = 'Zelta' THEN 49.99
            ELSE 0
        END) as monthly_recurring
    FROM est_lietotaji 
    WHERE plans IN ('Sudraba', 'Zelta') 
    AND plana_beigas > NOW()
    GROUP BY plans
");
$activeStmt->execute();
$activeData = $activeStmt->get_result();


$recentStmt = $savienojums->prepare("
    SELECT p.*, l.lietotajvards
    FROM est_plana_pirkums p
    LEFT JOIN est_lietotaji l ON p.user_id = l.lietotaja_id
    WHERE p.maksajuma_statuss = 'succeeded'
    ORDER BY p.nopirkts_at DESC
    LIMIT 20
");
$recentStmt->execute();
$recentData = $recentStmt->get_result();


$totalRevenue = 0;
$totalTransactions = 0;
$monthlyRevenue = [];

while ($row = $revenueData->fetch_assoc()) {
    $totalRevenue += $row['monthly_revenue'];
    $totalTransactions += $row['transaction_count'];
    
    if (!isset($monthlyRevenue[$row['month']])) {
        $monthlyRevenue[$row['month']] = 0;
    }
    $monthlyRevenue[$row['month']] += $row['monthly_revenue'];
}

$activeSubscriptions = [];
$totalMonthlyRecurring = 0;
while ($row = $activeData->fetch_assoc()) {
    $activeSubscriptions[] = $row;
    $totalMonthlyRecurring += $row['monthly_recurring'];
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonementi - Admin</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-controls {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .chart-toggle {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 8px 16px;
            margin: 0 5px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .chart-toggle:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }
        
        .chart-toggle.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .chart-container {
            position: relative;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-container canvas {
            max-height: 300px;
        }
        
        .chart-container.hidden {
            display: none;
        }
        
        .revenue-chart {
            display: none;
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
            <li><a href="<?php echo admin_route('subscription_dashboard'); ?>" class="active"><i class="fas fa-shopping-cart"></i> Abonementi</a></li>
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
            <h1><i class="fas fa-shopping-cart"></i> Abonementu panelis</h1>
            <div class="header-actions">
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
                <div><div class="val">€<?php echo number_format($totalRevenue, 2); ?></div><div class="lbl">Kopējie ieņēmumi</div></div>
            </div>
            
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div><div class="val"><?php echo count($activeSubscriptions); ?></div><div class="lbl">Aktīvie abonementi (iesp. €<?php echo number_format($totalMonthlyRecurring, 2); ?>/mēn)</div></div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                <div><div class="val"><?php echo $totalTransactions; ?></div><div class="lbl">Kopējie darījumi</div></div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-chart-line"></i> Ieņēmumi (Pēdējie 6 mēneši)</h3>
                </div>
                <div class="panel-body" style="padding: 20px;">
                    <div class="chart-container">
                        <canvas id="revenueCanvas" width="400" height="200"></canvas>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-clipboard-list"></i> Aktīvie abonementi pēc plāna</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Plāns</th>
                                <th>Aktīvie lietotāji</th>
                                <th>Ikmēneša ieņēmumi</th>
                                <th>Gada ieņēmumi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeSubscriptions as $sub): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo strtolower($sub['plans']) === 'zelta' ? 'orange' : 'gray'; ?>">
                                        <?php echo htmlspecialchars($sub['plans']); ?>
                                    </span>
                                </td>
                                <td><?php echo $sub['active_count']; ?></td>
                                <td>€<?php echo number_format($sub['monthly_recurring'], 2); ?></td>
                                <td>€<?php echo number_format($sub['monthly_recurring'] * 12, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-history"></i> Nesenie darījumi</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Lietotājs</th>
                                <th>Plāns</th>
                                <th>Summa</th>
                                <th>Datums</th>
                                <th>Transakcijas ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recentData->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lietotajvards']); ?></td>
                                <td>
                                    <span class="badge <?php echo strtolower($row['plana_vards']) === 'zelta' ? 'orange' : 'gray'; ?>">
                                        <?php echo htmlspecialchars($row['plana_vards']); ?>
                                    </span>
                                </td>
                                <td>€<?php echo number_format($row['maksa'], 2); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($row['nopirkts_at'])); ?></td>
                                <td><code><?php
                                    $mid = (string)($row['maksajuma_id'] ?? '');
                                    echo $mid !== '' ? htmlspecialchars(substr($mid, 0, 20)) . '…' : '—';
                                ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const monthlyRevenueData = <?php echo json_encode($monthlyRevenue); ?>;
        const months = Object.keys(monthlyRevenueData);
        const last6Months = months.slice(-6).reverse();
        const revenues = last6Months.map(month => monthlyRevenueData[month] || 0);
        
        function initChart() {
            const revenueCtx = document.getElementById('revenueCanvas').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: last6Months.map(month => {
                        const date = new Date(month + '-01');
                        return date.toLocaleDateString('lv-LV', { month: 'short' });
                    }),
                    datasets: [{
                        label: 'Ieņēmumi (EUR)',
                        data: revenues,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Ieņēmumi: €' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
        });
    </script>
</body>
</html>
