<?php
session_start();
require_once __DIR__ . '/../routes/admin.php';


$configPath = dirname(__DIR__) . '/con_db.php';
if (!file_exists($configPath)) {
    die('Nav atrasts con_db.php. Pārliecinies, ka fails atrodas tajā pašā mapē kā login.php.');
}

require $configPath;

$zinojums = "";


$roleColumnExists = false;
$roleColumnCheck = mysqli_query($savienojums, "SHOW COLUMNS FROM est_lietotaji LIKE 'loma'");
if ($roleColumnCheck && mysqli_num_rows($roleColumnCheck) > 0) {
    $roleColumnExists = true;
}

if (isset($_POST['login_btn'])) {
    $lietotajvards = mysqli_real_escape_string($savienojums, $_POST['username']);
    $parole_raw = $_POST['password'];

    // Šeit pārbauda, vai lietotājs jau eksistē sistēma
    $sql = "SELECT * FROM est_lietotaji WHERE lietotajvards='$lietotajvards'";
    $result = mysqli_query($savienojums, $sql);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $loma_no_db = ($roleColumnExists && isset($row['loma'])) ? $row['loma'] : 'lietotajs';
        $plans_no_db = isset($row['plans']) ? $row['plans'] : 'Nekads';

        if (password_verify($parole_raw, $row['parole'])) {
            // Veiksmīga ielogošanās to saglabā sesijā
            $_SESSION['user_id'] = $row['lietotaja_id'];
            $_SESSION['username'] = $row['lietotajvards'];
            $_SESSION['role'] = $loma_no_db;
            $_SESSION['user_type'] = 'user';
            $_SESSION['plans'] = $plans_no_db !== '' ? $plans_no_db : 'Nekads';

            header("Location: " . main_route('home'));
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
            <h2>Pieslēgties</h2>
            <p class="auth-subtitle">Ievadi datus, lai turpinātu.</p>
            <?php if($zinojums): ?>
                <p class="error-msg"><?php echo $zinojums; ?></p>
            <?php endif; ?>

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
        </div>
    </div>

    <script src="../script.js"></script>

</body>
</html>
