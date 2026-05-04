<?php
session_start();

require_once __DIR__ . '/../../routes/main.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit;
}

if (!userHasActivePaidPlan($currentUser)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

$ownerId = (int)($currentUser['lietotaja_id'] ?? $_SESSION['user_id'] ?? 0);
$infoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ownerId <= 0 || $infoId <= 0) {
    header('Location: ' . main_route('property.myhomes'));
    exit;
}

$stmt = $savienojums->prepare("SELECT id, nosaukums, veids, statuss, skatijumi, favoriti, created_at FROM est_homes WHERE id = ? AND ipasnieka_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ii', $infoId, $ownerId);
    $stmt->execute();
    $infoHome = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$infoHome) {
    header('Location: ' . main_route('property.myhomes'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pieteikums_decide') {
    $pieteikumsId = isset($_POST['pieteikums_id']) ? (int)$_POST['pieteikums_id'] : 0;
    $decision = trim((string)($_POST['decision'] ?? ''));
    if ($pieteikumsId > 0 && ($decision === 'accept' || $decision === 'reject')) {
        $savienojums->begin_transaction();
        $ok = false;
        try {
            $ps = $savienojums->prepare("SELECT p.id, h.veids FROM est_pieteikumi p JOIN est_homes h ON h.id = p.sludinajuma_id WHERE p.id = ? AND p.sludinajuma_id = ? AND h.ipasnieka_id = ? LIMIT 1");
            if ($ps) {
                $ps->bind_param('iii', $pieteikumsId, $infoId, $ownerId);
                $ps->execute();
                $row = $ps->get_result()->fetch_assoc();
                $ps->close();
                if ($row) {
                    $homeType = (string)($row['veids'] ?? '');
                    if ($decision === 'reject') {
                        $us = $savienojums->prepare("UPDATE est_pieteikumi SET statuss = 'Noraidits' WHERE id = ?");
                        if ($us) {
                            $us->bind_param('i', $pieteikumsId);
                            $ok = $us->execute();
                            $us->close();
                        }
                    } else {
                        $newStatus = $homeType === 'istermina_ire' ? 'Rezervets' : 'Apstiprinats';
                        $us = $savienojums->prepare("UPDATE est_pieteikumi SET statuss = ? WHERE id = ?");
                        if ($us) {
                            $us->bind_param('si', $newStatus, $pieteikumsId);
                            $ok = $us->execute();
                            $us->close();
                        }
                        if ($ok && $homeType !== 'istermina_ire') {
                            $hs = $savienojums->prepare("UPDATE est_homes SET statuss = 'Pardots' WHERE id = ? AND ipasnieka_id = ?");
                            if ($hs) {
                                $hs->bind_param('ii', $infoId, $ownerId);
                                $hs->execute();
                                $hs->close();
                            }
                        }
                    }
                }
            }
            if ($ok) {
                $savienojums->commit();
            } else {
                $savienojums->rollback();
            }
        } catch (Throwable $e) {
            $savienojums->rollback();
        }
    }
    header('Location: ' . main_route('property.stats', ['id' => $infoId]));
    exit;
}

$favDay = 0;
$favWeek = 0;
$favMonth = 0;
$stmt = $savienojums->prepare("SELECT COUNT(*) FROM est_favoriti WHERE home_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
if ($stmt) { $stmt->bind_param('i', $infoId); $stmt->execute(); $stmt->bind_result($favDay); $stmt->fetch(); $stmt->close(); }
$stmt = $savienojums->prepare("SELECT COUNT(*) FROM est_favoriti WHERE home_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($stmt) { $stmt->bind_param('i', $infoId); $stmt->execute(); $stmt->bind_result($favWeek); $stmt->fetch(); $stmt->close(); }
$stmt = $savienojums->prepare("SELECT COUNT(*) FROM est_favoriti WHERE home_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if ($stmt) { $stmt->bind_param('i', $infoId); $stmt->execute(); $stmt->bind_result($favMonth); $stmt->fetch(); $stmt->close(); }

$infoStats = [
    'skatijumi_kopa' => (int)($infoHome['skatijumi'] ?? 0),
    'favoriti_kopa' => (int)($infoHome['favoriti'] ?? 0),
];

$infoPieteikumi = [];
$pstmt = $savienojums->prepare("SELECT id, vards_uzvards, epasts, telefons, komentars, ires_menesi, nav_zinams, ires_sakuma_datums, sakuma_datums, beigu_datums, piedavata_summa, finansesanas_veids, statuss, created_at FROM est_pieteikumi WHERE sludinajuma_id = ? ORDER BY created_at DESC");
if ($pstmt) {
    $pstmt->bind_param('i', $infoId);
    $pstmt->execute();
    $res = $pstmt->get_result();
    while ($res && $row = $res->fetch_assoc()) {
        $infoPieteikumi[] = $row;
    }
    $pstmt->close();
}

$pageTitle = htmlspecialchars((string)($infoHome['nosaukums'] ?? '')) . ' - Statistika';
$extraStyles = ['owner', 'homestats'];
$bodyClass = 'owner-page homestats-page';
include __DIR__ . '/../../includes/header.php';
?>

<style>
.homestats-container {
    max-width: 1200px;
    margin: 120px auto 40px;
    padding: 0 20px;
    font-family: 'Inter', sans-serif;
}
.homestats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    padding: 20px 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    border: 1px solid rgba(255,255,255,0.4);
}
.homestats-title {
    margin: 0;
    font-size: 24px;
    color: #1a1a1a;
    font-weight: 700;
}
.homestats-title span {
    color: #30b607;
}
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #fff;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    border: 1px solid #e1e4e8;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.btn-back:hover {
    border-color: #30b607;
    color: #30b607;
    transform: translateY(-1px);
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.stat-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #fff;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.icon-views {
    background: linear-gradient(135deg, #30b607, #259106);
}
.icon-favs {
    background: linear-gradient(135deg, #ff4757, #e84118);
}
.stat-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #8898aa;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
}
.stat-info .stat-value {
    margin: 0;
    font-size: 36px;
    font-weight: 800;
    color: #1a1a1a;
    line-height: 1;
}
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
    display: inline-block;
    margin-left: 15px;
}
.status-Aktivs { background: #30b607; }
.status-Melnraksts { background: #475569; }
.status-Noraidīts { background: #e74c3c; }
.status-Pardots { background: #3498db; }

.applications-section {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    border: 1px solid #f0f2f5;
}
.section-title {
    margin: 0 0 25px 0;
    font-size: 20px;
    color: #1a1a1a;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
.app-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
}
.app-card:hover {
    border-color: #30b607;
    box-shadow: 0 4px 12px rgba(48, 182, 7, 0.08);
}
.app-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.app-name {
    font-weight: 600;
    color: #111827;
    font-size: 16px;
}
.app-status {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    background: #f3f4f6;
    color: #4b5563;
}
.status-Jauns { background: #fffbeb; color: #b45309; }
.status-Apstiprinats, .status-Rezervets { background: #f0fdf4; color: #166534; }
.status-Noraidits { background: #fef2f2; color: #991b1b; }
.app-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 12px;
}
.app-meta i {
    width: 16px;
    color: #9ca3af;
}
.app-details {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    color: #475569;
    margin-bottom: 15px;
}
.app-details strong {
    color: #1e293b;
}
.app-comment {
    font-size: 13px;
    color: #4b5563;
    font-style: italic;
    background: #f3f4f6;
    padding: 10px;
    border-radius: 8px;
    border-left: 3px solid #d1d5db;
    margin-bottom: 15px;
}
.app-actions {
    display: flex;
    gap: 10px;
}
.btn-action {
    flex: 1;
    padding: 8px 0;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}
.btn-accept {
    background: #30b607;
    color: white;
}
.btn-accept:hover { background: #269105; }
.btn-reject {
    background: #fff;
    color: #ef4444;
    border: 1px solid #fee2e2;
}
.btn-reject:hover {
    background: #fef2f2;
    border-color: #fca5a5;
}
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}
.empty-state i {
    font-size: 48px;
    color: #e5e7eb;
    margin-bottom: 15px;
}
</style>

<div class="homestats-container">
    <div class="homestats-header">
        <div style="display:flex; align-items:center;">
            <h1 class="homestats-title">Statistika: <span><?php echo htmlspecialchars((string)($infoHome['nosaukums'] ?? '')); ?></span></h1>
            <span class="status-badge status-<?php echo htmlspecialchars($infoHome['statuss']); ?>">
                <?php echo htmlspecialchars($infoHome['statuss']); ?>
            </span>
        </div>
        <a href="<?php echo main_route('property.myhomes'); ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Atpakaļ uz sarakstu</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-views">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-info">
                <h4>Kopējie skatījumi</h4>
                <div class="stat-value"><?php echo (int)$infoStats['skatijumi_kopa']; ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-favs">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-info">
                <h4>Pievienots favorītos</h4>
                <div class="stat-value"><?php echo (int)$infoStats['favoriti_kopa']; ?></div>
            </div>
        </div>
    </div>

    <div class="applications-section">
        <h2 class="section-title"><i class="fas fa-inbox" style="color: #30b607;"></i> Pieteikumi (<?php echo count($infoPieteikumi); ?>)</h2>
        
        <?php if (empty($infoPieteikumi)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope-open-text"></i>
                <p>Šim sludinājumam vēl nav saņemts neviens pieteikums.</p>
            </div>
        <?php else: ?>
            <div class="apps-grid">
                <?php foreach ($infoPieteikumi as $p): ?>
                    <div class="app-card">
                        <div class="app-top">
                            <div class="app-name"><?php echo htmlspecialchars((string)($p['vards_uzvards'] ?? '')); ?></div>
                            <div class="app-status status-<?php echo htmlspecialchars((string)($p['statuss'] ?? '')); ?>">
                                <?php echo htmlspecialchars((string)($p['statuss'] ?? '')); ?>
                            </div>
                        </div>
                        
                        <div class="app-meta">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string)($p['epasts'] ?? '')); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars((string)($p['telefons'] ?? '')); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($p['created_at'])); ?></span>
                        </div>

                        <div class="app-details">
                            <?php if (!empty($p['sakuma_datums']) || !empty($p['beigu_datums'])): ?>
                                <strong>Periods:</strong> <?php echo htmlspecialchars((string)$p['sakuma_datums']); ?> - <?php echo htmlspecialchars((string)$p['beigu_datums']); ?>
                            <?php elseif (!empty($p['ires_sakuma_datums'])): ?>
                                <strong>Sākums:</strong> <?php echo htmlspecialchars((string)$p['ires_sakuma_datums']); ?><br>
                                <strong>Ilgums:</strong> <?php if (!empty($p['ires_menesi'])): ?><?php echo (int)$p['ires_menesi']; ?> mēn.<?php endif; ?><?php if (!empty($p['nav_zinams'])): ?> (nav zināms)<?php endif; ?>
                            <?php elseif (!empty($p['piedavata_summa'])): ?>
                                <strong>Piedāvājums:</strong> <?php echo number_format((float)$p['piedavata_summa'], 0, ',', ' '); ?> €<br>
                                <?php if (!empty($p['finansesanas_veids'])): ?>
                                    <strong>Finansējums:</strong> <?php echo htmlspecialchars((string)$p['finansesanas_veids']); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($p['komentars'])): ?>
                            <div class="app-comment">
                                "<?php echo nl2br(htmlspecialchars((string)$p['komentars'])); ?>"
                            </div>
                        <?php endif; ?>

                        <?php if ((string)($p['statuss'] ?? '') === 'Jauns'): ?>
                            <div class="app-actions">
                                <form method="post" action="<?php echo main_route('property.stats', ['id' => $infoId]); ?>" style="flex:1; display:flex;">
                                    <input type="hidden" name="action" value="pieteikums_decide">
                                    <input type="hidden" name="pieteikums_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="decision" value="accept">
                                    <button type="submit" class="btn-action btn-accept">Pieņemt</button>
                                </form>
                                <form method="post" action="<?php echo main_route('property.stats', ['id' => $infoId]); ?>" style="flex:1; display:flex;">
                                    <input type="hidden" name="action" value="pieteikums_decide">
                                    <input type="hidden" name="pieteikums_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="decision" value="reject">
                                    <button type="submit" class="btn-action btn-reject">Noraidīt</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
