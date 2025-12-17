<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login/login.php');
    exit();
}
$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kļūsti par īpašnieku - HomeEstate</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #1d2733;
            --primary-light: #2c3e50;
            --accent: #30b607;
            --accent-light: #4cd964;
            --accent-dark: #259106;
            --white: #ffffff;
            --light-bg: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-400: #94a3b8;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 50px rgba(0,0,0,0.12);
            --radius-sm: 8px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        body.owner-page {
            background: var(--light-bg);
            min-height: 100vh;
        }

        /* Hero Section */
        .owner-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 50%, #1a3a1e 100%);
            padding: 160px 24px 120px;
            position: relative;
            overflow: hidden;
        }

        .owner-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(48,182,7,0.15) 0%, transparent 60%);
            animation: float 10s ease-in-out infinite;
        }

        .owner-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,215,0,0.1) 0%, transparent 50%);
            animation: float 12s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.05); }
        }

        .owner-hero__content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 2;
            color: var(--white);
        }

        .owner-hero .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: var(--white);
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .owner-hero h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }

        .owner-hero__content > p {
            font-size: 1.15rem;
            color: rgba(255,255,255,0.85);
            line-height: 1.7;
            margin-bottom: 32px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .owner-hero__bullets {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .owner-hero__bullets span {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .owner-hero__bullets span i {
            color: var(--accent-light);
            font-size: 1.1rem;
        }

        /* Model Section */
        .owner-model {
            padding: 80px 24px;
            background: var(--white);
        }

        .owner-model .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .owner-model__grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .owner-model h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .owner-model > .container > .owner-model__grid > div > p {
            color: var(--gray-600);
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .owner-list {
            list-style: none;
        }

        .owner-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            color: var(--gray-600);
            font-size: 1rem;
        }

        .owner-list li i {
            color: var(--accent);
            margin-top: 4px;
        }

        .owner-model__card {
            background: linear-gradient(135deg, var(--gray-100), var(--white));
            border-radius: var(--radius-lg);
            padding: 32px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-md);
        }

        .owner-model__card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .owner-model__card ol {
            list-style: none;
            counter-reset: step;
            margin-bottom: 20px;
        }

        .owner-model__card ol li {
            counter-increment: step;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            color: var(--gray-600);
            border-bottom: 1px solid var(--gray-200);
        }

        .owner-model__card ol li:last-child {
            border-bottom: none;
        }

        .owner-model__card ol li::before {
            content: counter(step);
            width: 32px;
            height: 32px;
            background: var(--accent);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .owner-model__card .muted {
            color: var(--gray-400);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .owner-model__card .muted::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 24 24'%3E%3Cpath d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z'/%3E%3C/svg%3E") center/contain no-repeat;
        }

        /* Plans Section */
        .owner-plans {
            padding: 100px 24px;
            background: linear-gradient(180deg, var(--light-bg) 0%, #e8f5e9 100%);
        }

        .owner-plans .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .owner-plans h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            text-align: center;
            margin-bottom: 12px;
        }

        .owner-plans__sub {
            text-align: center;
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-bottom: 60px;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            align-items: stretch;
        }

        /* Base Plan Card */
        .plan-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 0;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            border: 2px solid var(--gray-200);
        }

        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .plan-head {
            padding: 32px 28px 24px;
            text-align: center;
            position: relative;
        }

        .plan-badge {
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent);
            color: var(--white);
            padding: 6px 20px;
            border-radius: 0 0 12px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plan-name {
            display: block;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 8px;
            margin-top: 10px;
        }

        .plan-price {
            display: block;
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 8px;
        }

        .plan-period {
            display: block;
            font-size: 0.9rem;
            color: var(--gray-400);
        }

        .plan-features {
            list-style: none;
            padding: 24px 28px;
            border-top: 1px solid var(--gray-200);
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .plan-features li i {
            color: var(--accent);
            font-size: 0.9rem;
        }

        .plan-features li.muted {
            color: var(--gray-400);
        }

        .plan-features li.muted i {
            color: var(--gray-400);
        }

        .plan-btn {
            display: block;
            margin: 0 28px 28px;
            padding: 16px;
            text-align: center;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s;
        }

        .plan-btn.ghost {
            background: var(--gray-100);
            color: var(--gray-600);
            border: 2px solid var(--gray-200);
        }

        .plan-btn.ghost:hover {
            background: var(--gray-200);
            border-color: var(--gray-400);
        }

        /* ========== SILVER CARD EFFECT ========== */
        .plan-card.silver {
            border: 2px solid #c0c0c0;
            background: linear-gradient(145deg, #ffffff 0%, #f5f5f5 50%, #e8e8e8 100%);
            position: relative;
        }

        .plan-card.silver::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, 
                transparent 0%, 
                rgba(192,192,192,0.3) 25%, 
                transparent 50%, 
                rgba(192,192,192,0.2) 75%, 
                transparent 100%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .plan-card.silver:hover::before {
            opacity: 1;
        }

        .plan-card.silver:hover {
            border-color: #a8a8a8;
            box-shadow: 0 20px 60px rgba(150,150,150,0.3), 
                        0 0 40px rgba(192,192,192,0.2);
        }

        .plan-card.silver .plan-head {
            background: linear-gradient(180deg, rgba(192,192,192,0.15) 0%, transparent 100%);
        }

        .plan-card.silver .plan-badge {
            background: linear-gradient(135deg, #a8a8a8, #c0c0c0, #d8d8d8, #c0c0c0);
            background-size: 200% 200%;
            animation: silverShine 3s ease infinite;
            color: #333;
        }

        .plan-card.silver .plan-name {
            color: #666;
            font-weight: 700;
        }

        .plan-card.silver .plan-price {
            background: linear-gradient(135deg, #606060, #909090, #c0c0c0, #909090);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .plan-card.silver .plan-btn {
            background: linear-gradient(135deg, #909090, #b8b8b8, #c8c8c8);
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(150,150,150,0.4);
        }

        .plan-card.silver .plan-btn:hover {
            background: linear-gradient(135deg, #707070, #909090, #a8a8a8);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(150,150,150,0.5);
        }

        @keyframes silverShine {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ========== GOLD CARD EFFECT ========== */
        .plan-card.gold {
            border: 2px solid #d4af37;
            background: linear-gradient(145deg, #fffef5 0%, #fff9e6 50%, #fff3cd 100%);
            position: relative;
        }

        .plan-card.gold::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, 
                transparent 0%, 
                rgba(255,215,0,0.3) 25%, 
                transparent 50%, 
                rgba(255,215,0,0.2) 75%, 
                transparent 100%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .plan-card.gold:hover::before {
            opacity: 1;
        }

        .plan-card.gold:hover {
            border-color: #c9a227;
            box-shadow: 0 20px 60px rgba(212,175,55,0.35), 
                        0 0 40px rgba(255,215,0,0.25);
        }

        .plan-card.gold .plan-head {
            background: linear-gradient(180deg, rgba(255,215,0,0.12) 0%, transparent 100%);
        }

        .plan-card.gold .plan-head .plan-name {
            color: #b8860b;
            font-weight: 700;
        }

        .plan-card.gold .plan-price {
            background: linear-gradient(135deg, #b8860b, #d4af37, #ffd700, #d4af37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 200%;
            animation: goldShine 3s ease infinite;
        }

        .plan-card.gold .plan-features li i {
            color: #d4af37;
        }

        .plan-card.gold .plan-btn {
            background: linear-gradient(135deg, #b8860b, #d4af37, #f0c14b);
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(212,175,55,0.4);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .plan-card.gold .plan-btn:hover {
            background: linear-gradient(135deg, #9a7209, #b8860b, #d4af37);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.5);
        }

        @keyframes goldShine {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Gold Crown Icon */
        .plan-card.gold .plan-name::before {
            content: '👑 ';
        }

        /* CTA Strip */
        .cta-strip {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            padding: 60px 24px;
        }

        .cta-strip .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .cta-strip__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            flex-wrap: wrap;
        }

        .cta-strip__inner h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .cta-strip__inner p {
            color: rgba(255,255,255,0.8);
        }

        .btn-owner {
            background: var(--accent);
            color: var(--white);
            padding: 16px 36px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(48,182,7,0.4);
        }

        .btn-owner:hover {
            background: var(--accent-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(48,182,7,0.5);
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: rgba(255,255,255,0.8);
            padding: 60px 24px 24px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 48px;
            max-width: 1000px;
            margin: 0 auto 40px;
        }

        .footer-col h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 16px;
        }

        .footer-col p {
            line-height: 1.7;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s;
        }

        .footer-col ul a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .plans-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 0 auto;
            }
            .owner-model__grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .owner-hero { padding: 120px 20px 80px; }
            .owner-hero__bullets { flex-direction: column; gap: 16px; }
            .cta-strip__inner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body class="owner-page">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="homes.php">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="owner.php" class="active">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="newhome.php">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="about.php">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: bold; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login/logout.php" class="btn-register" style="background-color: #c0392b;">Iziet</a>
            <?php else: ?>
                <a href="login/login.php" class="btn-login">Ielogoties</a>
                <a href="login/register.php" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <header class="owner-hero">
        <div class="owner-hero__content">
            <p class="badge-pill">Pay-to-List modelis</p>
            <h1>Publicē savus īpašumus. Saņem rezultātus.</h1>
            <p>Tu maksā par sludinājuma publicēšanu, nevis par darījumu. Mēs nopelnām, kad tava sludinājuma vieta ir aktīva.</p>
            <div class="owner-hero__bullets">
                <span><i class="fas fa-bullhorn"></i> Prioritāte meklēšanā</span>
                <span><i class="fas fa-id-card"></i> Verificēta īpašnieka badge</span>
                <span><i class="fas fa-camera"></i> Līdz 10+ fotogrāfijām</span>
            </div>
        </div>
    </header>

    <section class="owner-model">
        <div class="container">
            <div class="owner-model__grid">
                <div>
                    <h2>Kāpēc Pay-to-List?</h2>
                    <p>Saņem ieņēmumus uzreiz — pirms īrnieks vai pircējs pat piesaka vizīti. Mēs neiekasējam komisiju no taviem darījumiem; tu maksā tikai par redzamību.</p>
                    <ul class="owner-list">
                        <li><i class="fas fa-check"></i> Ieņēmumi pirms darījuma — tu kontrolē procesu.</li>
                        <li><i class="fas fa-check"></i> Redzamība meklētājos un tematiskajos blokos.</li>
                        <li><i class="fas fa-check"></i> Lietotāji redz tevi kā uzticamu īpašnieku.</li>
                    </ul>
                </div>
                <div class="owner-model__card">
                    <h3>Darbojas vienkārši:</h3>
                    <ol>
                        <li>Izvēlies plānu.</li>
                        <li>Apstiprini maksājumu ar Stripe.</li>
                        <li>Publicē un pārvaldi savus sludinājumus.</li>
                    </ol>
                    <p class="muted">Maksājums notiek caur Stripe drošo checkout.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="owner-plans" id="plans">
        <div class="container">
            <h2>Plāni īpašniekiem</h2>
            <p class="owner-plans__sub">Izvēlies sev ērtāko Pay-to-List plānu. Cenas bez slēptām komisijām.</p>
            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-head">
                        <span class="plan-name">Free</span>
                        <span class="plan-price">€0</span>
                        <span class="plan-period">1 aktīvs sludinājums</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 1 aktīvs sludinājums</li>
                        <li><i class="fas fa-check"></i> Līdz 3 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta publikācija</li>
                        <li class="muted"><i class="fas fa-minus"></i> Nav prioritātes meklēšanā</li>
                    </ul>
                    <a class="plan-btn ghost" href="#">Sākt bez maksas</a>
                </div>

                <div class="plan-card silver">
                    <div class="plan-head">
                        <span class="plan-badge">Visbiežāk izvēlas</span>
                        <span class="plan-name">Silver</span>
                        <span class="plan-price">€9.99</span>
                        <span class="plan-period">mēnesī · 5 sludinājumi</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> 5 aktīvi sludinājumi</li>
                        <li><i class="fas fa-check"></i> Līdz 10 fotogrāfijām</li>
                        <li><i class="fas fa-check"></i> Standarta meklēšanas ranga līmenis</li>
                        <li><i class="fas fa-check"></i> Īpašnieka badge profilā</li>
                    </ul>
                    <a class="plan-btn" href="payments/checkout.php?plan=Silver&price=999">Pirkt ar Stripe</a>
                </div>

                <div class="plan-card gold">
                    <div class="plan-head">
                        <span class="plan-name">Gold</span>
                        <span class="plan-price">€29.99</span>
                        <span class="plan-period">mēnesī · Bez limita</span>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Neierobežoti sludinājumi</li>
                        <li><i class="fas fa-check"></i> 20+ fotogrāfijas</li>
                        <li><i class="fas fa-check"></i> Featured meklēšanā (top pozīcijas)</li>
                        <li><i class="fas fa-check"></i> "Verified Owner" badge</li>
                    </ul>
                    <a class="plan-btn" href="payments/checkout.php?plan=Gold&price=2999">Pirkt ar Stripe</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-strip">
        <div class="container">
            <div class="cta-strip__inner">
                <div>
                    <h3>Esi gatavs publicēt?</h3>
                    <p>Aktivizē plānu un pievieno sludinājumu minūtēs.</p>
                </div>
                <a class="btn-owner" href="#plans">Izvēlēties plānu</a>
            </div>
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
