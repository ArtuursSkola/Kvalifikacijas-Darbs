<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';
require_once __DIR__ . '/../includes/popup_system.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) die('Nav atrasts con_db.php');
require $configPath;
require_once dirname(__DIR__) . '/includes/account.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'moderator'], true)) {
    header('Location: ' . admin_route('login'));
    exit;
}

$success = '';
$error   = '';

if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    $delId  = (int)$_GET['delete'];
    $selStmt = $savienojums->prepare("SELECT pievienotais_fails FROM est_palidziba WHERE id = ?");
    $selStmt->bind_param('i', $delId);
    $selStmt->execute();
    $selStmt->bind_result($filesStr);
    $selStmt->fetch();
    $selStmt->close();
    if ($filesStr) {
        foreach (explode(',', $filesStr) as $fp) {
            $fp = trim($fp);
            $full = dirname(__DIR__) . '/' . $fp;
            if ($fp !== '' && file_exists($full)) @unlink($full);
        }
    }
    $delStmt = $savienojums->prepare("DELETE FROM est_palidziba WHERE id = ?");
    $delStmt->bind_param('i', $delId);
    if ($delStmt->execute()) {
        $success = 'Ziņojums dzēsts.';
        showSuccessPopup('Ziņojums veiksmīgi dzēsts!');
    }
    else $error = 'Neizdevās dzēst.';
    $delStmt->close();
}

if (isset($_GET['buj_toggle']) && ctype_digit((string)$_GET['buj_toggle'])) {
    $bujId  = (int)$_GET['buj_toggle'];
    
    try {
        $checkStmt = $savienojums->prepare("SELECT id, radata_buj FROM est_palidziba WHERE id = ? AND statuss = 'Atbildēts'");
        if ($checkStmt === false) {
            throw new Exception("Failed to prepare check query: " . $savienojums->error);
        }
        $checkStmt->bind_param('i', $bujId);
        if (!$checkStmt->execute()) {
            throw new Exception("Failed to execute check query: " . $checkStmt->error);
        }
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentBuj = (int)($row['radata_buj'] ?? 0);
            $newBuj = $currentBuj ? 0 : 1;
            
            $checkStmt->close();
            
            $bStmt = $savienojums->prepare("UPDATE est_palidziba SET radata_buj = ? WHERE id = ?");
            if ($bStmt === false) {
                throw new Exception("Failed to prepare update query: " . $savienojums->error);
            }
            $bStmt->bind_param('ii', $newBuj, $bujId);
            if ($bStmt->execute()) {
                $success = 'BUJ statuss atjaunināts.';
                showSuccessPopup('BUJ statuss veiksmīgi atjaunināts!');
            } else {
                $error = 'Neizdevās atjaunināt BUJ statusu: ' . $bStmt->error;
            }
            $bStmt->close();
        } else {
            $checkStmt->close();
            $error = 'Ziņojums nav atrasts vai nav atbildēts.';
        }
    } catch (Exception $e) {
        $error = 'Sistēmas kļūda: ' . $e->getMessage();
        if (isset($checkStmt)) $checkStmt->close();
        if (isset($bStmt)) $bStmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $msgId   = (int)($_POST['message_id'] ?? 0);
    $atbilde = trim($_POST['atbilde'] ?? '');
    if ($msgId > 0 && $atbilde !== '') {
        $rStmt = $savienojums->prepare("UPDATE est_palidziba SET atbilde=?, statuss='Atbildēts' WHERE id=?");
        $rStmt->bind_param('si', $atbilde, $msgId);
        if ($rStmt->execute()) {
            $success = 'Atbilde saglabāta.';
            showSuccessPopup('Atbilde veiksmīgi saglabāta!');
        }
        else $error = 'Neizdevās saglabāt atbildi.';
        $rStmt->close();
    } else {
        $error = 'Lūdzu aizpildiet atbildes lauku.';
    }
}

$filterStatus = $_GET['status'] ?? '';
$allowedStatuses = ['', 'Iesūtīts', 'Atbildēts'];
if (!in_array($filterStatus, $allowedStatuses, true)) $filterStatus = '';

$whereClause = $filterStatus !== '' ? "WHERE p.statuss = ?" : "";

$sql = "SELECT p.*, l.lietotajvards, l.epasts, l.loma, l.profila_bilde
        FROM est_palidziba p
        LEFT JOIN est_lietotaji l ON p.lietotaja_id = l.lietotaja_id
        $whereClause
        ORDER BY p.created_at DESC";

$messages = [];
if ($filterStatus !== '') {
    $mStmt = $savienojums->prepare($sql);
    $mStmt->bind_param('s', $filterStatus);
    $mStmt->execute();
    $res = $mStmt->get_result();
} else {
    $res = $savienojums->query($sql);
}
while ($row = $res->fetch_assoc()) $messages[] = $row;
if (isset($mStmt)) $mStmt->close();

$totalCount   = count($messages);
$waitingCount = count(array_filter($messages, fn($m) => $m['statuss'] === 'Iesūtīts'));
$repliedCount = count(array_filter($messages, fn($m) => $m['statuss'] === 'Atbildēts'));
$bujCount     = count(array_filter($messages, fn($m) => (int)($m['radata_buj'] ?? 0) === 1));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palīdzības centrs - Admin</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/palidziba.css">
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
            <li><a href="<?php echo admin_route('palidziba'); ?>" class="active"><i class="fas fa-headset"></i> Palīdzības centrs</a></li>
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
            <h1><i class="fas fa-headset"></i> Palīdzības centrs</h1>
            <div class="header-actions">
                <a href="<?php echo main_route('faq'); ?>" class="btn-icon" title="Publiskā FAQ lapa" target="_blank"><i class="fas fa-external-link-alt"></i></a>
                <a href="<?php echo main_route('logout'); ?>" class="btn-icon" title="Iziet"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <i class="fas fa-inbox"></i>
                <div><div class="val"><?php echo $totalCount; ?></div><div class="lbl">Kopā</div></div>
            </div>
            <div class="stat-card orange">
                <i class="fas fa-clock"></i>
                <div><div class="val"><?php echo $waitingCount; ?></div><div class="lbl">Gaida atbildi</div></div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-check-circle"></i>
                <div><div class="val"><?php echo $repliedCount; ?></div><div class="lbl">Atbildēts</div></div>
            </div>
            <div class="stat-card blue">
                <i class="fas fa-star"></i>
                <div><div class="val"><?php echo $bujCount; ?></div><div class="lbl">Publicēts BUJ</div></div>
            </div>
        </div>

        <div class="panel" style="padding-bottom:0">
            <div class="panel-header">
                <h3>Visi ziņojumi (<?php echo $totalCount; ?>)</h3>
            </div>
            <div class="filters-bar">
                <a href="?" <?php echo $filterStatus === '' ? 'class="active"' : ''; ?>>Visi</a>
                <a href="?status=Iesūtīts" <?php echo $filterStatus === 'Iesūtīts' ? 'class="active"' : ''; ?>>Gaida atbildi</a>
                <a href="?status=Atbildēts" <?php echo $filterStatus === 'Atbildēts' ? 'class="active"' : ''; ?>>Atbildēts</a>
            </div>

            <?php if (empty($messages)): ?>
                <div class="msg-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:12px;"></i>Nav ziņojumu</div>
            <?php else: ?>
            <div class="msg-list">
                <?php foreach ($messages as $msg):
                    $username = $msg['lietotajvards'] ?? 'Nezināms';
                    $email    = $msg['epasts'] ?? '—';
                    $initial  = strtoupper(substr($username, 0, 1));
                    $date     = $msg['created_at'] ? date('d.m.Y H:i', strtotime($msg['created_at'])) : '—';
                    $isBuj    = (int)($msg['radata_buj'] ?? 0) === 1;
                    $isReplied= $msg['statuss'] === 'Atbildēts';
                    $images   = array_filter(array_map('trim', explode(',', $msg['pievienotais_fails'] ?? '')));
                    $statusClass = $isReplied ? 'badge-atbildets' : 'badge-iesutits';
                    $statusIcon  = $isReplied ? 'fa-check' : 'fa-clock';
                ?>
                <div class="msg-card">
                    <div class="msg-card__head">

                        <?php
                        $avatarUrl = userProfileImageUrl($msg['profila_bilde'] ?? '');
                        ?>

                        <div class="msg-avatar">
                            <?php if (!empty($avatarUrl)): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profila bilde">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initial); ?>
                            <?php endif; ?>
                        </div>
                        <div class="msg-user-info">
                            <strong><?php echo htmlspecialchars($username); ?></strong>
                            <span><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="msg-meta">
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $statusIcon; ?>"></i>
                                <?php echo htmlspecialchars($msg['statuss']); ?>
                            </span>
                            <?php if ($isBuj): ?>
                                <span class="status-badge badge-buj"><i class="fas fa-star"></i> BUJ</span>
                            <?php endif; ?>
                            <span class="msg-date"><i class="far fa-clock"></i> <?php echo $date; ?></span>
                        </div>
                    </div>
                    <div class="msg-card__body">
                        <div class="msg-label">Tēma: <?php echo htmlspecialchars($msg['tema']); ?></div>
                        <div class="msg-text"><?php echo htmlspecialchars($msg['jautajuma_apraksts']); ?></div>

                        <?php if (!empty($images)): ?>
                        <div class="msg-images">
                            <?php foreach ($images as $imgPath): ?>
                                <?php $imgUrl = '../' . ltrim($imgPath, '/'); ?>
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>" class="msg-img" alt="Attēls"
                                     onclick="openLightbox(this.src)">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($isReplied && $msg['atbilde'] !== null && $msg['atbilde'] !== ''): ?>
                        <div class="msg-reply-box">
                            <div class="msg-label"><i class="fas fa-reply"></i> Atbilde</div>
                            <div class="msg-reply-text"><?php echo htmlspecialchars($msg['atbilde']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="msg-card__foot">
                        <button class="btn-reply" onclick="openReplyModal(<?php echo $msg['id']; ?>, <?php echo htmlspecialchars(json_encode($msg['jautajuma_apraksts'])); ?>, <?php echo htmlspecialchars(json_encode($msg['atbilde'] ?? '')); ?>)">
                            <i class="fas fa-reply"></i>
                            <?php echo $isReplied ? 'Mainīt atbildi' : 'Atbildēt'; ?>
                        </button>
                        <?php if ($isReplied): ?>
                            <a href="?buj_toggle=<?php echo $msg['id']; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?>"
                               class="btn-buj <?php echo $isBuj ? 'active' : ''; ?>"
                               title="<?php echo $isBuj ? 'Noņemt no BUJ' : 'Pievienot BUJ'; ?>">
                                <i class="fas fa-star"></i>
                                <?php echo $isBuj ? 'Noņemt no BUJ' : 'Pievienot BUJ'; ?>
                            </a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $msg['id']; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?>"
                           class="btn-del"
                           onclick="return confirm('Vai tiešām dzēst šo ziņojumu?')">
                            <i class="fas fa-trash"></i> Dzēst
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <div class="reply-modal-overlay" id="replyOverlay">
        <div class="reply-modal">
            <div class="reply-modal__hd">
                <div>
                    <h3>Atbildēt uz ziņojumu</h3>
                    <p>Atbilde tiks saglabāta un statuss mainīts uz "Atbildēts"</p>
                </div>
                <button class="reply-modal__close" onclick="closeReplyModal()"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="reply-modal__body">
                    <input type="hidden" name="message_id" id="replyMsgId">
                    <label>Lietotāja jautājums</label>
                    <div class="reply-modal__q" id="replyQuestion"></div>
                    <label>Jūsu atbilde *</label>
                    <textarea name="atbilde" id="replyText" placeholder="Ierakstiet atbildi..." required></textarea>
                    <div class="reply-modal__foot">
                        <button type="button" class="reply-cancel" onclick="closeReplyModal()">Atcelt</button>
                        <button type="submit" name="reply_submit" class="reply-save"><i class="fas fa-paper-plane"></i> Saglabāt atbildi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="img-lightbox" id="imgLightbox" onclick="closeLightbox()">
        <span class="img-lightbox-close">&times;</span>
        <img src="" id="lightboxImg" alt="Attēls">
    </div>

    <script>
        function openReplyModal(id, question, existing) {
            document.getElementById('replyMsgId').value = id;
            document.getElementById('replyQuestion').textContent = question;
            document.getElementById('replyText').value = existing || '';
            document.getElementById('replyOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeReplyModal() {
            document.getElementById('replyOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }
        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('imgLightbox').classList.add('open');
        }
        function closeLightbox() {
            document.getElementById('imgLightbox').classList.remove('open');
        }
        document.getElementById('replyOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeReplyModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeReplyModal();
        });
    </script>
    </body>
</html>
