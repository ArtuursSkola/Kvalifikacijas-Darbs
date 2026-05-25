<?php
require_once __DIR__ . '/account.php';
require_once __DIR__ . '/popup_system.php';
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
$canCreate = userHasActiveOwnerPlan($currentUser);
$profileBadge = $isOwner ? 'Īpašnieks' : 'Lietotājs';
$settingsUrl = main_route('account.settings_page');
$planHistory = $currentUser ? fetchUserPlanHistory($savienojums, (int)($currentUser['lietotaja_id'] ?? 0), $currentUser) : [];
$propertyHistory = $currentUser ? fetchUserPropertyTransactions($savienojums, (int)($currentUser['lietotaja_id'] ?? 0)) : [];

$renewPlanName = in_array($currentPlanLabel, ['Sudraba', 'Zelta'], true) ? $currentPlanLabel : null;
$renewPlanPrice = $renewPlanName === 'Zelta' ? 2999 : ($renewPlanName === 'Sudraba' ? 999 : null);
$myHomesUrl = main_route('property.myhomes');
?>

<nav class="navbar navbar--hero<?php echo $navbarClass !== '' ? ' ' . htmlspecialchars($navbarClass) : ''; ?>" id="navbar"
     data-fav-ids-api="<?php echo htmlspecialchars(main_route_absolute('api.favorites_ids'), ENT_QUOTES); ?>"
     data-fav-toggle-api="<?php echo htmlspecialchars(main_route_absolute('api.favorites_toggle'), ENT_QUOTES); ?>"
     data-fav-api="<?php echo htmlspecialchars(main_route_absolute('api.favorites'), ENT_QUOTES); ?>"
     data-login-url="<?php echo htmlspecialchars(main_route_absolute('login'), ENT_QUOTES); ?>"
     data-property-route="<?php echo htmlspecialchars(main_route_absolute('property.show'), ENT_QUOTES); ?>"
    data-logged-in="<?php echo $currentUser ? 'true' : 'false'; ?>"
>
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
        <?php if ($currentUser): ?>
            <li><a href="/pages/myapplications.php" class="<?php echo isActive('myapplications.php'); ?>">Mani pieteikumi</a></li>
        <?php endif; ?>

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
                <a href="<?php echo admin_route('dashboard'); ?>" class="btn-admin-panel" style="margin-right: 12px; background: #1d2733; color: #fff; border: none; font-size: 0.9rem; padding: 10px 16px;">Admin panelis</a>
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

                    <div class="profile-dropdown__nav-links-mobile">
                        <a class="profile-dropdown__link" href="<?php echo main_route('home'); ?>">
                            <i class="fas fa-home"></i>
                            Sākums
                        </a>
                        <a class="profile-dropdown__link" href="<?php echo main_route('property.list'); ?>">
                            <i class="fas fa-search"></i>
                            Meklēt īpašumu
                        </a>
                        <?php if ($isOwner): ?>
                            <a class="profile-dropdown__link" href="<?php echo main_route('owner'); ?>">
                                <i class="fas fa-crown"></i>
                                Kļūsti par īpašnieku
                            </a>
                        <?php endif; ?>
                        <?php if ($canCreate): ?>
                            <a class="profile-dropdown__link" href="<?php echo $myHomesUrl; ?>">
                                <i class="fas fa-list"></i>
                                Mani sludinājumi
                            </a>
                        <?php endif; ?>
                        <?php if ($canCreate): ?>
                            <a class="profile-dropdown__link" href="<?php echo main_route('property.create'); ?>">
                                <i class="fas fa-plus"></i>
                                Izveidot sludinājumu
                            </a>
                        <?php endif; ?>
                        <a class="profile-dropdown__link" href="<?php echo main_route('about'); ?>">
                            <i class="fas fa-info-circle"></i>
                            Par mums
                        </a>
                        <a class="profile-dropdown__link" href="/pages/myapplications.php">
                            <i class="fas fa-file-alt"></i>
                            Mani pieteikumi
                        </a>
                    </div>

                    <a class="profile-dropdown__link" href="#settings-modal">
                        <i class="fas fa-user-cog"></i>
                        Iestatījumi
                    </a>
                    <a class="profile-dropdown__link" href="#favorites-modal">
                        <i class="fas fa-heart"></i>
                        Favorīti
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
    <div class="settings-modal" id="settings-modal">
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
                        <input type="text" name="username" id="username_nav" value="<?php echo htmlspecialchars((string)($currentUser['lietotajvards'] ?? '')); ?>" required oninput="this.value=this.value.replace(/[\s0-9]/g,'')">

                        <label for="email_nav">E-pasts</label>
                        <input type="email" name="email" id="email_nav" value="<?php echo htmlspecialchars((string)($currentUser['epasts'] ?? '')); ?>" required>

                        <label for="telefons_nav">Telefona numurs</label>
                        <input type="tel" name="telefons" id="telefons_nav" pattern="[0-9]{8}" maxlength="8" value="<?php echo htmlspecialchars((string)($currentUser['telefons'] ?? '')); ?>">

                        <button type="submit" class="btn-submit">Saglabāt izmaiņas</button>
                    </form>
                </section>

                <section class="settings-panel">
                    <h3>Drošība</h3>
                    <?php 
                    $flash = $_SESSION['settings_flash'] ?? null;
                    if (is_array($flash) && !empty($flash['message']) && ($flash['section'] ?? '') === 'password'): 
                        unset($_SESSION['settings_flash']);
                    ?>
                        <div class="settings-flash settings-flash--<?php echo htmlspecialchars((string)($flash['type'] ?? 'info')); ?>" style="margin-bottom: 15px; border-radius: 12px; padding: 10px; background: rgba(231, 76, 60, 0.08); color: #b02618; border: 1px solid rgba(231, 76, 60, 0.2); font-weight: 600; font-size: 0.9rem;">
                            <?php echo htmlspecialchars((string)$flash['message']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo main_route('account.password'); ?>" class="settings-form">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? main_route('home')) . '#settings-modal'); ?>">

                        <label for="current_password_nav">Pašreizējā parole</label>
                        <input type="password" name="current_password" id="current_password_nav" autocomplete="current-password" required>

                        <label for="new_password_nav">Jaunā parole</label>
                        <input type="password" name="new_password" id="new_password_nav" autocomplete="new-password" required>

                        <label for="new_password_repeat_nav">Atkārtot jauno paroli</label>
                        <input type="password" name="new_password_repeat" id="new_password_repeat_nav" autocomplete="new-password" required>

                        <button type="submit" class="btn-submit">Mainīt paroli</button>
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
                                if ($currentPlanLabel === 'Nekads') {
                                    echo 'Nav plāna';
                                } elseif ($currentPlanLabel === 'Bezmaksas') {
                                    echo 'Nav aktīva plāna';
                                } elseif (!empty($currentUser['plana_beigas']) && isValidMysqlDateTime((string)$currentUser['plana_beigas'])) {
                                    echo htmlspecialchars(date('d.m.Y H:i', strtotime((string)$currentUser['plana_beigas'])));
                                } else {
                                    echo 'Nav plāna';
                                }
                                ?>
                            </strong>
                        </div>
                        <div class="settings-stat">
                            <span>Dienas līdz beigām</span>
                            <strong><?php echo $planDaysLeft !== null ? (int)$planDaysLeft . ' dienas' : ($currentPlanLabel === 'Bezmaksas' ? 'Bez termiņa' : 'Nav plāna'); ?></strong>
                        </div>
                    </div>

                    <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
                        <?php if ($renewPlanName && $renewPlanPrice !== null): ?>
                            <a class="btn-submit" href="<?php echo main_route('payment.checkout', ['plan' => $renewPlanName, 'price' => $renewPlanPrice]); ?>">Atjaunot plānu</a>
                        <?php endif; ?>

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
                                        <strong><?php echo htmlspecialchars((string)($purchase['plana_vards'] ?? 'Nekads')); ?></strong>
                                        <span><?php echo !empty($purchase['nopirkts_at']) ? htmlspecialchars(date('d.m.Y H:i', strtotime((string)$purchase['nopirkts_at']))) : 'Nav datu'; ?></span>
                                    </div>
                                    <div>
                                        <strong>
                                            <?php
                                            if (isset($purchase['maksa']) && $purchase['maksa'] !== null) {
                                                echo htmlspecialchars(number_format((float)$purchase['maksa'], 2, '.', ' ')) . ' ' . htmlspecialchars((string)($purchase['valuta'] ?? 'EUR'));
                                            } else {
                                                echo 'Nav summas';
                                            }
                                            ?>
                                        </strong>
                                        <span><?php echo !empty($purchase['beidzas_at']) ? 'Līdz ' . htmlspecialchars(date('d.m.Y', strtotime((string)$purchase['beidzas_at']))) : 'Bez termiņa'; ?></span>
                                    </div>
                                    <span class="settings-history__status"><?php
                                        $ms = (string)($purchase['maksajuma_statuss'] ?? 'succeeded');
                                        echo $ms === 'succeeded' ? 'Veiksmīgs' : htmlspecialchars($ms);
                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php else: ?>
                <section class="settings-panel">
                    <h3>Kļūt par īpašnieku</h3>
                    <p>Vai vēlaties kļūt par īpašnieku?</p>
                    <button type="button" class="btn-submit" onclick="showBecomeOwnerPopup()">Kļūt par īpašnieku</button>
                    <div style="margin-top: 12px;">

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

    <div class="settings-modal" id="favorites-modal">
        <a class="settings-modal__backdrop" href="#" aria-label="Aizvērt"></a>
        <div class="settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="favorites-modal-title">
            <a class="settings-modal__close" href="#" aria-label="Aizvērt">&times;</a>
            <div class="settings-modal__header">
                <h2 id="favorites-modal-title">Favorīti</h2>
                <p>Tavi saglabātie sludinājumi.</p>
            </div>
            <div id="favorites-empty" style="display:none; font-weight:700; color: rgba(44, 62, 80, 0.7); padding: 10px 2px;">Nav favorītu.</div>
            <div id="favorites-results" class="favorites-grid"></div>
        </div>
    </div>
<?php endif; ?>

<?php

if (isset($_SESSION['profile_update_success'])) {
    unset($_SESSION['profile_update_success']);
    showSuccessPopup('Izmaiņas ir saglabātas');
}
if (isset($_SESSION['password_change_success'])) {
    unset($_SESSION['password_change_success']);
    showSuccessPopup('Parole ir nomainīta');
}


if (isset($_SESSION['plan_change_success'])) {
    unset($_SESSION['plan_change_success']);
    showSuccessPopup($_SESSION['plan_change_message'] ?? 'Plāns veiksmīgi mainīts!');
    unset($_SESSION['plan_change_message']);
}


if (isset($_SESSION['settings_flash'])) {
    $flash = $_SESSION['settings_flash'];
    unset($_SESSION['settings_flash']);
    
    if ($flash['type'] === 'success') {
        showSuccessPopup($flash['message'] ?? 'Izmaiņas ir saglabātas');
    } elseif ($flash['type'] === 'error') {
        showPageAlert($flash['message'] ?? 'Kļūda. Notīkārtība.', 'error');
    }
}
?>

<script>
function showBecomeOwnerPopup() {

    if (confirm('Vai vēlaties kļūt īpašnieks? Tas dos jums iespējas iespējas sludinājumu publicēšanas iespējas.')) {

        fetch('<?php echo main_route("account.become_owner"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'plan=Bezmaksas'
        })
        .then(response => {
            if (response.ok) {
                showPageAlert('Jūs veiksmīgi kļūtāt īpašnieku!', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showPageAlert('Kļūda. Mēģinājiet vēlāk.', 'error');
            }
        })
        .catch(error => {
            showPageAlert('Kļūda. Mēģinājiet vēlāk.', 'error');
        });
    }
}
</script>
