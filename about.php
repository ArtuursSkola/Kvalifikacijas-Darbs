<?php session_start(); ?>
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
    <link rel="icon" type="image/png" href="Images/Logo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* About page specific offsets */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 24px 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 50%, #1a5a2e 100%);
            overflow: hidden;
            margin-top: -80px; /* Offset for fixed navbar */
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 30%, rgba(48,182,7,0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 70%, rgba(255,255,255,0.05) 0%, transparent 40%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-shapes {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--accent);
            top: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: var(--white);
            bottom: -80px;
            left: -80px;
            animation: float 10s ease-in-out infinite reverse;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            background: var(--accent-light);
            top: 40%;
            left: 10%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: var(--white);
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 28px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .hero-badge i { color: var(--accent-light); }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            color: var(--white);
            line-height: 1.15;
            margin-bottom: 24px;
        }

        .hero h1 .highlight {
            color: var(--accent-light);
            position: relative;
        }

        .hero p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.85);
            max-width: 650px;
            margin: 0 auto 36px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-hero.primary {
            background: var(--accent);
            color: var(--white);
            box-shadow: 0 8px 30px rgba(48,182,7,0.4);
        }

        .btn-hero.primary:hover {
            background: var(--accent-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(48,182,7,0.5);
        }

        .btn-hero.secondary {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: var(--white);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-hero.secondary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(10px); }
        }

        /* Sections */
        section {
            padding: 100px 24px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-label {
            display: inline-block;
            background: rgba(48,182,7,0.1);
            color: var(--accent);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray-600);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Mission Cards */
        .mission-section {
            background: var(--white);
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
        }

        .mission-card {
            position: relative;
            background: linear-gradient(135deg, var(--light-bg), var(--white));
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 40px;
            transition: all 0.4s ease;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .mission-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .mission-card:hover::before {
            transform: scaleX(1);
        }

        .mission-icon {
            width: 72px;
            height: 72px;
            background: rgba(48,182,7,0.1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 24px;
            transition: all 0.3s;
        }

        .mission-card:hover .mission-icon {
            background: var(--accent);
            color: var(--white);
            transform: scale(1.1) rotate(5deg);
        }

        .mission-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .mission-card p {
            color: var(--gray-600);
            line-height: 1.8;
        }

        /* Values Section */
        .values-section {
            background: linear-gradient(180deg, var(--light-bg), var(--white));
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .value-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent);
        }

        .value-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--white);
            margin: 0 auto 20px;
            transition: all 0.3s;
        }

        .value-card:hover .value-icon {
            transform: scale(1.15) rotate(10deg);
            box-shadow: 0 8px 25px rgba(48,182,7,0.4);
        }

        .value-card h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .value-card p {
            font-size: 0.95rem;
            color: var(--gray-600);
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .stats-section .section-title,
        .stats-section .section-subtitle {
            color: var(--white);
        }

        .stats-section .section-label {
            background: rgba(255,255,255,0.15);
            color: var(--white);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-lg);
            padding: 40px 24px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-6px);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--accent-light);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-suffix {
            font-size: 1.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }

        /* Timeline Section */
        .timeline-section {
            background: var(--white);
        }

        .timeline {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--accent), var(--accent-dark));
            border-radius: 4px;
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            padding: 24px 0;
            display: flex;
            align-items: flex-start;
        }

        .timeline-item:nth-child(odd) {
            flex-direction: row;
        }

        .timeline-item:nth-child(even) {
            flex-direction: row-reverse;
        }

        .timeline-content {
            width: calc(50% - 40px);
            background: var(--light-bg);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            padding: 28px;
            transition: all 0.3s;
        }

        .timeline-content:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 32px;
            width: 20px;
            height: 20px;
            background: var(--accent);
            border: 4px solid var(--white);
            border-radius: 50%;
            transform: translateX(-50%);
            box-shadow: 0 0 0 6px rgba(48,182,7,0.2);
            z-index: 2;
        }

        .timeline-year {
            display: inline-block;
            background: var(--accent);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .timeline-content h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .timeline-content p {
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-section h2 {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 800;
            color: var(--white);
            margin-bottom: 16px;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 32px;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--white);
            color: var(--accent);
            padding: 18px 40px;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }

        .btn-cta:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: rgba(255,255,255,0.8);
            padding: 80px 24px 24px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .footer-col h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
        }

        .footer-col h3 span { color: var(--accent); }

        .footer-col p {
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 20px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 12px;
        }

        .footer-col ul a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s;
        }

        .footer-col ul a:hover {
            color: var(--accent);
            padding-left: 5px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .footer-social a {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            background: var(--accent);
            transform: translateY(-4px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
        }

        /* Animations */
        .fade-up {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .mission-grid { grid-template-columns: 1fr; }
            .values-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-content { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .hero {
                margin-top: -70px;
                min-height: auto;
                padding: 140px 20px 80px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-hero {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }

            section {
                padding: 60px 20px;
            }

            .values-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }

            .timeline::before {
                left: 16px;
            }

            .timeline-item,
            .timeline-item:nth-child(even) {
                flex-direction: column;
                padding-left: 50px;
            }

            .timeline-content {
                width: 100%;
            }

            .timeline-dot {
                left: 16px;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="homes.php">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="owner.php">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="newhome.php">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="about.php" class="active">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="font-weight: 600; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login/logout.php" class="btn-register" style="background: #e74c3c;">Iziet</a>
            <?php else: ?>
                <a href="login/login.php" class="btn-login" style="color: inherit;">Ielogoties</a>
                <a href="login/register.php" class="btn-register">Reģistrēties</a>
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
                <a href="homes.php" class="btn-hero primary">
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
                <a href="login/register.php" class="btn-cta">
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
                        <li><a href="index.php">Sākums</a></li>
                        <li><a href="homes.php">Īpašumi</a></li>
                        <li><a href="about.php">Par mums</a></li>
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
