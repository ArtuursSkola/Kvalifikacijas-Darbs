<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā login.php.');
}

require $configPath;

$lockoutColumnsCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'failed_attempts'");
if ($lockoutColumnsCheck && mysqli_num_rows($lockoutColumnsCheck) === 0) {
    mysqli_query($savienojums, "ALTER TABLE est_lietotaji ADD COLUMN failed_attempts INT DEFAULT 0, ADD COLUMN lockout_until DATETIME DEFAULT NULL");
}

$zinojums = '';

$roleColumnExists = false;
$roleColumnCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'loma'");
if ($roleColumnCheck && mysqli_num_rows($roleColumnCheck) > 0) {
    $roleColumnExists = true;
}

$twoFaColumnExists = false;
$twoFaCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE '2fa_kods'");
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
        $mail2faHint = 'E-pasta 2FA nav pieejams: trūkst PHPMailer. Palaidiet composer install VAI pārbaudiet, ka eksistē mape lib/PHPMailer/src (projekta kopā ar kodu).';
    } elseif (mail_load_config() === null) {
        $mail2faHint = 'Nav config/mail.php. Pārbaudiet, ka fails eksistē un ir lasāms.';
    } else {
        $mail2faHint = 'SMTP nav pilnībā konfigurēts: izveidojiet config/mail.local.php (skat. mail.local.example.php) vai iestatiet MAILTRAP_PASSWORD.';
    }
}

function login_clear_pending_2fa(): void
{
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_username'], $_SESSION['pending_2fa_role'], $_SESSION['pending_2fa_plans']);
}

/** @param array<string, mixed> $row */
function login_complete_user_session(array $row, bool $roleColumnExists): void
{
    $loma_no_db = ($roleColumnExists && isset($row['loma'])) ? (string)$row['loma'] : 'lietotajs';
    $plans_no_db = isset($row['plans']) ? (string)$row['plans'] : 'Nekads';

    $_SESSION['user_id'] = (int)$row['lietotaja_id'];
    $_SESSION['username'] = (string)$row['lietotajvards'];
    $_SESSION['role'] = $loma_no_db;
    $_SESSION['user_type'] = 'user';
    $_SESSION['plans'] = $plans_no_db !== '' ? $plans_no_db : 'Nekads';
    $_SESSION['login_success'] = true;
}

if (isset($_POST['login_2fa_btn'])) {
    $code = preg_replace('/\D/', '', (string)($_POST['twofa_code'] ?? ''));
    $pendingId = (int)($_SESSION['pending_2fa_user_id'] ?? 0);
    if ($pendingId <= 0 || strlen($code) !== 6) {
        $zinojums = 'Nederīgs vai trūkstošs apstiprinājuma kods.';
    } else {
        $stmt = mysqli_prepare($savienojums, 'SELECT * FROM est_lietotaji WHERE lietotaja_id = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $pendingId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            $stored = isset($row['2fa_kods']) ? (string)$row['2fa_kods'] : '';
            if ($row && hash_equals($stored, $code)) {
                $clr = mysqli_prepare($savienojums, 'UPDATE est_lietotaji SET `2fa_kods` = NULL WHERE lietotaja_id = ?');
                if ($clr) {
                    mysqli_stmt_bind_param($clr, 'i', $pendingId);
                    mysqli_stmt_execute($clr);
                    mysqli_stmt_close($clr);
                }
                login_clear_pending_2fa();
                login_complete_user_session($row, $roleColumnExists);
                header('Location: ' . main_route('home'));
                exit;
            }
        }
        $zinojums = 'Nepareizs apstiprinājuma kods.';
    }
}

if (isset($_POST['login_btn'])) {
    $lietotajvards = mysqli_real_escape_string($savienojums, $_POST['username'] ?? '');
    $parole_raw = $_POST['password'] ?? '';

    $sql = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards'";
    $result = mysqli_query($savienojums, $sql);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $uid = (int)$row['lietotaja_id'];

        if ($row['lockout_until'] !== null && strtotime($row['lockout_until']) > time()) {
            $zinojums = 'Konts ir bloķēts uz 15 minūtēm.';
        } else {
            if (password_verify((string)$parole_raw, (string)($row['parole'] ?? ''))) {
                mysqli_query($savienojums, "UPDATE est_lietotaji SET failed_attempts = 0, lockout_until = NULL WHERE lietotaja_id = $uid");
                if ($useEmail2fa) {
                    $ep = trim((string)($row['epasts'] ?? ''));
                    if (!filter_var($ep, FILTER_VALIDATE_EMAIL)) {
                        login_complete_user_session($row, $roleColumnExists);
                        header('Location: ' . main_route('home'));
                        exit;
                    }
                    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $upd = mysqli_prepare($savienojums, 'UPDATE est_lietotaji SET `2fa_kods` = ? WHERE lietotaja_id = ?');
                    if ($upd) {
                        mysqli_stmt_bind_param($upd, 'si', $code, $uid);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);
                    }
                    $sent = mail_send_login_2fa_code($ep, (string)($row['lietotajvards'] ?? ''), $code, false);
                    if (!$sent) {
                        $clr = mysqli_prepare($savienojums, 'UPDATE est_lietotaji SET `2fa_kods` = NULL WHERE lietotaja_id = ?');
                        if ($clr) {
                            mysqli_stmt_bind_param($clr, 'i', $uid);
                            mysqli_stmt_execute($clr);
                            mysqli_stmt_close($clr);
                        }
                        $zinojums = 'Neizdevās nosūtīt apstiprinājuma e-pastu. Pārbaudiet SMTP (Mailtrap parole, ports) un PHP error_log.';
                    } else {
                        $_SESSION['pending_2fa_user_id'] = $uid;
                        header('Location: ' . main_route('login', ['twofa' => '1']));
                        exit;
                    }
                } else {
                    login_complete_user_session($row, $roleColumnExists);
                    header('Location: ' . main_route('home'));
                    exit;
                }
            } else {
                $attempts = (int)$row['failed_attempts'] + 1;
                if ($attempts >= 5) {
                    mysqli_query($savienojums, "UPDATE est_lietotaji SET failed_attempts = $attempts, lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE lietotaja_id = $uid");
                    $zinojums = 'Konts ir bloķēts uz 15 minūtēm.';
                } else {
                    mysqli_query($savienojums, "UPDATE est_lietotaji SET failed_attempts = $attempts WHERE lietotaja_id = $uid");
                    $zinojums = 'Nepareiza parole!';
                }
            }
        }
    } else {
        $zinojums = 'Lietotājs nav atrasts!';
    }
}

$show2faForm = $useEmail2fa && isset($_GET['twofa']) && (string)$_GET['twofa'] === '1' && !empty($_SESSION['pending_2fa_user_id']);

if (!$useEmail2fa && isset($_GET['twofa'])) {
    login_clear_pending_2fa();
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ielogoties - HomeEstate</title>
    <link rel="icon" type="image/png" href="../Images/Logo.png">
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
            <h2><?php echo $show2faForm ? 'Apstipriniet e-pastu' : 'Pieslēgties'; ?></h2>
            <p class="auth-subtitle"><?php echo $show2faForm ? 'Ievadiet 6 ciparu kodu, ko nosūtījām uz jūsu e-pastu.' : 'Ievadi datus, lai turpinātu.'; ?></p>
            <?php if ($zinojums): ?>
                <p class="error-msg"><?php echo htmlspecialchars($zinojums); ?></p>
            <?php endif; ?>

            <?php if ($show2faForm): ?>
                <form action="<?php echo main_route('login'); ?>" method="POST" autocomplete="one-time-code">
                    <div class="form-group">
                        <label>Apstiprinājuma kods</label>
                        <input type="text" name="twofa_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="000000">
                    </div>
                    <button type="submit" name="login_2fa_btn" class="btn-submit login-btn-submit">Apstiprināt</button>
                </form>
                <p class="auth-footer"><a href="<?php echo main_route('login'); ?>">Atpakaļ uz pieteikšanos</a></p>
            <?php else: ?>
                <form action="<?php echo main_route('login'); ?>" method="POST">
                    <div class="form-group">
                        <label>Lietotājvārds</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Parole</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="login_btn" class="btn-submit login-btn-submit">Ielogoties</button>
                </form>
                <div class="auth-footer">Nav konta? <a href="<?php echo main_route('register'); ?>">Reģistrēties</a></div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../script.js"></script>

</body>
</html>
