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

if (!userHasActiveOwnerPlan($currentUser)) {
    header('Location: ' . main_route('owner') . '#plans');
    exit;
}

$pageTitle = 'Mani sludinājumi - HomeEstate';
$extraStyles = ['owner', 'myhomes'];
$bodyClass = 'owner-page myhomes-page';
include __DIR__ . '/../../includes/header.php';

$flash = $_SESSION['owner_flash'] ?? null;
unset($_SESSION['owner_flash']);

if (isset($_SESSION['property_success'])) {
    $successType = $_SESSION['property_success'];
    unset($_SESSION['property_success']);
    if ($successType === 'create') {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showPageAlert('Sludinājums veiksmīgi izveidots', 'success'); });</script>";
    } elseif ($successType === 'edit') {
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showPageAlert('Sludinājums veiksmīgi rediģēts', 'success'); });</script>";
    }
}

$ownerId = (int)($currentUser['lietotaja_id'] ?? $_SESSION['user_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$_POST['csrf'])) {
        $_SESSION['owner_flash'] = ['type' => 'error', 'message' => 'CSRF marķiera validācija neizdevās!'];
        header('Location: ' . main_route('property.myhomes'));
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    $homeId = (int)($_POST['home_id'] ?? 0);
    if ($homeId > 0 && $ownerId > 0) {
        if ($action === 'delete_home') {
            $stmt = $savienojums->prepare("DELETE FROM est_homes WHERE id = ? AND ipasnieka_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $homeId, $ownerId);
                if ($stmt->execute()) {
                    $_SESSION['owner_flash'] = ['type' => 'success', 'message' => 'Sludinājums dzēsts.'];
                } else {
                    $_SESSION['owner_flash'] = ['type' => 'error', 'message' => 'Neizdevās dzēst sludinājumu.'];
                }
                $stmt->close();
            }
        }

        if ($action === 'deactivate_home') {
            $stmt = $savienojums->prepare("UPDATE est_homes SET statuss = 'Neaktivs' WHERE id = ? AND ipasnieka_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $homeId, $ownerId);
                if ($stmt->execute()) {
                    $_SESSION['owner_flash'] = ['type' => 'success', 'message' => 'Sludinājums deaktivizēts.'];
                } else {
                    $_SESSION['owner_flash'] = ['type' => 'error', 'message' => 'Neizdevās deaktivizēt sludinājumu.'];
                }
                $stmt->close();
            }
        }
    }

    header('Location: ' . main_route('property.myhomes'));
    exit;
}

$myHomes = [];
if ($ownerId > 0) {
    $sql = "SELECT id, nosaukums, pilseta, statuss, cena, veids, galvenais_attels, created_at
        FROM est_homes
        WHERE ipasnieka_id = ?
        ORDER BY created_at DESC";
    $stmt = $savienojums->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && $row = $res->fetch_assoc()) {
            $myHomes[] = $row;
        }
        $stmt->close();
    }
}
?>

<header class="myhomes-hero">
    <div class="myhomes-hero__inner">
        <p class="badge-pill"><i class="fas fa-folder-open"></i> Panelis</p>
        <h1>Mani <span>sludinājumi</span> (<?php echo count($myHomes); ?>)</h1>
        <p>Pārvaldi savus aktīvos sludinājumus un melnrakstus vienuviet.</p>

        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="settings-flash settings-flash--<?php echo htmlspecialchars((string)($flash['type'] ?? 'info')); ?>" style="margin-top: 14px; max-width: 720px;">
                <?php echo htmlspecialchars((string)$flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="myhomes-hero__actions">
            <a href="<?php echo main_route('property.create'); ?>" class="btn-owner-add">
                <i class="fas fa-plus"></i> Izveidot jaunu
            </a>
            <a href="<?php echo main_route('owner'); ?>" class="btn-owner-add myhomes-ghost">
                <i class="fas fa-crown"></i> Plāni
            </a>
        </div>
    </div>
</header>

<section class="owner-listings myhomes-listings">
    <div class="container">
        <div class="section-header-flex">
            <div>
                <h2>Sludinājumu saraksts</h2>
                <p>Jauns sludinājums tiks saglabāts kā melnraksts līdz apstiprināšanai.</p>
            </div>
        </div>

        <?php if (empty($myHomes)): ?>
            <div class="empty-owner-state">
                <i class="fas fa-home"></i>
                <p>Tev vēl nav neviena sludinājuma.</p>
                <a href="<?php echo main_route('property.create'); ?>" class="btn-owner-add"><i class="fas fa-plus"></i> Izveidot pirmo</a>
            </div>
        <?php else: ?>

            <div class="owner-grid">
                <?php foreach ($myHomes as $home): ?>
                    <?php
                    $status = $home['statuss'] ?: 'Melnraksts';
                    $statusLabels = [
                        'Aktivs' => ['label' => 'Aktīvs', 'class' => 'status-active'],
                        'Melnraksts' => ['label' => 'Melnraksts', 'class' => 'status-draft'],
                        'Noraidīts' => ['label' => 'Noraidīts', 'class' => 'status-rejected'],
                        'Pardots' => ['label' => 'Pārdots', 'class' => 'status-inactive'],
                        'Neaktivs' => ['label' => 'Neaktīvs', 'class' => 'status-inactive'],
                    ];
                    $st = $statusLabels[$status] ?? ['label' => (string)$status, 'class' => ''];
                    $fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80';
                    $img = trim((string)($home['galvenais_attels'] ?? ''));
                    if ($img === '') {
                        $img = $fallbackImg;
                    }
                    $type = (string)($home['veids'] ?? '');
                    $suffix = $type === 'ire' ? ' € / mēn' : ($type === 'istermina_ire' ? ' € / nakti' : ' €');
                    $price = isset($home['cena']) ? number_format((float)$home['cena'], 0, ',', ' ') . $suffix : '—';
                    ?>
                    <div class="owner-card">
                        <div class="owner-card__img">
                            <img
                                src="<?php echo htmlspecialchars(media_url($img)); ?>"
                                alt=""
                                loading="lazy"
                                onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($fallbackImg, ENT_QUOTES); ?>';"
                            >
                            <span class="owner-status <?php echo htmlspecialchars($st['class']); ?>"><?php echo htmlspecialchars($st['label']); ?></span>
                        </div>
                        <div class="owner-card__info">
                            <h4 title="<?php echo htmlspecialchars((string)($home['nosaukums'] ?? '')); ?>"><?php echo htmlspecialchars((string)($home['nosaukums'] ?? '')); ?></h4>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars((string)($home['pilseta'] ?? '')); ?></p>
                            <div class="owner-card__footer">
                                <span class="price"><?php echo htmlspecialchars($price); ?></span>
                                <div class="owner-card__actions">
                                    <a href="<?php echo main_route('property.show', ['id' => $home['id'], 'from' => 'my_listings']); ?>"
                                       class="btn-owner-action btn-owner-view"
                                       title="Skatīt">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo main_route('property.create', ['id' => (int)$home['id']]); ?>" class="btn-owner-action btn-owner-edit" title="Rediģēt"><i class="fas fa-edit"></i></a>
                                    <a href="<?php echo main_route('property.stats', ['id' => (int)$home['id']]); ?>" class="btn-owner-action btn-owner-info" title="Info"><i class="fas fa-info"></i></a>
                                    <a href="#listing-actions-modal"
                                       class="btn-owner-action btn-owner-danger js-listing-actions"
                                       data-home-id="<?php echo (int)$home['id']; ?>"
                                       data-home-title="<?php echo htmlspecialchars((string)($home['nosaukums'] ?? ''), ENT_QUOTES); ?>"
                                       title="Dzēst / deaktivizēt">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="settings-modal" id="listing-actions-modal">
    <div class="settings-modal__backdrop"></div>
    <div class="settings-modal__dialog" style="max-width: 560px;">
        <a class="settings-modal__close" href="#">×</a>
        <div class="settings-modal__header">
            <h2>Sludinājuma darbības</h2>
            <p id="listing-actions-title"></p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <form method="post" action="<?php echo main_route('property.myhomes'); ?>">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES); ?>">
                <input type="hidden" name="action" value="deactivate_home">
                <input type="hidden" name="home_id" id="listing-action-home-id-deactivate" value="">
                <button type="submit" class="btn-owner-action btn-owner-edit">Deaktivizēt</button>
            </form>
            <form method="post" action="<?php echo main_route('property.myhomes'); ?>">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES); ?>">
                <input type="hidden" name="action" value="delete_home">
                <input type="hidden" name="home_id" id="listing-action-home-id-delete" value="">
                <button type="submit" class="btn-owner-action btn-owner-danger">Dzēst</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var triggers = document.querySelectorAll('.js-listing-actions');
    var inputDeactivate = document.getElementById('listing-action-home-id-deactivate');
    var inputDelete = document.getElementById('listing-action-home-id-delete');
    var title = document.getElementById('listing-actions-title');

    triggers.forEach(function (el) {
        el.addEventListener('click', function () {
            var id = el.getAttribute('data-home-id') || '';
            var t = el.getAttribute('data-home-title') || '';
            if (inputDeactivate) inputDeactivate.value = id;
            if (inputDelete) inputDelete.value = id;
            if (title) title.textContent = t;
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
