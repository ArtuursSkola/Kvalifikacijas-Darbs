<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';

// con_db.php atrodas viena līmeņa augstāk (blakus index.php)
$configPath = dirname(__DIR__) . '/con_db.php';
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
    <link rel="icon" type="image/png" href="../Images/Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        body.auth-page {
            background: radial-gradient(circle at 18% 20%, rgba(48,182,7,0.08), transparent 32%),
                        radial-gradient(circle at 82% 8%, rgba(44,62,80,0.07), transparent 34%),
                        var(--light-bg);
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 140px 16px 90px;
        }

        .auth-card {
            width: min(520px, 100%);
            margin: 0 auto;
            border-radius: 18px;
            padding: 34px 30px;
            box-shadow: 0 18px 48px rgba(0,0,0,0.12);
            border: 1px solid #e6ecf0;
            background: var(--white);
        }

        .auth-card h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.8rem;
            margin-bottom: 6px;
        }

        .auth-subtitle {
            text-align: center;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 18px;
        }

        .auth-card form {
            display: grid;
            gap: 14px;
        }

        .form-group {
            margin: 0;
        }

        .form-group label {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 6px;
            display: block;
        }

        .form-group input {
            border-radius: 12px;
            border: 1px solid #dfe5eb;
            background: #f9fbfd;
            padding: 12px 12px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: 2px solid rgba(48,182,7,0.25);
            border-color: rgba(48,182,7,0.4);
            background: var(--white);
        }

        .role-toggle {
            background: #f4f7fb;
            border: 1px solid #dfe5eb;
            border-radius: 12px;
            padding: 10px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .role-option span {
            background: var(--white);
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 700;
            color: var(--text-dark);
            transition: 0.2s ease;
            text-align: center;
        }

        .role-option input:checked + span {
            background: rgba(48,182,7,0.12);
            border-color: rgba(48,182,7,0.35);
            color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(48,182,7,0.12);
        }

        .role-note {
            margin-top: 6px;
            color: var(--text-light);
        }

        .btn-submit {
            border-radius: 12px;
            font-size: 1.05rem;
        }

        .auth-footer {
            margin-top: 16px;
            text-align: center;
        }
    </style>
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
            <div class="auth-footer" style="margin-top:8px;">Admin/moderator? <a href="<?php echo admin_route('register'); ?>">Izveidot admin/mod kontu</a></div>
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