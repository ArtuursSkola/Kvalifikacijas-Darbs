<?php
session_start();

// con_db.php atrodas viena līmeņa augstāk (blakus index.php)
$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā login.php.');
}

require $configPath;

$zinojums = "";

// Pārbaudām, vai tabulā ir kolonna 'loma', lai varētu salīdzināt lomas
$roleColumnExists = false;
$roleColumnCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'loma'");
if ($roleColumnCheck && mysqli_num_rows($roleColumnCheck) > 0) {
    $roleColumnExists = true;
}

if (isset($_POST['login_btn'])) {
    $lietotajvards = mysqli_real_escape_string($savienojums, $_POST['username']);
    $parole_raw = $_POST['password'];

    // Meklējam lietotāju
    $sql = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards'";
    $result = mysqli_query($savienojums, $sql);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $loma_no_db = ($roleColumnExists && isset($row['loma'])) ? $row['loma'] : 'lietotajs';
        $plan_no_db = isset($row['plan']) ? $row['plan'] : null;
        
        // Pārbaudām paroli (Verify hash)
        if (password_verify($parole_raw, $row['parole'])) {
            // Veiksmīga ielogošanās -> saglabājam sesijā
            $_SESSION['user_id'] = $row['lietotaja_id'];
            $_SESSION['username'] = $row['lietotajvards'];
            $_SESSION['role'] = $loma_no_db;
            if ($plan_no_db !== null) {
                $_SESSION['plan'] = $plan_no_db;
            }
            
            // Pārsūtām uz sākumlapu (vienu līmeni augstāk)
            header("Location: ../index.php");
            exit();
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

        .btn-submit.login-btn-submit {
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

    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="../index.php">Sākums</a></li>
        </ul>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>Pieslēgties</h2>
            <p class="auth-subtitle">Ievadi datus, lai turpinātu.</p>
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
                <button type="submit" name="login_btn" class="btn-submit login-btn-submit">Ielogoties</button>
            </form>
            <div class="auth-footer">Nav konta? <a href="register.php">Reģistrēties</a></div>
            <div class="auth-footer" style="margin-top:8px;">Admin/moderator? <a href="../Admin/login.php">Atvērt admin login</a></div>
        </div>
    </div>

    <script src="../script.js"></script>

</body>
</html>