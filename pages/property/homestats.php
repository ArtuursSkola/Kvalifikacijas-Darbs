<?php
session_start();

require_once __DIR__ . '/../../routes/main.php';
require_once dirname(__DIR__, 2) . '/con_db.php';
require_once dirname(__DIR__, 2) . '/includes/account.php';
require_once dirname(__DIR__, 2) . '/includes/mailer.php';
require_once dirname(__DIR__, 2) . '/includes/chat.php';

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit;
}

if (!userHasActiveOwnerPlan($currentUser)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

$ownerId = (int)($currentUser['lietotaja_id'] ?? $_SESSION['user_id'] ?? 0);
$infoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ownerId <= 0 || $infoId <= 0) {
    header('Location: ' . main_route('property.myhomes'));
    exit;
}
// SQL vaicājums īpašuma datu iegūšanai
$stmt = $savienojums->prepare("SELECT id, nosaukums, veids, statuss, skatijumi,
       favoriti, created_at FROM est_homes WHERE id = ? AND ipasnieka_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ii', $infoId, $ownerId);
    $stmt->execute();
    // Iegūst īpašuma informāciju
    $infoHome = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
// Ja īpašums neeksistē, novirza atpakaļ
if (!$infoHome) {
    header('Location: ' . main_route('property.myhomes'));
    exit;
}
// Apstrādā pieteikuma pieņemšanu vai noraidīšanu
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pieteikums_decide') {
    $pieteikumsId = isset($_POST['pieteikums_id']) ? (int)$_POST['pieteikums_id'] : 0;
    $decision = trim((string)($_POST['decision'] ?? ''));
    // Pārbauda vai darbība ir korekta
    if ($pieteikumsId > 0 && ($decision === 'accept' || $decision === 'reject')) {
        $applicantEpasts = '';
        $applicantVards = '';
        $listingNosaukums = '';
        $applicantUserId = 0;
        // SQL vaicājums pieteicēja datu iegūšanai
        $infoStmt = $savienojums->prepare(
            'SELECT p.epasts, p.vards_uzvards, p.lietotaja_id, h.nosaukums FROM est_pieteikumi p '
            . 'JOIN est_homes h ON h.id = p.sludinajuma_id WHERE p.id = ? AND p.sludinajuma_id = ? AND h.ipasnieka_id = ? LIMIT 1'
        );
        // Iegūst pieteikuma informāciju
        if ($infoStmt) {
            $infoStmt->bind_param('iii', $pieteikumsId, $infoId, $ownerId);
            $infoStmt->execute();
            $infoRow = $infoStmt->get_result()->fetch_assoc();
            $infoStmt->close();
            if ($infoRow) {
                $applicantEpasts = (string)($infoRow['epasts'] ?? '');
                $applicantVards = (string)($infoRow['vards_uzvards'] ?? '');
                $listingNosaukums = (string)($infoRow['nosaukums'] ?? '');
                $applicantUserId = (int)($infoRow['lietotaja_id'] ?? 0);
            }
        }

        $savienojums->begin_transaction();
        $ok = false;
        $mailAccepted = false;
        $mailStatusLabel = '';
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
                        if ($ok) {
                            $mailAccepted = false;
                            $mailStatusLabel = 'Noraidīts';
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
                        if ($ok) {
                            $mailAccepted = true;
                            $mailStatusLabel = $newStatus === 'Rezervets' ? 'Rezervēts' : 'Apstiprināts';
                        }
                    }
                }
            }
            if ($ok) {
                $savienojums->commit();
                if (mail_is_configured() && $applicantEpasts !== '') {
                    mail_notify_applicant_pieteikums_decision(
                        $applicantEpasts,
                        $applicantVards !== '' ? $applicantVards : 'Lietotājs',
                        $listingNosaukums !== '' ? $listingNosaukums : 'Sludinājums',
                        $mailAccepted,
                        (string)($currentUser['epasts'] ?? ''),
                        (string)($currentUser['telefons'] ?? '')
                    );
                }
                if ($applicantUserId > 0) {
                    if ($mailAccepted) {
                        chat_send_system_message(
                            $savienojums,
                            $applicantUserId,
                            "Jūsu pieteikums sludinājumam «" . ($listingNosaukums !== '' ? $listingNosaukums : 'Sludinājums') . "» ir apstiprināts.\n\n"
                            . "Īpašnieka kontakts:\n"
                            . "E-pasts: " . (string)($currentUser['epasts'] ?? '') . "\n"
                            . "Telefons: " . (string)($currentUser['telefons'] ?? '')
                        );
                    } else {
                        chat_send_system_message(
                            $savienojums,
                            $applicantUserId,
                            "Jūsu pieteikums sludinājumam «" . ($listingNosaukums !== '' ? $listingNosaukums : 'Sludinājums') . "» ir noraidīts."
                        );
                    }
                }
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
$pietStatusLabel = [
    'Jauns' => 'Jauns',
    'Apstiprinats' => 'Apstiprināts',
    'Rezervets' => 'Rezervēts',
    'Noraidits' => 'Noraidīts',
];
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

<header class="homestats-hero">
    <div class="homestats-hero__inner">
        <p class="badge-pill"><i class="fas fa-chart-bar"></i> Statistika</p>
        <h1><span><?php echo htmlspecialchars((string)($infoHome['nosaukums'] ?? '')); ?></span></h1>
        <p>Apskatiet sava īpašuma skatījumu statistiku un darbības analīzi</p>
    </div>
</header>

<div class="homestats-container">
    <div class="homestats-header">
        <div class="homestats-title-area">
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
                    <div class="app-card" id="pieteikums-<?php echo (int)$p['id']; ?>">
                        <div class="app-top">
                            <div class="app-name"><?php echo htmlspecialchars((string)($p['vards_uzvards'] ?? '')); ?></div>
                            <div class="app-status status-<?php echo htmlspecialchars((string)($p['statuss'] ?? '')); ?>">
                                <?php echo htmlspecialchars($pietStatusLabel[(string)($p['statuss'] ?? '')] ?? (string)($p['statuss'] ?? '')); ?>
                            </div>
                        </div>
                        
                        <div class="app-meta">
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string)($p['epasts'] ?? '')); ?></span>
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars((string)($p['telefons'] ?? '')); ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d.m.Y H:i', strtotime($p['created_at'])); ?></span>
                        </div>

                        <div class="app-details">
                            <?php if (!empty($p['sakuma_datums']) || !empty($p['beigu_datums'])): ?>
                                <strong>Periods:</strong> <?php echo htmlspecialchars((string)$p['sakuma_datums']); ?> - <?php echo htmlspecialchars((string)$p['beigu_datums']); ?><br>
                                <?php if (!empty($p['piedavata_summa'])): ?>
                                    <strong>Aprēķinātā kopsumma:</strong> <?php echo number_format((float)$p['piedavata_summa'], 0, ',', ' '); ?> €
                                <?php endif; ?>
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
