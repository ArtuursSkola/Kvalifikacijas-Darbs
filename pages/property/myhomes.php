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

$pageTitle = 'Mani sludinājumi - HomeEstate';
$extraStyles = ['owner', 'myhomes'];
$bodyClass = 'owner-page myhomes-page';
include __DIR__ . '/../../includes/header.php';

$ownerId = (int)($currentUser['lietotaja_id'] ?? $_SESSION['user_id'] ?? 0);
$myHomes = [];
if ($ownerId > 0) {
    $sql = "SELECT id, title, city, status, price, type, main_image, created_at
        FROM est_homes
        WHERE owner_id = ?
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
        <h1>Mani <span>sludinājumi</span></h1>
        <p>Pārvaldi savus aktīvos sludinājumus un melnrakstus vienuviet.</p>

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
                    $status = $home['status'] ?: 'draft';
                    $statusLabels = [
                        'active' => ['label' => 'Aktīvs', 'class' => 'status-active'],
                        'draft' => ['label' => 'Melnraksts', 'class' => 'status-draft'],
                        'rejected' => ['label' => 'Noraidīts', 'class' => 'status-rejected'],
                        'inactive' => ['label' => 'Neaktīvs', 'class' => 'status-inactive'],
                    ];
                    $st = $statusLabels[$status] ?? ['label' => (string)$status, 'class' => ''];
                    $img = trim((string)($home['main_image'] ?? ''));
                    if ($img === '') {
                        $img = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80';
                    }
                    $price = isset($home['price']) ? number_format((float)$home['price'], 0, ',', ' ') . ' €' : '—';
                    ?>
                    <div class="owner-card">
                        <div class="owner-card__img">
                            <img src="<?php echo htmlspecialchars(asset_path($img)); ?>" alt="">
                            <span class="owner-status <?php echo htmlspecialchars($st['class']); ?>"><?php echo htmlspecialchars($st['label']); ?></span>
                        </div>
                        <div class="owner-card__info">
                            <h4 title="<?php echo htmlspecialchars((string)($home['title'] ?? '')); ?>"><?php echo htmlspecialchars((string)($home['title'] ?? '')); ?></h4>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars((string)($home['city'] ?? '')); ?></p>
                            <div class="owner-card__footer">
                                <span class="price"><?php echo htmlspecialchars($price); ?></span>
                                <a href="<?php echo main_route('property.show', ['id' => (int)$home['id']]); ?>" class="btn-sm-view">Skatīt</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

