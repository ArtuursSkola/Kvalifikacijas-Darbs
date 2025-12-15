<?php
session_start();

// Projekts izvietots vienā mapē (nav "login" apakšmapes). con_db.php atrodas blakus index.php.
$configPath = __DIR__ . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā register.php.');
}

require $configPath;

$zinojums = "";
$atlasita_loma = 'lietotajs';

// Pārbaudām, vai tabulā eksistē kolonna 'loma'
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

    // Pārbaudes
    if ($parole_raw !== $parole_repeat) {
        $zinojums = "Paroles nesakrīt!";
    } else {
        // Pārbaudām, vai lietotājs jau eksistē
        $check_query = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards' OR epasts='$epasts'";
        $result = mysqli_query($savienojums, $check_query);

        if (mysqli_num_rows($result) > 0) {
            $zinojums = "Lietotājvārds vai e-pasts jau eksistē!";
        } else {
            // Šifrējam paroli
            $parole_hash = password_hash($parole_raw, PASSWORD_DEFAULT);

            // Ievietojam datubāzē
            if ($roleColumnExists) {
                $sql = "INSERT INTO est_lietotaji (lietotajvards, epasts, parole, loma) VALUES ('$lietotajvards', '$epasts', '$parole_hash', '$atlasita_loma')";
            } else {
                $sql = "INSERT INTO est_lietotaji (lietotajvards, epasts, parole) VALUES ('$lietotajvards', '$epasts', '$parole_hash')";
            }
            
            if (mysqli_query($savienojums, $sql)) {
                $zinojums = "Reģistrācija veiksmīga! Vari ielogoties.";
            } else {
                $zinojums = "Kļūda sistēmā: " . mysqli_error($savienojums);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija - HomeEstate</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
        </ul>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-container">
            <h2>Reģistrēties</h2>
            
            <?php if($zinojums): ?>
                <p class="<?php echo strpos($zinojums, 'veiksmīga') !== false ? 'success-msg' : 'error-msg'; ?>">
                    <?php echo $zinojums; ?>
                </p>
            <?php endif; ?>

            <form action="register.php" method="POST">
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
            <p style="margin-top: 15px; font-size: 0.9rem;">Jau ir konts? <a href="login.php" style="color: var(--accent-color);">Ielogoties</a></p>
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

</body>
</html>