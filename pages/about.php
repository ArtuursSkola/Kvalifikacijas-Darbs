<?php session_start(); require_once __DIR__ . '/../routes/main.php'; ?>
<?php 
$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Par mums - HomeEstate</title>
    <link rel="icon" type="image/png" href="<?php echo asset_path('Images/Logo.png'); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_path('style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_path('css/about.css'); ?>">
</head>
<body>

    <nav class="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
            <li><a href="<?php echo main_route('property.list'); ?>">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="<?php echo main_route('owner'); ?>">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="<?php echo main_route('property.create'); ?>">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="<?php echo main_route('about'); ?>" class="active">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="font-weight: 600; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="<?php echo main_route('logout'); ?>" class="btn-register" style="background: #e74c3c;">Iziet</a>
            <?php else: ?>
                <a href="<?php echo main_route('login'); ?>" class="btn-login" style="color: inherit;">Ielogoties</a>
                <a href="<?php echo main_route('register'); ?>" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-award"></i>
                Nr.1 Nekustamā īpašuma platforma
            </div>
            <h1>Mēs esam <span class="highlight">HomeEstate</span></h1>
            <p>Pārvēršam mājokļa meklēšanu par patīkamu un vienkāršu pieredzi. Moderna, droša un uzticama platforma jūsu nākamajām mājām.</p>
            <div class="hero-buttons">
                <a href="<?php echo main_route('property.list'); ?>" class="btn-hero primary">
                    <i class="fas fa-search"></i>
                    Meklēt īpašumus
                </a>
                <a href="#mission" class="btn-hero secondary">
                    <i class="fas fa-play-circle"></i>
                    Uzzināt vairāk
                </a>
            </div>
        </div>
        <div class="scroll-indicator">
            <span>Ritini uz leju</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <section class="mission-section" id="mission">
        <div class="container">
            <div class="section-header fade-up">
                <span class="section-label">Mūsu Mērķi</span>
                <h2 class="section-title">Misija un Vīzija</h2>
                <p class="section-subtitle">Mēs strādājam, lai padarītu nekustamā īpašuma tirgu pieejamāku un caurskatāmāku visiem.</p>
            </div>
            <div class="mission-grid">
                <div class="mission-card fade-up">
                    <div class="mission-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Mūsu Misija</h3>
                    <p>Nodrošināt mūsdienīgu, drošu un pārskatāmu digitālo platformu, kas efektīvi savieno nekustamā īpašuma īpašniekus ar potenciālajiem īrniekiem vai pircējiem, veicinot godīgu un ātru darījumu veikšanu.</p>
                </div>
                <div class="mission-card fade-up">
                    <div class="mission-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Mūsu Vīzija</h3>
                    <p>Kļūt par vadošo nekustamā īpašuma platformu reģionā, kas ir pazīstama ar savu uzticamību, inovatīviem risinājumiem un augstu lietotāju apmierinātības līmeni, izmantojot jaunākās tehnoloģijas.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="values-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="section-label">Ko Mēs Vērtējam</span>
                <h2 class="section-title">Mūsu Vērtības</h2>
                <p class="section-subtitle">Principi, kas virza mūsu ikdienas darbu un lēmumu pieņemšanu.</p>
            </div>
            <div class="values-grid">
                <div class="value-card fade-up">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4>Uzticamība</h4>
                    <p>Katrs sludinājums tiek pārbaudīts, lai garantētu drošību un patiesumu.</p>
                </div>
                <div class="value-card fade-up">
                    <div class="value-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4>Efektivitāte</h4>
                    <p>Meklēšanas un rezervācijas procesi ir maksimāli vienkāršoti un ātri.</p>
                </div>
                <div class="value-card fade-up">
                    <div class="value-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h4>Caurspīdīgums</h4>
                    <p>Nav slēptu maksājumu. Viss ir skaidrs no paša sākuma.</p>
                </div>
                <div class="value-card fade-up">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Lietotājam Draudzīgs</h4>
                    <p>Mēs veidojam sistēmu, kuru mēs paši vēlētos izmantot.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="section-label">HomeEstate Skaitļos</span>
                <h2 class="section-title">Mūsu Sasniegumi</h2>
                <p class="section-subtitle">Skaitļi, kas atspoguļo mūsu izaugsmi un lietotāju uzticību.</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card fade-up">
                    <div class="stat-number"><span class="counter" data-target="1250">0</span></div>
                    <div class="stat-label">Aktīvie Sludinājumi</div>
                </div>
                <div class="stat-card fade-up">
                    <div class="stat-number"><span class="counter" data-target="380">0</span><span class="stat-suffix">+</span></div>
                    <div class="stat-label">Veiksmīgi Darījumi</div>
                </div>
                <div class="stat-card fade-up">
                    <div class="stat-number"><span class="counter" data-target="2700">0</span></div>
                    <div class="stat-label">Reģistrētie Lietotāji</div>
                </div>
                <div class="stat-card fade-up">
                    <div class="stat-number"><span class="counter" data-target="49">0</span></div>
                    <div class="stat-label">Partneru Aģentūras</div>
                </div>
            </div>
        </div>
    </section>

    <section class="timeline-section">
        <div class="container">
            <div class="section-header fade-up">
                <span class="section-label">Mūsu Ceļš</span>
                <h2 class="section-title">Attīstības Stāsts</h2>
                <p class="section-subtitle">No idejas līdz reālai platformai - mūsu ceļojums.</p>
            </div>
            <div class="timeline">
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2024 Sākums</span>
                        <h3>Idejas Dzimšana</h3>
                        <p>Veikta tirgus izpēte un definētas galvenās problēmas, kuras HomeEstate atrisinās nekustamā īpašuma nozarē.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2024 Vidus</span>
                        <h3>MVP Izstrāde</h3>
                        <p>Izveidota pamata platformas arhitektūra, ieviešot reģistrācijas un sludinājumu apskates funkcijas.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2024 Rudens</span>
                        <h3>Beta Testēšana</h3>
                        <p>100 atlasīti lietotāji uzsāk platformas testēšanu, ieviešot pirmās atsauksmju funkcijas.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2025</span>
                        <h3>Oficiālā Palaišana</h3>
                        <p>Platforma tiek atvērta plašākai publikai ar drošu maksājumu sistēmu un jaudīgiem filtriem.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">Nākotne</span>
                        <h3>AI Integrācija</h3>
                        <p>Plānots integrēt mākslīgo intelektu, lai personalizētu meklēšanas pieredzi un sniegtu labākos ieteikumus.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-content fade-up">
                <h2>Pievienojies HomeEstate kopienai!</h2>
                <p>Sāc meklēt savu ideālo mājokli vai publicē savu īpašumu jau šodien.</p>
                <a href="<?php echo main_route('register'); ?>" class="btn-cta">
                    <i class="fas fa-user-plus"></i>
                    Reģistrēties tagad
                </a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>Home<span>Estate</span></h3>
                    <p>Mūsdienīga platforma nekustamā īpašuma īrei un pārdošanai. Mēs palīdzam atrast jūsu nākamās mājas.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Navigācija</h4>
                    <ul>
                        <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
                        <li><a href="<?php echo main_route('property.list'); ?>">Īpašumi</a></li>
                        <li><a href="<?php echo main_route('about'); ?>">Par mums</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Juridiskā Info</h4>
                    <ul>
                        <li><a href="#">Lietošanas noteikumi</a></li>
                        <li><a href="#">Privātuma politika</a></li>
                        <li><a href="#">Sīkdatņu politika</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontakti</h4>
                    <ul>
                        <li><i class="fas fa-envelope" style="margin-right: 8px; color: var(--accent);"></i> info@homeestate.lv</li>
                        <li><i class="fas fa-phone" style="margin-right: 8px; color: var(--accent);"></i> +371 20000000</li>
                        <li><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: var(--accent);"></i> Rīga, Latvija</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HomeEstate. Visas tiesības aizsargātas.</p>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Navbar scroll effect
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Fade up animations
        const fadeElements = document.querySelectorAll('.fade-up');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        
        fadeElements.forEach(el => observer.observe(el));

        // Counter animation
        const counters = document.querySelectorAll('.counter');
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-target'));
                    const duration = 2000;
                    const step = target / (duration / 16);
                    let current = 0;
                    
                    const updateCounter = () => {
                        current += step;
                        if (current < target) {
                            counter.textContent = Math.floor(current).toLocaleString('lv-LV');
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target.toLocaleString('lv-LV');
                        }
                    };
                    
                    updateCounter();
                    counterObserver.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));

        // Card tilt effect
        const cards = document.querySelectorAll('.mission-card, .value-card');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left - rect.width / 2) / rect.width;
                const y = (e.clientY - rect.top - rect.height / 2) / rect.height;
                card.style.transform = `translateY(-8px) perspective(1000px) rotateX(${y * -5}deg) rotateY(${x * 5}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    });
    </script>
</body>
</html>
