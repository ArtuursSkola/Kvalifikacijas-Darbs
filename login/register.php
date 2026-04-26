<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā register.php.');
}

require $configPath;

$zinojums = "";
$atlasita_loma = 'lietotajs';

$roleColumnExists = false;
$roleColumnCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'loma'");
if ($roleColumnCheck && mysqli_num_rows($roleColumnCheck) > 0) {
    $roleColumnExists = true;
}

if (isset($_POST['register_btn'])) {
    $lietotajvards = mysqli_real_escape_string($savienojums, $_POST['username']);
    $epasts = mysqli_real_escape_string($savienojums, $_POST['email']);
    $parole_raw = $_POST['password'];
    $parole_repeat = $_POST['password_repeat'];
    $atlasita_loma = isset($_POST['role']) && $_POST['role'] === 'ipasnieks' ? 'ipasnieks' : 'lietotajs';

    $errors = [];

    if (preg_match('/[0-9]/', $lietotajvards)) {
        $errors[] = "Lietotājvārds nevar saturēt skaitļus";
    }
    if (strlen($lietotajvards) < 5) {
        $errors[] = "Lietotājvārdam jābūt vismaz 5 simbolus garam";
    } elseif (strlen($lietotajvards) > 25) {
        $errors[] = "Lietotājvārds nevar būt garāks par 25 simboliem";
    }

    if (strpos($epasts, '@') === false) {
        $errors[] = "E-pastam jāsatur @ simbols";
    }

    if (strlen($parole_raw) < 8) {
        $errors[] = "Parolei jābūt vismaz 8 simbolus garai";
    }
    if (!preg_match('/[0-9]/', $parole_raw)) {
        $errors[] = "Parolei jāsatur vismaz viens skaitlis";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $parole_raw)) {
        $errors[] = "Parolei jāsatur vismaz viens simbols";
    }

    if ($parole_raw !== $parole_repeat) {
        $errors[] = "Paroles nesakrīt!";
    }

    if (empty($errors)) {
        $check_query = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards' OR epasts='$epasts'";
        $result = mysqli_query($savienojums, $check_query);

        if (mysqli_num_rows($result) > 0) {
            $zinojums = "Lietotājvārds vai e-pasts jau eksistē!";
        } else {
            $parole_hash = password_hash($parole_raw, PASSWORD_DEFAULT);

            if ($roleColumnExists) {
                $sql = "INSERT INTO est_lietotaji (lietotajvards, epasts, parole, loma) VALUES ('$lietotajvards', '$epasts', '$parole_hash', '$atlasita_loma')";
            } else {
                $sql = "INSERT INTO est_lietotaji (lietotajvards, epasts, parole) VALUES ('$lietotajvards', '$epasts', '$parole_hash')";
            }
            
            if (mysqli_query($savienojums, $sql)) {
                $_SESSION['user_id'] = mysqli_insert_id($savienojums);
                $_SESSION['username'] = $lietotajvards;
                $_SESSION['role'] = $atlasita_loma;
                
                header('Location: ' . main_route('home'));
                exit;
            } else {
                $zinojums = "Kļūda sistēmā: " . mysqli_error($savienojums);
            }
        }
    } else {
        $zinojums = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija - HomeEstate</title>
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
            <h2>Reģistrēties</h2>
            <p class="auth-subtitle">Izveido kontu, lai uzsāktu.</p>
            <?php if($zinojums): ?>
                <p class="<?php echo strpos($zinojums, 'veiksmīga') !== false ? 'success-msg' : 'error-msg'; ?>">
                    <?php echo $zinojums; ?>
                </p>
            <?php endif; ?>

            <form action="<?php echo main_route('register'); ?>" method="POST">
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
                    <label>Izvēlies lomu</label>
                    <div class="role-toggle">
                        <label class="role-option">
                            <input type="radio" name="role" value="lietotajs" <?php echo $atlasita_loma === 'ipasnieks' ? '' : 'checked'; ?>>
                            <span>Lietotājs</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="ipasnieks" <?php echo $atlasita_loma === 'ipasnieks' ? 'checked' : ''; ?>>
                            <span>Īpašnieks</span>
                        </label>
                    </div>
                    <div class="role-note" id="role-note-register"></div>
                </div>
                <button type="submit" name="register_btn" class="btn-submit">Izveidot kontu</button>
            </form>
            <div class="auth-footer">Jau ir konts? <a href="<?php echo main_route('login'); ?>">Ielogoties</a></div>
        </div>
    </div>

    <script>
    (function() {
        const radios = document.querySelectorAll('input[name="role"]');
        const note = document.getElementById('role-note-register');
        if (!note || !radios.length) return;
        const update = () => {
            const val = Array.from(radios).find(r => r.checked)?.value;
            note.textContent = val === 'ipasnieks' ? 'Pašlaik izvēlēts: Īpašnieks' : 'Pašlaik izvēlēts: Lietotājs';
        };
        radios.forEach(r => r.addEventListener('change', update));
        update();
    })();
    </script>

    <script src="../script.js"></script>

</body>
</html>