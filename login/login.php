<?php
session_start();

// Projekts izvietots vienā mapē (nav "login" apakšmapes). con_db.php atrodas blakus index.php.
$configPath = __DIR__ . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā login.php.');
}

require $configPath;

$zinojums = "";
$atlasita_loma = 'lietotajs';

// Pārbaudām, vai tabulā ir kolonna 'loma', lai varētu salīdzināt lomas
$roleColumnExists = false;
$roleColumnCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'loma'");
if ($roleColumnCheck && mysqli_num_rows($roleColumnCheck) > 0) {
    $roleColumnExists = true;
}

if (isset($_POST['login_btn'])) {
    $lietotajvards = mysqli_real_escape_string($savienojums, $_POST['username']);
    $parole_raw = $_POST['password'];
    $atlasita_loma = isset($_POST['role']) && $_POST['role'] === 'ipasnieks' ? 'ipasnieks' : 'lietotajs';

    // Meklējam lietotāju
    $sql = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards'";
    $result = mysqli_query($savienojums, $sql);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $loma_no_db = ($roleColumnExists && isset($row['loma'])) ? $row['loma'] : 'lietotajs';
        
        // Pārbaudām paroli (Verify hash)
        if (password_verify($parole_raw, $row['parole'])) {
            // Ja ir kolonna 'loma', salīdzinām ar lietotāja izvēli
            if ($roleColumnExists && $atlasita_loma !== $loma_no_db) {
                $zinojums = "Šis konts ir reģistrēts kā \"$loma_no_db\".";
            } else {
            // Veiksmīga ielogošanās -> saglabājam sesijā
            $_SESSION['user_id'] = $row['lietotaja_id'];
            $_SESSION['username'] = $row['lietotajvards'];
            $_SESSION['role'] = $roleColumnExists ? $loma_no_db : $atlasita_loma;
            
            // Pārsūtām uz sākumlapu (tajā pašā mapē)
            header("Location: index.php");
            exit();
            }
        } else {
            $zinojums = "Nepareiza parole!";
        }
    } else {
        $zinojums = "Lietotājs nav atrasts!";
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ielogoties - HomeEstate</title>
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
            <h2>Pieslēgties</h2>
            
            <?php if($zinojums): ?>
                <p class="error-msg"><?php echo $zinojums; ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Lietotājvārds</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Parole</label>
                    <input type="password" name="password" required>
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
                    <div class="role-note" id="role-note-login"></div>
                </div>
                <button type="submit" name="login_btn" class="btn-submit login-btn-submit">Ielogoties</button>
            </form>
            <p style="margin-top: 15px; font-size: 0.9rem;">Nav konta? <a href="register.php" style="color: var(--accent-color);">Reģistrēties</a></p>
        </div>
    </div>

    <script>
    (function() {
        const radios = document.querySelectorAll('input[name="role"]');
        const note = document.getElementById('role-note-login');
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