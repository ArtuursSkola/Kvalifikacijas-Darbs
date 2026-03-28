<?php
require_once __DIR__ . '/account.php';

if (!isset($savienojums) || !$savienojums instanceof mysqli) {
    require_once __DIR__ . '/../con_db.php';
}

if (!function_exists('isActive')) {
    function isActive($pageName) {
        $current = basename($_SERVER['PHP_SELF']);
        return ($current === $pageName) ? 'active' : '';
    }
}

$currentUser = $currentUser ?? loadCurrentUserContext($savienojums);
$navbarClass = trim((string)($navbarClass ?? ''));
$currentPlanLabel = getCurrentPlanLabel($currentUser);
$planDaysLeft = getPlanDaysLeft($currentUser);
$profileImageUrl = userProfileImageUrl($currentUser['profila_bilde'] ?? null);
$profileInitial = strtoupper(substr((string)($currentUser['lietotajvards'] ?? $currentUser['username'] ?? 'U'), 0, 1));
$isOwner = $currentUser ? (($currentUser['loma'] ?? '') === 'ipasnieks') : false;
$canCreate = userHasActivePaidPlan($currentUser);
$planHistory = $currentUser ? fetchUserPlanHistory($savienojums, (int)($currentUser['lietotaja_id'] ?? 0), $currentUser) : [];
$profileBadge = $isOwner ? 'Īpašnieks' : 'Lietotājs';
?>
<nav class="navbar<?php echo $navbarClass !== '' ? ' ' . htmlspecialchars($navbarClass) : ''; ?>" id="navbar">
    <div class="logo">Home<span>Estate</span></div>

    <ul class="nav-links">
        <li><a href="<?php echo main_route('home'); ?>" class="<?php echo isActive('index.php'); ?>">Sākums</a></li>
        <li><a href="<?php echo main_route('property.list'); ?>" class="<?php echo isActive('homes.php'); ?>">Meklēt īpašumu</a></li>
        <?php if ($isOwner): ?>
            <li><a href="<?php echo main_route('owner'); ?>" class="<?php echo isActive('owner.php'); ?>">Kļūsti par īpašnieku</a></li>
        <?php endif; ?>
        <?php if ($canCreate): ?>
            <li><a href="<?php echo main_route('property.create'); ?>" class="<?php echo isActive('newhome.php'); ?>">Izveidot sludinājumu</a></li>
        <?php endif; ?>
        <li><a href="<?php echo main_route('about'); ?>" class="<?php echo isActive('about.php'); ?>">Par mums</a></li>

        <li class="auth-buttons-mobile">
            <?php if ($currentUser): ?>
                <button type="button" class="mobile-settings-link" data-open-settings>Profila iestatījumi</button>
                <a href="<?php echo main_route('logout', ['i' => 1]); ?>" class="btn-register">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </li>
    </ul>

    <div class="auth-buttons">
        <?php if ($currentUser): ?>
            <div class="profile-menu">
                <button
                    type="button"
                    class="profile-trigger"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-label="Atvērt profila izvēlni"
                >
                    <i class="fas fa-user profile-trigger__icon" aria-hidden="true"></i>
                </button>

                <div class="profile-dropdown" hidden>
                    <div class="profile-dropdown__summary">
                        <div class="profile-dropdown__identity">
                            <div class="profile-dropdown__avatar">
                                <?php if ($profileImageUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="" class="profile-avatar-img">
                                <?php else: ?>
                                    <span class="profile-avatar-fallback"><?php echo htmlspecialchars($profileInitial); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($currentUser['lietotajvards'] ?? 'Lietotājs'); ?></strong>
                                <span class="profile-dropdown__badge"><?php echo htmlspecialchars($profileBadge); ?></span>
                            </div>
                        </div>
                        <div class="profile-dropdown__plan">
                            <span>Plāns</span>
                            <strong><?php echo htmlspecialchars($currentPlanLabel); ?></strong>
                        </div>
                        <?php if ($planDaysLeft !== null): ?>
                            <div class="profile-dropdown__meta"><?php echo $planDaysLeft; ?> dienas līdz termiņa beigām</div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="profile-dropdown__link" data-open-settings>
                        <i class="fas fa-user-cog"></i>
                        Iestatījumi
                    </button>
                    <a href="<?php echo main_route('logout', ['i' => 1]); ?>" class="profile-dropdown__link">
                        <i class="fas fa-sign-out-alt"></i>
                        Iziet
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
            <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
        <?php endif; ?>
    </div>

    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>
</nav>

<?php if ($currentUser): ?>
    <div class="settings-modal" id="settings-modal" hidden>
        <div class="settings-modal__backdrop" data-close-settings></div>
        <div class="settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
            <button type="button" class="settings-modal__close" data-close-settings aria-label="Aizvērt">&times;</button>

            <div class="settings-modal__header">
                <h2 id="settings-modal-title">Konta iestatījumi</h2>
                <p>Maini lietotājvārdu, profila bildi un pārskati savu plānu.</p>
            </div>

            <div class="settings-modal__grid">
                <section class="settings-panel">
                    <h3>Profils</h3>
                    <form method="POST" action="<?php echo main_route('account.settings'); ?>" enctype="multipart/form-data" class="settings-form">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? main_route('home')); ?>">

                        <div class="settings-avatar-row">
                            <div class="settings-avatar-preview">
                                <?php if ($profileImageUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profila bilde" id="settings-avatar-preview">
                                <?php else: ?>
                                    <span id="settings-avatar-preview" class="settings-avatar-fallback"><?php echo htmlspecialchars($profileInitial); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="settings-avatar-fields">
                                <label for="profile_picture">Profila bilde</label>
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                            </div>
                        </div>

                        <label for="username">Lietotājvārds</label>
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($currentUser['lietotajvards'] ?? ''); ?>" required>

                        <button type="submit" class="btn-register">Saglabāt izmaiņas</button>
                    </form>
                </section>

                <section class="settings-panel">
                    <h3>Konta statuss</h3>

                    <div class="settings-stat-list">
                        <div class="settings-stat">
                            <span>Aktīvais plāns</span>
                            <strong><?php echo htmlspecialchars($currentPlanLabel); ?></strong>
                        </div>

                        <?php if ($isOwner): ?>
                            <div class="settings-stat">
                                <span>Plāns aktivizēts</span>
                                <strong><?php echo !empty($currentUser['plan_activated_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime($currentUser['plan_activated_at']))) : 'Nav datu'; ?></strong>
                            </div>
                            <div class="settings-stat">
                                <span>Plāns beidzas</span>
                                <strong><?php echo !empty($currentUser['plan_expires_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime($currentUser['plan_expires_at']))) : 'Nav aktīva plāna'; ?></strong>
                            </div>
                            <div class="settings-stat">
                                <span>Dienas līdz beigām</span>
                                <strong><?php echo $planDaysLeft !== null ? $planDaysLeft . ' dienas' : 'Nav aktīva plāna'; ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="settings-panel settings-panel--wide">
                    <h3>Pirkumu vēsture</h3>

                    <?php if ($planHistory === []): ?>
                        <p class="settings-history-empty">Pirkumu vēsture pagaidām nav pieejama.</p>
                    <?php else: ?>
                        <div class="settings-history">
                            <?php foreach ($planHistory as $purchase): ?>
                                <div class="settings-history__item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($purchase['plan_name'] ?? 'Free'); ?></strong>
                                        <span><?php echo !empty($purchase['purchased_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime($purchase['purchased_at']))) : 'Nav datu'; ?></span>
                                    </div>
                                    <div>
                                        <strong>
                                            <?php
                                            if (isset($purchase['amount_paid']) && $purchase['amount_paid'] !== null) {
                                                echo htmlspecialchars(number_format((float)$purchase['amount_paid'], 2, '.', ' ')) . ' ' . htmlspecialchars($purchase['currency'] ?? 'EUR');
                                            } else {
                                                echo 'Nav summas';
                                            }
                                            ?>
                                        </strong>
                                        <span><?php echo !empty($purchase['expires_at']) ? 'Līdz ' . htmlspecialchars(date('d.m.Y', strtotime($purchase['expires_at']))) : 'Bez termiņa'; ?></span>
                                    </div>
                                    <span class="settings-history__status"><?php echo htmlspecialchars($purchase['payment_status'] ?? 'succeeded'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
<?php endif; ?>

