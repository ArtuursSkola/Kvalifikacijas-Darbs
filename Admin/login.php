<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
	die('Nav atrasts con_db.php');
}
require $configPath;

$errors = [];

$twoFaColumnExists = false;
$twoFaCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_admin LIKE '2fa_kods'");
if ($twoFaCheck && mysqli_num_rows($twoFaCheck) > 0) {
	$twoFaColumnExists = true;
}

$twoFaConfigFile = dirname(__DIR__) . '/config/2fa_status.json';
$twoFaGloballyEnabled = true;
if (file_exists($twoFaConfigFile)) {
	$data = json_decode(file_get_contents($twoFaConfigFile), true);
	if (isset($data['enabled'])) {
		$twoFaGloballyEnabled = (bool)$data['enabled'];
	}
}

$useEmail2fa = $twoFaGloballyEnabled && $twoFaColumnExists;

$mail2faHint = '';
if (!$useEmail2fa && $twoFaColumnExists) {
	if (!mail_phpmailer_available()) {
		$mail2faHint = 'E-pasta 2FA nav pieejams: trūkst PHPMailer. Palaidiet composer install VAI pārbaudiet lib/PHPMailer/src.';
	} elseif (mail_load_config() === null) {
		$mail2faHint = 'Nav config/mail.php.';
	} else {
		$mail2faHint = 'SMTP nav pilnībā konfigurēts: izveidojiet config/mail.local.php (skat. mail.local.example.php) vai iestatiet MAILTRAP_PASSWORD.';
	}
}

function admin_login_clear_pending_2fa(): void
{
	unset($_SESSION['pending_2fa_admin_id'], $_SESSION['pending_2fa_admin_username']);
}

/** @param array<string, mixed> $user */
function admin_login_complete_session(array $user): void
{
	$_SESSION['user_id'] = (int)$user['admin_id'];
	$_SESSION['username'] = (string)$user['lietotajvards'];
	$_SESSION['role'] = (string)$user['loma'];
	$_SESSION['user_type'] = 'admin';
}

if (isset($_POST['login_2fa_btn'])) {
	$code = preg_replace('/\D/', '', (string)($_POST['twofa_code'] ?? ''));
	$pendingId = (int)($_SESSION['pending_2fa_admin_id'] ?? 0);
	if ($pendingId <= 0 || strlen($code) !== 6) {
		$errors[] = 'Nederīgs vai trūkstošs apstiprinājuma kods.';
	} else {
		$stmt = $savienojums->prepare("SELECT admin_id, lietotajvards, parole, loma, epasts, `2fa_kods` FROM est_admin WHERE admin_id = ? AND loma IN ('admin','moderator') LIMIT 1");
		if ($stmt) {
			$stmt->bind_param('i', $pendingId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			$stored = isset($user['2fa_kods']) ? (string)$user['2fa_kods'] : '';
			if ($user && hash_equals($stored, $code)) {
				$clr = $savienojums->prepare('UPDATE est_admin SET `2fa_kods` = NULL WHERE admin_id = ?');
				if ($clr) {
					$clr->bind_param('i', $pendingId);
					$clr->execute();
					$clr->close();
				}
				admin_login_clear_pending_2fa();
				admin_login_complete_session($user);
				header('Location: ' . admin_route('dashboard'));
				exit;
			}
		}
		$errors[] = 'Nepareizs apstiprinājuma kods.';
	}
}

if (isset($_POST['login_btn'])) {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($username === '' || $password === '') {
		$errors[] = 'Ievadi lietotājvārdu un paroli.';
	} else {
		$stmt = $savienojums->prepare("SELECT admin_id, lietotajvards, parole, loma, epasts FROM est_admin WHERE lietotajvards=? AND loma IN ('admin','moderator') LIMIT 1");
		if ($stmt) {
			$stmt->bind_param('s', $username);
			$stmt->execute();
			$result = $stmt->get_result();
			$user = $result ? $result->fetch_assoc() : null;
			$stmt->close();

			if ($user && password_verify($password, (string)($user['parole'] ?? ''))) {
				if ($useEmail2fa) {
					$ep = trim((string)($user['epasts'] ?? ''));
					if (!filter_var($ep, FILTER_VALIDATE_EMAIL)) {
						admin_login_complete_session($user);
						header('Location: ' . admin_route('dashboard'));
						exit;
					}
					$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
					$aid = (int)$user['admin_id'];
					$upd = $savienojums->prepare('UPDATE est_admin SET `2fa_kods` = ? WHERE admin_id = ?');
					if ($upd) {
						$upd->bind_param('si', $code, $aid);
						$upd->execute();
						$upd->close();
					}
					$sent = mail_send_login_2fa_code($ep, (string)($user['lietotajvards'] ?? ''), $code, true);
					if (!$sent) {
						$clr = $savienojums->prepare('UPDATE est_admin SET `2fa_kods` = NULL WHERE admin_id = ?');
						if ($clr) {
							$clr->bind_param('i', $aid);
							$clr->execute();
							$clr->close();
						}
						$errors[] = 'Neizdevās nosūtīt apstiprinājuma e-pastu. Pārbaudiet SMTP (Mailtrap) un PHP error_log.';
					} else {
						$_SESSION['pending_2fa_admin_id'] = $aid;
						header('Location: ' . admin_route('login', ['twofa' => '1']));
						exit;
					}
				} else {
					admin_login_complete_session($user);
					header('Location: ' . admin_route('dashboard'));
					exit;
				}
			} else {
				$errors[] = 'Nepareizs lietotājvārds vai parole (admin/moderator).';
			}
		} else {
			$errors[] = 'Neizdevās sagatavot pieprasījumu.';
		}
	}
}

$show2faForm = $useEmail2fa && isset($_GET['twofa']) && (string)$_GET['twofa'] === '1' && !empty($_SESSION['pending_2fa_admin_id']);

if (!$useEmail2fa && isset($_GET['twofa'])) {
	admin_login_clear_pending_2fa();
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin/Moderatora pieslēgšanās</title>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="../style.css">
</head>
<body class="auth-page">
<nav class="navbar">
    <div class="logo">Home<span>Estate</span></div>
    <ul class="nav-links">
        <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
    </ul>
</nav>
	<div class="auth-wrapper">
		<div class="auth-card">
			<h2><?php echo $show2faForm ? 'Apstipriniet e-pastu' : 'Admin / Moderators'; ?></h2>
			<p class="auth-sub"><?php echo $show2faForm ? 'Ievadiet 6 ciparu kodu, ko nosūtījām uz jūsu e-pastu.' : 'Ievadi kredenciālus, lai atvērtu paneli.'; ?></p>
			<?php if ($errors): ?>
				<div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
			<?php endif; ?>
			<?php if ($show2faForm): ?>
				<form method="POST" action="<?php echo admin_route('login'); ?>" autocomplete="one-time-code">
					<div class="form-group">
						<label>Apstiprinājuma kods</label>
						<input type="text" name="twofa_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="000000">
					</div>
					<button type="submit" name="login_2fa_btn" class="btn-submit">Apstiprināt</button>
				</form>
				<p class="auth-footer"><a href="<?php echo admin_route('login'); ?>">Atpakaļ uz pieteikšanos</a></p>
			<?php else: ?>
				<form method="POST" action="<?php echo admin_route('login'); ?>">
					<div class="form-group">
						<label>Lietotājvārds</label>
						<input type="text" name="username" required>
					</div>
					<div class="form-group">
						<label>Parole</label>
						<input type="password" name="password" required>
					</div>
					<button type="submit" name="login_btn" class="btn-submit">Pieslēgties</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
