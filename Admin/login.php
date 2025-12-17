<?php
session_start();

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
	die('Nav atrasts con_db.php');
}
require $configPath;

$errors = [];

// Ensure admin table exists with needed columns
function ensureAdminTable(mysqli $conn, array &$errors): void {
	$createSql = "CREATE TABLE IF NOT EXISTS est_admin (
		admin_id INT AUTO_INCREMENT PRIMARY KEY,
		lietotajvards VARCHAR(150) NOT NULL UNIQUE,
		epasts VARCHAR(255) NOT NULL UNIQUE,
		parole VARCHAR(255) NOT NULL,
		loma VARCHAR(50) NOT NULL DEFAULT 'moderator',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
	if (!$conn->query($createSql)) {
		$errors[] = 'Neizdevās izveidot est_admin: ' . $conn->error;
		return;
	}
	// Backfill columns if table existed without them
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

ensureAdminTable($savienojums, $errors);

if (isset($_POST['login_btn'])) {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($username === '' || $password === '') {
		$errors[] = 'Ievadi lietotājvārdu un paroli.';
	} else {
		$stmt = $savienojums->prepare("SELECT admin_id, lietotajvards, parole, loma FROM est_admin WHERE lietotajvards=? AND loma IN ('admin','moderator') LIMIT 1");
		if ($stmt) {
			$stmt->bind_param('s', $username);
			$stmt->execute();
			$result = $stmt->get_result();
			$user = $result ? $result->fetch_assoc() : null;
			$stmt->close();

			if ($user && password_verify($password, $user['parole'])) {
			$_SESSION['user_id'] = $user['admin_id'];
				$_SESSION['username'] = $user['lietotajvards'];
				$_SESSION['role'] = $user['loma'];
				header('Location: index.php');
				exit;
			} else {
				$errors[] = 'Nepareizs lietotājvārds vai parole (admin/moderator).';
			}
		} else {
			$errors[] = 'Neizdevās sagatavot pieprasījumu.';
		}
	}
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
	<style>
		body { background: #f5f8fb; }
		.auth-wrapper { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 120px 16px 60px; }
		.auth-card { width: min(480px, 100%); background:#fff; border:1px solid #e5ebf1; border-radius: 14px; padding: 26px; box-shadow:0 14px 32px rgba(0,0,0,0.08); }
		h2 { margin: 0 0 6px; color: var(--primary-color); }
		.auth-sub { margin: 0 0 14px; color: var(--text-light); }
		.form-group { margin-bottom: 12px; }
		.form-group label { font-weight: 700; color: var(--primary-color); display:block; margin-bottom:6px; }
		.form-group input { width:100%; padding:12px; border-radius: 10px; border:1px solid #dfe5eb; background:#f9fbfd; }
		.form-group input:focus { outline:2px solid rgba(48,182,7,0.25); border-color: rgba(48,182,7,0.35); background:#fff; }
		.btn-submit { width:100%; border-radius:12px; }
		.error { background:#ffe8e6; color:#c0392b; padding:10px; border-radius:10px; margin-bottom:10px; border:1px solid #f1c1bd; }
		.switch { margin-top: 12px; text-align:center; }
	</style>
</head>
<body>
	<div class="auth-wrapper">
		<div class="auth-card">
			<h2>Admin / Moderators</h2>
			<p class="auth-sub">Ievadi kredenciālus, lai atvērtu paneli.</p>
			<?php if ($errors): ?>
				<div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
			<?php endif; ?>
			<form method="POST" action="login.php">
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
			<div class="switch">Nav konta? <a href="register.php">Reģistrē admin/mod</a></div>
			<div class="switch"><a href="../login/login.php">Atpakaļ uz lietotāju login</a></div>
		</div>
	</div>
</body>
</html>
