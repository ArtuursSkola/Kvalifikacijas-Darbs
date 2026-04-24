<?php
require_once __DIR__ . '/account.php';
require_once __DIR__ . '/../routes/main.php';
require_once __DIR__ . '/../routes/admin.php';

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
$profileBadge = $isOwner ? 'Īpašnieks' : 'Lietotājs';
$settingsUrl = main_route('account.settings_page');
$planHistory = $currentUser ? fetchUserPlanHistory($savienojums, (int)($currentUser['lietotaja_id'] ?? 0), $currentUser) : [];
$propertyHistory = $currentUser ? fetchUserPropertyTransactions($savienojums, (int)($currentUser['lietotaja_id'] ?? 0)) : [];

$renewPlanName = in_array($currentPlanLabel, ['Silver', 'Gold'], true) ? $currentPlanLabel : null;
$renewPlanPrice = $renewPlanName === 'Gold' ? 2999 : ($renewPlanName === 'Silver' ? 999 : null);
$myHomesUrl = main_route('property.myhomes');
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
            <li><a href="<?php echo $myHomesUrl; ?>" class="<?php echo isActive('myhomes.php'); ?>">Mani sludinājumi</a></li>
        <?php endif; ?>
        <?php if ($canCreate): ?>
            <li><a href="<?php echo main_route('property.create'); ?>" class="<?php echo isActive('newhome.php'); ?>">Izveidot sludinājumu</a></li>
        <?php endif; ?>
        <li><a href="<?php echo main_route('about'); ?>" class="<?php echo isActive('about.php'); ?>">Par mums</a></li>

        <li class="auth-buttons-mobile">
            <?php if ($currentUser): ?>
                <a href="<?php echo $settingsUrl; ?>" class="mobile-settings-link">Iestatījumi</a>
                <a href="<?php echo main_route('logout', ['i' => 1]); ?>" class="btn-register">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </li>
    </ul>

    <div class="auth-buttons">
        <?php if ($currentUser): ?>
            <?php if (in_array(($currentUser['loma'] ?? ''), ['admin', 'moderator'], true)): ?>
                <a href="<?php echo admin_route('dashboard'); ?>" class="btn-login" style="margin-right: 12px; background: #1d2733; color: #fff; border: none; font-size: 0.9rem; padding: 10px 16px;">Admin panelis</a>
            <?php endif; ?>
            <details class="profile-menu">
                <summary
                    class="profile-trigger"
                    aria-haspopup="true"
                    aria-label="Atvērt profila izvēlni"
                >
                    <?php if ($profileImageUrl !== ''): ?>
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="" class="profile-trigger__avatar">
                    <?php else: ?>
                        <i class="fas fa-user profile-trigger__icon" aria-hidden="true"></i>
                    <?php endif; ?>
                </summary>

                <div class="profile-dropdown" role="menu">
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
                        <?php if ($isOwner): ?>
                        <div class="profile-dropdown__plan">
                            <span>Plāns</span>
                            <strong><?php echo htmlspecialchars($currentPlanLabel); ?></strong>
                        </div>
                        <?php if ($planDaysLeft !== null): ?>
                            <div class="profile-dropdown__meta"><?php echo (int)$planDaysLeft; ?> dienas līdz termiņa beigām</div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <a class="profile-dropdown__link" href="#settings-modal">
                        <i class="fas fa-user-cog"></i>
                        Iestatījumi
                    </a>
                    <a href="<?php echo main_route('logout', ['i' => 1]); ?>" class="profile-dropdown__link">
                        <i class="fas fa-sign-out-alt"></i>
                        Iziet
                    </a>
                </div>
            </details>
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
    <div class="settings-modal" id="settings-modal" aria-hidden="true">
        <a class="settings-modal__backdrop" href="#" aria-label="Aizvērt"></a>
        <div class="settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="settings-modal-title">
            <a class="settings-modal__close" href="#" aria-label="Aizvērt">&times;</a>

            <div class="settings-modal__header">
                <h2 id="settings-modal-title">Konta iestatījumi</h2>
                <p>Maini profilu, paroli un pārskati savu plānu.</p>
            </div>

            <div class="settings-modal__grid">
                <section class="settings-panel">
                    <h3>Profils</h3>
                    <form method="POST" action="<?php echo main_route('account.settings'); ?>" enctype="multipart/form-data" class="settings-form">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? main_route('home')) . '#settings-modal'); ?>">

                        <div class="settings-avatar-row">
                            <div class="settings-avatar-preview">
                                <?php if ($profileImageUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profila bilde" class="settings-avatar-img">
                                <?php else: ?>
                                    <span class="settings-avatar-fallback"><?php echo htmlspecialchars($profileInitial); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="settings-avatar-fields">
                                <label for="profile_picture_nav">Profila bilde</label>
                                <input type="file" name="profile_picture" id="profile_picture_nav" accept="image/*">
                            </div>
                        </div>

                        <label for="username_nav">Lietotājvārds</label>
                        <input type="text" name="username" id="username_nav" value="<?php echo htmlspecialchars((string)($currentUser['lietotajvards'] ?? '')); ?>" required>

                        <button type="submit" class="btn-register">Saglabāt izmaiņas</button>
                    </form>
                </section>

                <section class="settings-panel">
                    <h3>Drošība</h3>
                    <form method="POST" action="<?php echo main_route('account.password'); ?>" class="settings-form">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? main_route('home')) . '#settings-modal'); ?>">

                        <label for="current_password_nav">Pašreizējā parole</label>
                        <input type="password" name="current_password" id="current_password_nav" autocomplete="current-password" required>

                        <label for="new_password_nav">Jaunā parole</label>
                        <input type="password" name="new_password" id="new_password_nav" autocomplete="new-password" required>

                        <label for="new_password_repeat_nav">Atkārtot jauno paroli</label>
                        <input type="password" name="new_password_repeat" id="new_password_repeat_nav" autocomplete="new-password" required>

                        <button type="submit" class="btn-register">Mainīt paroli</button>
                    </form>
                </section>

                <?php if ($isOwner): ?>
                <section class="settings-panel">
                    <h3>Plāns</h3>
                    <div class="settings-stat-list">
                        <div class="settings-stat">
                            <span>Aktīvais plāns</span>
                            <strong><?php echo htmlspecialchars($currentPlanLabel); ?></strong>
                        </div>
                        <div class="settings-stat">
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
                        <div class="settings-stat">
                            <span>Dienas līdz beigām</span>
                            <strong><?php echo $planDaysLeft !== null ? (int)$planDaysLeft . ' dienas' : 'Nav aktīva plāna'; ?></strong>
                        </div>
                    </div>

                    <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
                        <?php if ($renewPlanName && $renewPlanPrice !== null): ?>
                            <a class="btn-login" href="<?php echo main_route('payment.checkout', ['plan' => $renewPlanName, 'price' => $renewPlanPrice]); ?>">Atjaunot plānu</a>
                        <?php else: ?>
                            <a class="btn-login" href="<?php echo main_route('owner'); ?>#plans">Izvēlēties plānu</a>
                        <?php endif; ?>
                        <a class="btn-login" href="<?php echo $settingsUrl; ?>">Atvērt iestatījumus</a>
                    </div>
                </section>

                <section class="settings-panel settings-panel--wide">
                    <h3>Pirkumu vēsture (plāni)</h3>
                    <?php if ($planHistory === []): ?>
                        <p class="settings-history-empty">Pirkumu vēsture pagaidām nav pieejama.</p>
                    <?php else: ?>
                        <div class="settings-history">
                            <?php foreach ($planHistory as $purchase): ?>
                                <div class="settings-history__item">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string)($purchase['plan_name'] ?? 'Free')); ?></strong>
                                        <span><?php echo !empty($purchase['purchased_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$purchase['purchased_at']))) : 'Nav datu'; ?></span>
                                    </div>
                                    <div>
                                        <strong>
                                            <?php
                                            if (isset($purchase['amount_paid']) && $purchase['amount_paid'] !== null) {
                                                echo htmlspecialchars(number_format((float)$purchase['amount_paid'], 2, '.', ' ')) . ' ' . htmlspecialchars((string)($purchase['currency'] ?? 'EUR'));
                                            } else {
                                                echo 'Nav summas';
                                            }
                                            ?>
                                        </strong>
                                        <span><?php echo !empty($purchase['expires_at']) ? 'Līdz ' . htmlspecialchars(date('d.m.Y', strtotime((string)$purchase['expires_at']))) : 'Bez termiņa'; ?></span>
                                    </div>
                                    <span class="settings-history__status"><?php echo htmlspecialchars((string)($purchase['payment_status'] ?? 'succeeded')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php else: ?>
                <section class="settings-panel">
                    <h3>Kļūt par īpašnieku</h3>
                    <p>Vai vēlaties kļūt par īpašnieku?</p>
                    <form method="POST" action="<?php echo main_route('account.become_owner'); ?>">
                        <button type="submit" class="btn-register">Kļūt par īpašnieku</button>
                    </form>
                    <div style="margin-top: 12px;">
                        <a class="btn-login" href="<?php echo $settingsUrl; ?>">Atvērt iestatījumus</a>
                    </div>
                </section>
                <?php endif; ?>

                <section class="settings-panel settings-panel--wide">
                    <h3>Darījumu vēsture (īpašumi)</h3>
                    <?php if ($propertyHistory === []): ?>
                        <p class="settings-history-empty">Darījumu vēsture pagaidām nav pieejama.</p>
                    <?php else: ?>
                        <div class="settings-history">
                            <?php foreach ($propertyHistory as $t): ?>
                                <div class="settings-history__item">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string)($t['transaction_type'] ?? 'Darījums')); ?></strong>
                                        <span><?php echo !empty($t['created_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$t['created_at']))) : 'Nav datu'; ?></span>
                                    </div>
                                    <div>
                                        <strong>
                                            <?php
                                            if (isset($t['amount']) && $t['amount'] !== null) {
                                                echo htmlspecialchars(number_format((float)$t['amount'], 2, '.', ' ')) . ' ' . htmlspecialchars((string)($t['currency'] ?? 'EUR'));
                                            } else {
                                                echo 'Nav summas';
                                            }
                                            ?>
                                        </strong>
                                        <span>
                                            <?php
                                            $title = trim((string)($t['home_title'] ?? ''));
                                            $city = trim((string)($t['home_city'] ?? ''));
                                            echo htmlspecialchars($title !== '' ? $title : 'Īpašums');
                                            echo $city !== '' ? ' · ' . htmlspecialchars($city) : '';
                                            ?>
                                        </span>
                                    </div>
                                    <span class="settings-history__status"><?php echo htmlspecialchars((string)($t['transaction_type'] ?? '')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
<?php endif; ?>
