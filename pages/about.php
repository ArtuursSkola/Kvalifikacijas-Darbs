<?php
session_start();
require_once __DIR__ . '/../routes/main.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

$pageTitle = 'Par mums - HomeEstate';
$extraStyles = ['about'];
include __DIR__ . '/../includes/header.php';

$activeHomesCount = 0;
$resActive = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Aktivs'");
if ($resActive && $row = $resActive->fetch_row()) $activeHomesCount = (int)$row[0];

$totalUsersCount = 0;
$resUsers = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji");
if ($resUsers && $row = $resUsers->fetch_row()) $totalUsersCount = (int)$row[0];

$dealsCount = 0;
$resDeals = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE statuss = 'Pardots'");
if ($resDeals && $row = $resDeals->fetch_row()) $dealsCount = (int)$row[0];
?>

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
                    <div class="stat-number"><?php echo number_format($activeHomesCount, 0, ',', ' '); ?></div>
                    <div class="stat-label">Aktīvie Sludinājumi</div>
                </div>
                <div class="stat-card fade-up">
                    <div class="stat-number"><?php echo number_format($dealsCount, 0, ',', ' '); ?><span class="stat-suffix">+</span></div>
                    <div class="stat-label">Veiksmīgi Darījumi</div>
                </div>
                <div class="stat-card fade-up">
                    <div class="stat-number"><?php echo number_format($totalUsersCount, 0, ',', ' '); ?></div>
                    <div class="stat-label">Reģistrētie Lietotāji</div>
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
                        <span class="timeline-year">2025 Vidus</span>
                        <h3>Idejas Dzimšana</h3>
                        <p>Veikta tirgus izpēte un definētas galvenās problēmas, kuras HomeEstate atrisinās nekustamā īpašuma nozarē.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2025 Beigas</span>
                        <h3>MVP Izstrāde</h3>
                        <p>Izveidota pamata platformas arhitektūra, ieviešot reģistrācijas un sludinājumu apskates funkcijas.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2026 Sākums</span>
                        <h3>Pilvērtīgā izveide</h3>
                        <p>Platforma ir izveidota un sākas lapas testēšana un pielabošana un gatavošana palaišanai.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">2026 Vidus</span>
                        <h3>Oficiālā Palaišana</h3>
                        <p>Platforma tiek atvērta plašākai publikai ar drošu maksājumu sistēmu un jaudīgiem filtriem.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                <div class="timeline-item fade-up">
                    <div class="timeline-content">
                        <span class="timeline-year">Nākotne</span>
                        <h3>Popularizēšana</h3>
                        <p>Sākt veidot reklāmas un materiālus, kas pierādīs, ka esam paši labākie un visuzticamākie Latvijā.</p>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {

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

