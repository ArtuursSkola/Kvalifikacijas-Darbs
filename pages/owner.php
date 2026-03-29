<?php
session_start();
require_once __DIR__ . '/../routes/main.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit();
}

$pageTitle = 'Kļūsti par īpašnieku - HomeEstate';
$extraStyles = ['owner'];
$bodyClass = 'owner-page';
include __DIR__ . '/../includes/header.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

$myHomes = [];
if ($isOwner) {
    $ownerId = (int)$_SESSION['user_id'];
    $sql = "SELECT id, title, city, status, price, type, main_image FROM est_homes WHERE owner_id = ? ORDER BY created_at DESC";
    $stmt = $savienojums->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $myHomes[] = $row;
        }
        $stmt->close();
    }
}
?><header class="owner-hero">
        <div class="owner-hero__content">
            <p class="badge-pill">Pay-to-List modelis</p>
            <h1>Publicē savus īpašumus. Saņem rezultātus.</h1>
            <p>Tu maksā par sludinājuma publicēšanu, nevis par darījumu. Mēs nopelnām, kad tava sludinājuma vieta ir aktīva.</p>
            <div class="owner-hero__bullets">
                <span><i class="fas fa-bullhorn"></i> Prioritāte meklēšanā</span>
                <span><i class="fas fa-id-card"></i> Verificēta īpašnieka badge</span>
                <span><i class="fas fa-camera"></i> Līdz 10+ fotogrāfijām</span>
            </div>
        </div>
    </header>

    <section class="owner-model">
        <div class="container">
            <div class="owner-model__grid">
                <div>
                    <h2>Kāpēc Pay-to-List?</h2>
                    <p>Saņem ieņēmumus uzreiz — pirms īrnieks vai pircējs pat piesaka vizīti. Mēs neiekasējam komisiju no taviem darījumiem; tu maksā tikai par redzamību.</p>
                    <ul class="owner-list">
                        <li><i class="fas fa-check"></i> Ieņēmumi pirms darījuma — tu kontrolē procesu.</li>
                        <li><i class="fas fa-check"></i> Redzamība meklētājos un tematiskajos blokos.</li>
                        <li><i class="fas fa-check"></i> Lietotāji redz tevi kā uzticamu īpašnieku.</li>
                    </ul>
                </div>
                <div class="owner-model__card">
                    <h3>Darbojas vienkārši:</h3>
                    <ol>
                        <li>Izvēlies plānu.</li>
                        <li>Apstiprini maksājumu ar Stripe.</li>
                        <li>Publicē un pārvaldi savus sludinājumus.</li>
                    </ol>
                    <p class="muted">Maksājums notiek caur Stripe drošo checkout.</p>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isOwner): ?>
    <section class="owner-listings">
        <div class="container">
            <div class="section-header-flex">
                <div>
                    <h2>Mani sludinājumi</h2>
                    <p>Pārvaldi savus aktīvos un melnraksta sludinājumus.</p>
                </div>
                <?php if ($canCreate): ?>
                    <a href="<?php echo main_route('property.create'); ?>" class="btn-owner-add"><i class="fas fa-plus"></i> Pievienot jaunu</a>
                <?php endif; ?>
            </div>

            <?php if (empty($myHomes)): ?>
                <div class="empty-owner-state">
                    <i class="fas fa-home"></i>
                    <p>Tev vēl nav neviena sludinājuma.</p>
                    <a href="<?php echo $canCreate ? main_route('property.create') : '#plans'; ?>" class="btn-owner">Sākt publicēšanu</a>
                </div>
            <?php else: ?>
                <div class="owner-grid">
                    <?php foreach ($myHomes as $home): 
                        $status = $home['status'] ?: 'draft';
                        $statusLabels = [
                            'active' => ['label' => 'Aktīvs', 'class' => 'status-active'],
                            'draft' => ['label' => 'Melnraksts', 'class' => 'status-draft'],
                            'rejected' => ['label' => 'Noraidīts', 'class' => 'status-rejected'],
                            'inactive' => ['label' => 'Neaktīvs', 'class' => 'status-inactive']
                        ];
                        $st = $statusLabels[$status] ?? ['label' => $status, 'class' => ''];
                        $img = $home['main_image'] ?: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=500&q=60';
                    ?>
                    <div class="owner-card">
                        <div class="owner-card__img">
                            <img src="<?php echo asset_path($img); ?>" alt="">
                            <span class="owner-status <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span>
                        </div>
                        <div class="owner-card__info">
                            <h4><?php echo htmlspecialchars($home['title']); ?></h4>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['city']); ?></p>
                            <div class="owner-card__footer">
                                <span class="price"><?php echo number_format($home['price'], 0, ',', ' '); ?> €</span>
                                <a href="<?php echo main_route('property.show', ['id' => $home['id']]); ?>" class="btn-sm-view">Skatīt</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="owner-plans" id="plans">
        <div class="container">
            <h2>Plāni īpašniekiem</h2>
            <p class="owner-plans__sub">Izvēlies sev ērtāko Pay-to-List plānu. Cenas bez slēptām komisijām.</p>
            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-head">
                        <span class="plan-name">Free</span>
                        <span class="plan-price">€0</span>
                        <span class="plan-period">1 aktīvs sludinājums</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 1 aktīvs sludinājums</li>
                        <li><i class="fas fa-check"></i> Līdz 3 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta publikācija</li>
                        <li class="muted"><i class="fas fa-minus"></i> Nav prioritātes meklēšanā</li>
                    </ul>
                    <a class="plan-btn ghost" href="#">Sākt bez maksas</a>
                </div>

                <div class="plan-card silver">
                    <div class="plan-head">
                        <span class="plan-badge">Visbiežāk izvēlas</span>
                        <span class="plan-name">Silver</span>
                        <span class="plan-price">€9.99</span>
                        <span class="plan-period">mēnesī · 5 sludinājumi</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 5 aktīvi sludinājumi</li>
                        <li><i class="fas fa-check"></i> Līdz 10 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta meklēšanas ranga līmenis</li>
                        <li><i class="fas fa-check"></i> Īpašnieka badge profilā</li>
                    </ul>
                    <a class="plan-btn" href="<?php echo main_route('payment.checkout', ['plan' => 'Silver', 'price' => 999]); ?>">Pirkt ar Stripe</a>
                </div>

                <div class="plan-card gold">
                    <div class="plan-head">
                        <span class="plan-name">Gold</span>
                        <span class="plan-price">€29.99</span>
                        <span class="plan-period">mēnesī · Bez limita</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Neierobežoti sludinājumi</li>
                        <li><i class="fas fa-check"></i> 20+ fotogrāfijas</li>
                        <li><i class="fas fa-check"></i> Featured meklēšanā (top pozīcijas)</li>
                        <li><i class="fas fa-check"></i> "Verified Owner" badge</li>
                    </ul>
                    <a class="plan-btn" href="<?php echo main_route('payment.checkout', ['plan' => 'Gold', 'price' => 2999]); ?>">Pirkt ar Stripe</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-strip">
        <div class="container">
            <div class="cta-strip__inner">
                <div>
                    <h3>Esi gatavs publicēt?</h3>
                    <p>Aktivizē plānu un pievieno sludinājumu minūtēs.</p>
                </div>
                <a class="btn-owner" href="#plans">Izvēlēties plānu</a>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
