<?php
session_start();

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
	die('Nav atrasts con_db.php');
}
require $configPath;

$msg = '';

function ensureAdminTable(mysqli $conn): void {
	$createSql = "CREATE TABLE IF NOT EXISTS est_admin (
		admin_id INT AUTO_INCREMENT PRIMARY KEY,
		lietotajvards VARCHAR(150) NOT NULL UNIQUE,
		epasts VARCHAR(255) NOT NULL UNIQUE,
		parole VARCHAR(255) NOT NULL,
		loma VARCHAR(50) NOT NULL DEFAULT 'moderator',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
	$conn->query($createSql);
	$needs = [
		'loma' => "ALTER TABLE est_admin ADD COLUMN loma VARCHAR(50) NOT NULL DEFAULT 'moderator'",
		'created_at' => "ALTER TABLE est_admin ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
	];
	foreach ($needs as $col => $sql) {
		$res = $conn->query("SHOW COLUMNS FROM est_admin LIKE '" . $conn->real_escape_string($col) . "'");
		if ($res && $res->num_rows === 0) {
			$conn->query($sql);
		}
	}
}

ensureAdminTable($savienojums);

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
	<style>
		body { background: #f5f8fb; }
		.auth-wrapper { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 120px 16px 60px; }
		.auth-card { width: min(520px, 100%); background:#fff; border:1px solid #e5ebf1; border-radius: 14px; padding: 28px; box-shadow:0 14px 32px rgba(0,0,0,0.08); }
		h2 { margin: 0 0 6px; color: var(--primary-color); }
		.auth-sub { margin: 0 0 14px; color: var(--text-light); }
		.form-group { margin-bottom: 12px; }
		.form-group label { font-weight: 700; color: var(--primary-color); display:block; margin-bottom:6px; }
		.form-group input, .form-group select { width:100%; padding:12px; border-radius:10px; border:1px solid #dfe5eb; background:#f9fbfd; }
		.form-group input:focus, .form-group select:focus { outline:2px solid rgba(48,182,7,0.25); border-color: rgba(48,182,7,0.35); background:#fff; }
		.btn-submit { width:100%; border-radius:12px; }
		.msg { margin-bottom:12px; padding:10px; border-radius:10px; }
		.msg.ok { background:#e8f8ed; color:#1f7a35; border:1px solid #c5e8d0; }
		.msg.err { background:#ffe8e6; color:#c0392b; border:1px solid #f1c1bd; }
		.switch { margin-top:12px; text-align:center; }
	</style>
</head>
<body>
	<div class="auth-wrapper">
		<div class="auth-card">
			<h2>Reģistrēt admin/mod</h2>
			<p class="auth-sub">Izveido jaunu administratoru vai moderatoru.</p>
			<?php if ($msg): ?>
				<div class="msg <?php echo strpos($msg, 'izveidots') !== false ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($msg); ?></div>
			<?php endif; ?>
			<form method="POST" action="register.php">
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
			<div class="switch">Jau ir konts? <a href="login.php">Ielogoties</a></div>
			<div class="switch"><a href="../login/login.php">Atpakaļ uz lietotāju login</a></div>
		</div>
	</div>
</body>
</html>
