<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../includes/account.php';
require_once __DIR__ . '/../con_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit();
}

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('login'));
    exit();
}

$role = $currentUser['loma'] ?? '';
if ($role === 'lietotajs') {
    header('Location: ' . main_route('home'));
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
                    <button type="submit" class="plan-btn js-plan-select" data-plan="Bezmaksas">Sākt bezmaksas</button>
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
                <a class="plan-btn js-plan-select" data-plan="Sudraba" href="<?php echo main_route('payment.checkout', ['plan' => 'Sudraba', 'price' => 999]); ?>">Pirkt ar Stripe</a>
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
                <a class="plan-btn js-plan-select" data-plan="Zelta" href="<?php echo main_route('payment.checkout', ['plan' => 'Zelta', 'price' => 2999]); ?>">Pirkt ar Stripe</a>
            </div>
        </div>
    </div>
</section>

<style>
.plan-confirm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    font-family: 'Poppins', sans-serif;
}
.plan-confirm-btn--cancel {
    background-color: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.2);
}
.plan-confirm-btn--cancel:hover {
    background-color: #dc2626;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
}
.plan-confirm-btn--confirm {
    background-color: var(--accent);
    color: var(--white);
}
.plan-confirm-btn--confirm:hover {
    background-color: var(--accent-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(48, 182, 7, 0.35);
}
</style>

<div class="settings-modal" id="plan-confirm-modal" style="display: none;">
    <div class="settings-modal__backdrop" id="plan-confirm-backdrop"></div>
    <div class="settings-modal__dialog" style="max-width: 480px; margin: 150px auto 0;">
        <div class="settings-modal__header" style="margin-bottom: 20px;">
            <h2 id="plan-confirm-title">Apstiprinājums</h2>
            <p id="plan-confirm-message" style="margin-top: 10px; line-height: 1.6; font-size: 1rem; color: var(--gray-600);"></p>
        </div>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="plan-confirm-btn plan-confirm-btn--cancel" id="plan-confirm-btn-cancel"></button>
            <button type="button" class="plan-confirm-btn plan-confirm-btn--confirm" id="plan-confirm-btn-confirm"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var currentActivePlan = <?php echo json_encode(getCurrentPlanLabel($currentUser)); ?>;
    var planButtons = document.querySelectorAll('.js-plan-select');
    
    planButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var targetPlan = btn.getAttribute('data-plan');
            if (currentActivePlan === 'Sudraba' || currentActivePlan === 'Zelta') {
                e.preventDefault();
                handlePlanChange(currentActivePlan, targetPlan, function () {
                    if (btn.tagName.toLowerCase() === 'button') {
                        btn.closest('form').submit();
                    } else if (btn.tagName.toLowerCase() === 'a') {
                        window.location.href = btn.getAttribute('href');
                    }
                });
            }
        });
    });

    function showCustomConfirm(message, btnCancelText, btnConfirmText, onConfirm, onCancel) {
        var modal = document.getElementById('plan-confirm-modal');
        var msgEl = document.getElementById('plan-confirm-message');
        var btnCancel = document.getElementById('plan-confirm-btn-cancel');
        var btnConfirm = document.getElementById('plan-confirm-btn-confirm');
        var backdrop = document.getElementById('plan-confirm-backdrop');
        
        msgEl.textContent = message;
        btnCancel.textContent = btnCancelText;
        btnConfirm.textContent = btnConfirmText;
        
        modal.style.display = 'block';
        
        var newBtnCancel = btnCancel.cloneNode(true);
        var newBtnConfirm = btnConfirm.cloneNode(true);
        var newBackdrop = backdrop.cloneNode(true);
        
        btnCancel.parentNode.replaceChild(newBtnCancel, btnCancel);
        btnConfirm.parentNode.replaceChild(newBtnConfirm, btnConfirm);
        backdrop.parentNode.replaceChild(newBackdrop, backdrop);
        
        newBtnCancel.addEventListener('click', function () {
            modal.style.display = 'none';
            if (onCancel) onCancel();
        });
        
        newBtnConfirm.addEventListener('click', function () {
            modal.style.display = 'none';
            if (onConfirm) onConfirm();
        });
        
        newBackdrop.addEventListener('click', function () {
            modal.style.display = 'none';
            if (onCancel) onCancel();
        });
    }

    function handlePlanChange(activePlan, targetPlan, proceedCallback) {
        if (activePlan === targetPlan) {
            var msg = "Jums jau ir aktīvs " + activePlan + " plāns. Vai tiešām vēlaties iegādāties šo pašu plānu vēlreiz?";
            showCustomConfirm(msg, "Nē", "Jā", proceedCallback);
        } else {
            var warningMsg = "Jums jau ir aktīvs abonements (" + activePlan + " plāns). Ja nevēlaties maksāt par diviem plāniem, varat atcelt esošo abonementu un gaidīt līdz tā derīguma termiņa beigām. Vai vēlaties turpināt?";
            showCustomConfirm(warningMsg, "Atcelt", "Turpināt", function () {
                var confirmMsg = "";
                if (targetPlan === 'Bezmaksas') {
                    confirmMsg = "Vai tiešām vēlaties pāriet no " + activePlan + " plāna uz Bezmaksas plānu?";
                } else {
                    confirmMsg = "Vai tiešām vēlaties mainīt savu plānu no " + activePlan + " uz " + targetPlan + "?";
                }
                showCustomConfirm(confirmMsg, "Nē", "Jā", proceedCallback);
            });
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
