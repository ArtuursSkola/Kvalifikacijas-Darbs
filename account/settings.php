<?php
session_start();

require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../includes/account.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . main_route('login'));
    exit;
}

$currentUser = loadCurrentUserContext($savienojums);
if (!$currentUser) {
    header('Location: ' . main_route('logout'));
    exit;
}

$pageTitle = 'Konta iestatījumi - HomeEstate';
$extraStyles = ['settings'];
$bodyClass = 'settings-page';
include __DIR__ . '/../includes/header.php';

$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);

$currentPlanLabel = getCurrentPlanLabel($currentUser);
$planDaysLeft = getPlanDaysLeft($currentUser);
$planHistory = fetchUserPlanHistory($savienojums, (int)$currentUser['lietotaja_id'], $currentUser);
$propertyHistory = fetchUserPropertyTransactions($savienojums, (int)$currentUser['lietotaja_id']);

$profileImageUrl = userProfileImageUrl($currentUser['profila_bilde'] ?? null);
$profileInitial = strtoupper(substr((string)($currentUser['lietotajvards'] ?? 'U'), 0, 1));

$renewPlanName = in_array($currentPlanLabel, ['Silver', 'Gold'], true) ? $currentPlanLabel : null;
$renewPlanPrice = $renewPlanName === 'Gold' ? 2999 : ($renewPlanName === 'Silver' ? 999 : null);
?>

<main class="settings-shell">
    <div class="settings-header">
        <h1>Konta iestatījumi</h1>
        <p>Pārvaldi profilu, drošību un savu plānu.</p>
    </div>

    <?php if (is_array($flash) && !empty($flash['message'])): ?>
        <div class="settings-flash settings-flash--<?php echo htmlspecialchars((string)($flash['type'] ?? 'info')); ?>">
            <?php echo htmlspecialchars((string)$flash['message']); ?>
        </div>
    <?php endif; ?>

    <section class="settings-card">
        <h2>Profils</h2>
        <form method="POST" action="<?php echo main_route('account.settings'); ?>" enctype="multipart/form-data" class="settings-form">
            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? main_route('account.settings_page')); ?>">

            <div class="settings-avatar-row">
                <div class="settings-avatar-preview">
                    <?php if ($profileImageUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profila bilde" class="settings-avatar-img">
                    <?php else: ?>
                        <span class="settings-avatar-fallback"><?php echo htmlspecialchars($profileInitial); ?></span>
                    <?php endif; ?>
                </div>

                <div class="settings-avatar-fields">
                    <label for="profile_picture">Profila bilde</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                </div>
            </div>

            <label for="username">Lietotājvārds</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars((string)($currentUser['lietotajvards'] ?? '')); ?>" required>

            <button type="submit" class="btn-register">Saglabāt izmaiņas</button>
        </form>
    </section>

    <section class="settings-card">
        <h2>Drošība</h2>
        <form method="POST" action="<?php echo main_route('account.password'); ?>" class="settings-form">
            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? main_route('account.settings_page')); ?>">

            <label for="current_password">Pašreizējā parole</label>
            <input type="password" name="current_password" id="current_password" autocomplete="current-password" required>

            <label for="new_password">Jaunā parole</label>
            <input type="password" name="new_password" id="new_password" autocomplete="new-password" required>

            <label for="new_password_repeat">Atkārtot jauno paroli</label>
            <input type="password" name="new_password_repeat" id="new_password_repeat" autocomplete="new-password" required>

            <button type="submit" class="btn-register">Mainīt paroli</button>
        </form>
    </section>

    <?php if ($currentUser['loma'] === 'ipasnieks'): ?>
    <section class="settings-card">
        <h2>Plāns</h2>
        <div class="settings-plan-grid">
            <div class="settings-plan-item">
                <span>Aktīvais plāns</span>
                <strong><?php echo htmlspecialchars($currentPlanLabel); ?></strong>
            </div>
            <div class="settings-plan-item">
                <span>Plāns beidzas</span>
                <strong>
                    <?php
                    if (!empty($currentUser['plan_expires_at']) && strtotime((string)$currentUser['plan_expires_at']) !== false) {
                        echo htmlspecialchars(date('d.m.Y H:i', strtotime((string)$currentUser['plan_expires_at'])));
                    } else {
                        echo 'Nav aktīva plāna';
                    }
                    ?>
                </strong>
            </div>
            <div class="settings-plan-item">
                <span>Dienas līdz beigām</span>
                <strong><?php echo $planDaysLeft !== null ? (int)$planDaysLeft . ' dienas' : 'Nav aktīva plāna'; ?></strong>
            </div>
        </div>

        <div class="settings-plan-actions">
            <?php if ($renewPlanName && $renewPlanPrice !== null): ?>
                <a class="btn-login" href="<?php echo main_route('payment.checkout', ['plan' => $renewPlanName, 'price' => $renewPlanPrice]); ?>">Atjaunot plānu</a>
            <?php else: ?>
                <a class="btn-login" href="<?php echo main_route('owner'); ?>#plans">Izvēlēties plānu</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="settings-card">
        <h2>Pirkumu vēsture (plāni)</h2>
        <?php if ($planHistory === []): ?>
            <p class="settings-empty">Pirkumu vēsture pagaidām nav pieejama.</p>
        <?php else: ?>
            <div class="settings-history">
                <?php foreach ($planHistory as $purchase): ?>
                    <div class="settings-history__row">
                        <div>
                            <strong><?php echo htmlspecialchars((string)($purchase['plan_name'] ?? 'Free')); ?></strong>
                            <div class="muted">
                                <?php echo !empty($purchase['purchased_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$purchase['purchased_at']))) : 'Nav datu'; ?>
                            </div>
                        </div>
                        <div class="settings-history__right">
                            <strong>
                                <?php
                                if (isset($purchase['amount_paid']) && $purchase['amount_paid'] !== null) {
                                    echo htmlspecialchars(number_format((float)$purchase['amount_paid'], 2, '.', ' ')) . ' ' . htmlspecialchars((string)($purchase['currency'] ?? 'EUR'));
                                } else {
                                    echo 'Nav summas';
                                }
                                ?>
                            </strong>
                            <div class="muted">
                                <?php echo !empty($purchase['expires_at']) ? 'Līdz ' . htmlspecialchars(date('d.m.Y', strtotime((string)$purchase['expires_at']))) : 'Bez termiņa'; ?>
                            </div>
                        </div>
                        <span class="settings-pill"><?php echo htmlspecialchars((string)($purchase['payment_status'] ?? 'succeeded')); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php else: ?>
    <section class="settings-card">
        <h2>Kļūt par īpašnieku</h2>
        <p>Vai vēlaties kļūt par īpašnieku?</p>
        <form method="POST" action="<?php echo main_route('account.become_owner'); ?>">
            <button type="submit" class="btn-register">Kļūt par īpašnieku</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="settings-card">
        <h2>Darījumu vēsture (īpašumi)</h2>
        <?php if ($propertyHistory === []): ?>
            <p class="settings-empty">Darījumu vēsture pagaidām nav pieejama.</p>
        <?php else: ?>
            <div class="settings-history">
                <?php foreach ($propertyHistory as $t): ?>
                    <div class="settings-history__row">
                        <div>
                            <strong>
                                <?php
                                $label = (string)($t['transaction_type'] ?? '');
                                if ($label === 'rent') $label = 'Īre';
                                if ($label === 'buy') $label = 'Pirkums';
                                echo htmlspecialchars($label !== '' ? $label : 'Darījums');
                                ?>
                            </strong>
                            <div class="muted">
                                <?php
                                $title = trim((string)($t['home_title'] ?? ''));
                                $city = trim((string)($t['home_city'] ?? ''));
                                echo htmlspecialchars($title !== '' ? $title : 'Īpašums');
                                echo $city !== '' ? ' · ' . htmlspecialchars($city) : '';
                                ?>
                            </div>
                        </div>
                        <div class="settings-history__right">
                            <strong>
                                <?php
                                if (isset($t['amount']) && $t['amount'] !== null) {
                                    echo htmlspecialchars(number_format((float)$t['amount'], 2, '.', ' ')) . ' ' . htmlspecialchars((string)($t['currency'] ?? 'EUR'));
                                } else {
                                    echo 'Nav summas';
                                }
                                ?>
                            </strong>
                            <div class="muted">
                                <?php echo !empty($t['created_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$t['created_at']))) : 'Nav datu'; ?>
                            </div>
                        </div>
                        <?php if (!empty($t['home_id'])): ?>
                            <a class="settings-link" href="<?php echo main_route('property.show', ['id' => (int)$t['home_id']]); ?>">Skatīt</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

