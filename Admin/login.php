<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
	die('Nav atrasts con_db.php');
}
require $configPath;

$errors = [];


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
				$_SESSION['user_type'] = 'admin';
				header('Location: ' . admin_route('dashboard'));
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
			<h2>Admin / Moderators</h2>
			<p class="auth-sub">Ievadi kredenciālus, lai atvērtu paneli.</p>
			<?php if ($errors): ?>
				<div class="error"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
			<?php endif; ?>
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
		</div>
	</div>
</body>
</html>
