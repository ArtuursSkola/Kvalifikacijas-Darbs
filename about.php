<?php session_start(); ?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Par mums - HomeEstate</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/2.0.2/anime.min.js"></script>
</head>
<body class="about-page">


    <nav class="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="#">Meklēt īpašumu</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="owner.php">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <li><a href="about.php" class="active" style="color: var(--accent-color) !important;">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: bold; color: var(--primary-color);">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn-register" style="background-color: #c0392b;">Iziet</a>
            <?php else: ?>
                <a href="login.php" class="btn-login" style="color: var(--primary-color);">Ielogoties</a>
                <a href="register.php" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>
    
    <div class="about-header">
        <div class="container">
            <h1 class="ml6">
                <span class="text-wrapper">
                    <span class="letters">Mēs esam HomeEstate</span>
                </span>
            </h1>
            <p class="header-p-anim">Mūsu mērķis ir pārvērst mājokļa meklēšanu par patīkamu un vienkāršu pieredzi.</p>
        </div>
    </div>
    
    <section class="about-section">
        <div class="container">
            <h2 class="fade-in">Mūsu Vērtības un Mērķi</h2>
            <div class="mission-vision">
                <div class="mission-box fade-in">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Mūsu Misija</h3>
                    <p>Nodrošināt mūsdienīgu, drošu un pārskatāmu digitālo platformu, kas efektīvi savieno nekustamā īpašuma īpašniekus ar potenciālajiem īrniekiem vai pircējiem, veicinot godīgu un ātru darījumu veikšanu.</p>
                </div>
                <div class="mission-box fade-in">
                    <i class="fas fa-rocket"></i>
                    <h3>Mūsu Vīzija</h3>
                    <p>Kļūt par vadošo nekustamā īpašuma platformu reģionā, kas ir pazīstama ar savu uzticamību, inovatīviem risinājumiem un augstu lietotāju apmierinātības līmeni.</p>
                </div>
            </div>
            
            <div class="values-grid">
                <div class="value-item fade-in">
                    <i class="fas fa-handshake"></i>
                    <h4>Uzticamība</h4>
                    <p>Katrs sludinājums tiek pārbaudīts, lai garantētu drošību un patiesumu.</p>
                </div>
                <div class="value-item fade-in">
                    <i class="fas fa-bolt"></i>
                    <h4>Efektivitāte</h4>
                    <p>Meklēšanas un rezervācijas procesi ir maksimāli vienkāršoti un ātri.</p>
                </div>
                <div class="value-item fade-in">
                    <i class="fas fa-glasses"></i>
                    <h4>Caurspīdīgums</h4>
                    <p>Nav slēptu maksājumu. Viss ir skaidrs no paša sākuma.</p>
                </div>
                <div class="value-item fade-in">
                    <i class="fas fa-heart"></i>
                    <h4>Lietotājam Draudzīgs</h4>
                    <p>Mēs veidojam sistēmu, kuru mēs paši vēlētos izmantot.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="about-section" style="background-color: var(--white); padding-bottom: 0;">
        <div class="container">
            <h2 class="fade-in">HomeEstate Attīstības Stāsts</h2>
            <div class="timeline-container">
                
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <h3>2024. gada Sākums</h3>
                        <p>Idejas dzimšana. Tiek veikta tirgus izpēte un definētas galvenās problēmas, kuras HomeEstate atrisinās nekustamā īpašuma nozarē.</p>
                    </div>
                </div>

                <div class="timeline-item right">
                    <div class="timeline-content">
                        <h3>2024. gada Vidus</h3>
                        <p>MVP (Minimum Viable Product) izstrāde. Tiek izveidota pamata platformas arhitektūra, ieviešot reģistrācijas un sludinājumu apskates funkcijas.</p>
                    </div>
                </div>
                
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <h3>2024. gada Rudens</h3>
                        <p>Beta Testēšana. 100 atlasīti lietotāji uzsāk platformas testēšanu, ieviešot pirmās atsauksmju un ziņojumu funkcijas.</p>
                    </div>
                </div>
                
                <div class="timeline-item right">
                    <div class="timeline-content">
                        <h3>2025. gada Sākums</h3>
                        <p>Oficiāla palaišana. Platforma tiek atvērta plašākai publikai, ieviešot drošu maksājumu sistēmu un jaudīgākus filtrus.</p>
                    </div>
                </div>
                
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <h3>Nākotnes Vīzija</h3>
                        <p>Mākslīgais intelekts mājokļu ieteikumiem. Tuvākajā nākotnē plānots integrēt AI, lai personalizētu meklēšanas pieredzi un sniegtu labākos ieteikumus.</p>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <h2 style="color: var(--primary-color);">HomeEstate Skaitļos</h2>
            <p>Mēs augam katru dienu. Uzticies skaitļiem, kas atspoguļo mūsu veiksmi.</p>
            <div class="stats-grid">
                
                <div class="stat-item">
                    <span class="counter" data-target="1250">0</span>
                    <span class="label">Aktīvie Sludinājumi</span>
                </div>

                <div class="stat-item">
                    <span class="counter" data-target="380">0</span>
                    <span class="label">Veiksmīgi Darījumi</span>
                </div>
                
                <div class="stat-item">
                    <span class="counter" data-target="2700">0</span>
                    <span class="label">Reģistrētie Lietotāji</span>
                </div>

                <div class="stat-item">
                    <span class="counter" data-target="49">0</span>
                    <span class="label">Partneru Aģentūras</span>
                </div>

            </div>
        </div>
    </section>

    <section class="features-section" style="background-color: var(--primary-color);">
        <div class="container" style="color: var(--white); text-align: center;">
            <h2 style="color: var(--white); margin-bottom: 20px;">Pievienojies HomeEstate kopienai jau šodien!</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px;">Sāc meklēt savu ideālo mājokli vai publicē savu īpašumu tūlīt.</p>
            <a href="register.php" class="btn-search" style="background-color: var(--accent-color);">Reģistrēties tagad</a>
        </div>
    </section>

    <footer>
        <div class="container footer-content">
            <div class="footer-col">
                <h3>HomeEstate</h3>
                <p>Mūsdienīga platforma nekustamā īpašuma īrei un pārdošanai.</p>
            </div>
            <div class="footer-col">
                <h3>Saites</h3>
                <ul>
                    <li><a href="about.php">Par mums</a></li>
                    <li><a href="#">Lietošanas noteikumi</a></li>
                    <li><a href="#">Privātuma politika</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Kontakti</h3>
                <p>info@homeestate.lv</p>
                <p>+371 20000000</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 HomeEstate. Visas tiesības aizsargātas.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>