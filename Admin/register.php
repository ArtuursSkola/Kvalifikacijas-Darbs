<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
	die('Nav atrasts con_db.php');
}
require $configPath;

$msg = '';


if (isset($_POST['register_btn'])) {
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$password2 = $_POST['password_repeat'] ?? '';
	$role = $_POST['role'] ?? 'moderator';
	if (!in_array($role, ['admin','moderator'], true)) {
		$role = 'moderator';
	}

	if ($username === '' || $email === '' || $password === '' || $password2 === '') {
		$msg = 'Aizpildi visus laukus.';
	} elseif ($password !== $password2) {
		$msg = 'Paroles nesakrīt.';
	} else {
		$chk = $savienojums->prepare('SELECT admin_id FROM est_admin WHERE lietotajvards=? OR epasts=? LIMIT 1');
		if ($chk) {
			$chk->bind_param('ss', $username, $email);
			$chk->execute();
			$res = $chk->get_result();
			$exists = $res && $res->num_rows > 0;
			$chk->close();
			if ($exists) {
				$msg = 'Lietotājs ar šādu vārdu vai e-pastu jau ir.';
			} else {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$ins = $savienojums->prepare('INSERT INTO est_admin (lietotajvards, epasts, parole, loma) VALUES (?, ?, ?, ?)');
				if ($ins) {
					$ins->bind_param('ssss', $username, $email, $hash, $role);
					if ($ins->execute()) {
						$msg = 'Konts izveidots. Tagad vari ielogoties.';
					} else {
						$msg = 'Neizdevās saglabāt: ' . $ins->error;
					}
					$ins->close();
				} else {
					$msg = 'Neizdevās sagatavot ievietošanu.';
				}
			}
		} else {
			$msg = 'Neizdevās sagatavot pārbaudi.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reģistrēt admin/moderatoru</title>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link rel="stylesheet" href="../style.css">
	<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
	<div class="auth-wrapper">
		<div class="auth-card">
			<h2>Reģistrēt admin/mod</h2>
			<p class="auth-sub">Izveido jaunu administratoru vai moderatoru.</p>
			<?php if ($msg): ?>
				<div class="msg <?php echo strpos($msg, 'izveidots') !== false ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($msg); ?></div>
			<?php endif; ?>
			<form method="POST" action="<?php echo admin_route('register'); ?>">
				<div class="form-group">
					<label>Lietotājvārds</label>
					<input type="text" name="username" required>
				</div>
				<div class="form-group">
					<label>E-pasts</label>
					<input type="email" name="email" required>
				</div>
				<div class="form-group">
					<label>Parole</label>
					<input type="password" name="password" required>
				</div>
				<div class="form-group">
					<label>Atkārtot paroli</label>
					<input type="password" name="password_repeat" required>
				</div>
				<div class="form-group">
					<label>Loma</label>
					<select name="role">
						<option value="moderator">Moderators</option>
						<option value="admin">Admins</option>
					</select>
				</div>
				<button type="submit" name="register_btn" class="btn-submit">Izveidot kontu</button>
			</form>
			<div class="switch">Jau ir konts? <a href="<?php echo admin_route('login'); ?>">Ielogoties</a></div>
			<div class="switch"><a href="<?php echo main_route('login'); ?>">Atpakaļ uz lietotāju login</a></div>
		</div>
	</div>
</body>
</html>
