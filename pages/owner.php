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

$flash = $_SESSION['owner_flash'] ?? null;
unset($_SESSION['owner_flash']);
?>

<header class="owner-hero">
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
                <p>Saņem ieņēmumus uzreiz pirms īrnieks vai pircējs piesaka vizīti. Mēs neiekasējam komisiju no taviem darījumiem; tu maksā tikai par redzamību.</p>
                <ul class="owner-list">
                    <li><i class="fas fa-check"></i> Ieņēmumi pirms darījuma, tu kontrolē procesu.</li>
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

<section class="owner-plans" id="plans">
    <div class="container">
        <h2>Plāni īpašniekiem</h2>
        <p class="owner-plans__sub">Izvēlies sev ērtāko Pay-to-List plānu. Cenas bez slēptām komisijām.</p>
        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="settings-flash settings-flash--<?php echo htmlspecialchars((string)($flash['type'] ?? 'info')); ?>" style="margin: 0 auto 20px; max-width: 900px;">
                <?php echo htmlspecialchars((string)$flash['message']); ?>
            </div>
        <?php endif; ?>
        <div class="plans-grid">
            <div class="plan-card">
                <div class="plan-head">
                    <span class="plan-name">Bezmaksas</span>
                    <span class="plan-price">€0</span>
                    <span class="plan-period">1 aktīvs sludinājums</span>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> 1 aktīvs sludinājums</li>
                    <li><i class="fas fa-check"></i> Līdz 3 fotogrāfijām</li>
                    <li><i class="fas fa-check"></i> Standarta publikācija</li>
                    <li class="muted"><i class="fas fa-minus"></i> Nav prioritātes meklēšanā</li>
                </ul>
                <form method="POST" action="<?php echo main_route('account.become_owner'); ?>">
                    <input type="hidden" name="plan" value="Bezmaksas">
                    <button type="submit" class="plan-btn ghost">Sākt bezmaksas</button>
                </form>
            </div>

            <div class="plan-card silver">
                <div class="plan-head">
                    <span class="plan-badge">Visbiežāk izvēlas</span>
                    <span class="plan-name">Sudraba</span>
                    <span class="plan-price">€9.99</span>
                    <span class="plan-period">mēnesī · 5 sludinājumi</span>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> 5 aktīvi sludinājumi</li>
                    <li><i class="fas fa-check"></i> Līdz 10 fotogrāfijām</li>
                    <li><i class="fas fa-check"></i> Standarta meklēšanas ranga līmenis</li>
                    <li><i class="fas fa-check"></i> Īpašnieka badge profilā</li>
                </ul>
                <a class="plan-btn" href="<?php echo main_route('payment.checkout', ['plan' => 'Sudraba', 'price' => 999]); ?>">Pirkt ar Stripe</a>
            </div>

            <div class="plan-card gold">
                <div class="plan-head">
                    <span class="plan-name">Zelta</span>
                    <span class="plan-price">€29.99</span>
                    <span class="plan-period">mēnesī · Bez limita</span>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> Neierobežoti sludinājumi</li>
                    <li><i class="fas fa-check"></i> 20+ fotogrāfijas</li>
                    <li><i class="fas fa-check"></i> Featured meklēšanā (top pozīcijas)</li>
                    <li><i class="fas fa-check"></i> Verified Owner badge</li>
                </ul>
                <a class="plan-btn" href="<?php echo main_route('payment.checkout', ['plan' => 'Zelta', 'price' => 2999]); ?>">Pirkt ar Stripe</a>
            </div>
        </div>
    </div>
</section>


<?php include __DIR__ . '/../includes/footer.php'; ?>
