<?php
session_start();
$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meklēt īpašumus - HomeEstate</title>
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

        body.homes-page {
            background: var(--light-bg);
            min-height: 100vh;
        }

        /* Navbar Override */
        .navbar.scrolled {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
        }

        /* Hero Section */
        .homes-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 50%, #1a5a2e 100%);
            padding: 140px 24px 100px;
            position: relative;
            overflow: hidden;
        }

        .homes-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(48,182,7,0.15) 0%, transparent 60%);
            animation: pulse 8s ease-in-out infinite;
        }

        .homes-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 50%);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .homes-hero__inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .homes-hero__text {
            color: var(--white);
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .homes-hero__text h1 {
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 18px;
        }

        .homes-hero__text > p {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.85);
            line-height: 1.7;
            margin-bottom: 28px;
            max-width: 500px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-hero-search {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--accent);
            color: var(--white);
            padding: 16px 32px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(48,182,7,0.4);
        }

        .btn-hero-search:hover {
            background: var(--accent-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(48,182,7,0.5);
        }

        .hero-note {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-note i { color: var(--accent-light); }

        /* Stats Cards */
        .homes-hero__card {
            display: grid;
            gap: 16px;
        }

        .hero-metric {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: all 0.3s;
        }

        .hero-metric:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(8px);
        }

        .metric-label {
            display: block;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 8px;
        }

        .metric-value {
            display: block;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-light);
            line-height: 1;
        }

        .metric-sub {
            display: block;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            margin-top: 6px;
        }

        /* Search Section */
        .search-listings-section {
            padding: 60px 24px 100px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Filter Shell */
        .filter-shell {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-md);
            margin-bottom: 40px;
            margin-top: -60px;
            position: relative;
            z-index: 10;
            border: 1px solid var(--gray-200);
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .filter-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-header h3 i {
            color: var(--accent);
        }

        .filter-count {
            background: rgba(48,182,7,0.1);
            color: var(--accent);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-600);
        }

        .filter-group select,
        .filter-group input {
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            color: var(--gray-800);
            background: var(--white);
            transition: all 0.2s;
            outline: none;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(48,182,7,0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--white);
            border: none;
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(48,182,7,0.3);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(48,182,7,0.4);
        }

        .filter-hint {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
            font-size: 0.85rem;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-hint i { color: var(--accent); }

        /* Results Header */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .results-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .results-header h2 span {
            color: var(--accent);
        }

        .view-toggle {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            width: 44px;
            height: 44px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            background: var(--white);
            color: var(--gray-400);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-btn.active,
        .view-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(48,182,7,0.05);
        }

        /* Listing Grid */
        .listing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
        }

        /* Property Cards */
        .property-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .property-image {
            position: relative;
            height: 240px;
            overflow: hidden;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .property-card:hover .property-image img {
            transform: scale(1.1);
        }

        .property-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .property-badge.rent {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: var(--white);
        }

        .property-badge.sale {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--white);
        }

        .property-favorite {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1.1rem;
        }

        .property-favorite:hover {
            transform: scale(1.15);
            background: var(--white);
        }

        .property-favorite.active i {
            font-weight: 900;
        }

        .property-details {
            padding: 24px;
        }

        .property-details h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .property-location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-600);
            font-size: 0.95rem;
            margin-bottom: 16px;
        }

        .property-location i { color: var(--accent); }

        .property-features {
            display: flex;
            gap: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 20px;
        }

        .property-features span {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .property-features span i {
            color: var(--accent);
            font-size: 1rem;
        }

        .property-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .property-price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--accent);
        }

        .property-price small {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--gray-400);
        }

        .btn-view-property {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-view-property:hover {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            transform: translateY(-2px);
        }

        /* Empty State */
        .search-empty {
            text-align: center;
            padding: 80px 24px;
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--gray-200);
        }

        .search-empty i {
            font-size: 4rem;
            color: var(--gray-200);
            margin-bottom: 20px;
        }

        .search-empty h3 {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .search-empty p {
            color: var(--gray-400);
        }

        /* Modal */
        .listing-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .listing-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
        }

        .listing-modal__content {
            position: relative;
            background: var(--white);
            border-radius: var(--radius-lg);
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 44px;
            height: 44px;
            background: var(--white);
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            color: var(--gray-600);
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .modal-close:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
        }

        .modal-image {
            position: relative;
            height: 100%;
            min-height: 400px;
        }

        .modal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-image .badge {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }

        .modal-info {
            padding: 32px;
            display: flex;
            flex-direction: column;
        }

        .modal-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .modal-info .location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            margin-bottom: 20px;
        }

        .modal-info .location i { color: var(--accent); }

        .modal-info .features {
            display: flex;
            gap: 24px;
            padding: 20px 0;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 20px;
        }

        .modal-info .features span {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
        }

        .modal-info .features span i { color: var(--accent); }

        .modal-info .description {
            color: var(--gray-600);
            line-height: 1.7;
            flex: 1;
        }

        .modal-price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .modal-price-row .price {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--accent);
        }

        .btn-contact {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--white);
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(48,182,7,0.4);
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
            max-width: 1200px;
            margin: 0 auto 48px;
        }

        .footer-col h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 20px;
        }

        .footer-col h3 span { color: var(--accent); }

        .footer-col p { line-height: 1.8; }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 20px;
        }

        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 12px; }

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
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .homes-hero__inner { grid-template-columns: 1fr; gap: 40px; }
            .homes-hero__card { grid-template-columns: repeat(3, 1fr); }
            .modal-body { grid-template-columns: 1fr; }
            .modal-image { min-height: 250px; }
            .footer-content { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .homes-hero { padding: 120px 20px 80px; }
            .homes-hero__card { grid-template-columns: 1fr; }
            .filters { grid-template-columns: 1fr; }
            .listing-grid { grid-template-columns: 1fr; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
            .footer-social { justify-content: center; }
            .results-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body class="homes-page">
    <nav class="navbar scrolled">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="homes.php" class="active">Meklēt īpašumu</a></li>
            <?php if ($isOwner): ?>
                <li><a href="owner.php">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <li><a href="newhome.php">Izveidot sludinājumu</a></li>
            <?php endif; ?>
            <li><a href="about.php">Par mums</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 15px; font-weight: 600; color: var(--primary);">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="login/logout.php" class="btn-register" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">Iziet</a>
            <?php else: ?>
                <a href="login/login.php" class="btn-login">Ielogoties</a>
                <a href="login/register.php" class="btn-register">Reģistrēties</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <header class="homes-hero">
        <div class="homes-hero__inner">
            <div class="homes-hero__text">
                <p class="badge-pill"><i class="fas fa-search"></i> Meklē un filtrē</p>
                <h1>Atrodi sev piemērotu īpašumu</h1>
                <p>Izvēlies pilsētu, tipu un budžetu. Plašs piedāvājumu klāsts ar detalizētiem aprakstiem un ātru saziņu ar īpašniekiem.</p>
                <div class="hero-actions">
                    <button id="filter-hero" class="btn-hero-search" type="button">
                        <i class="fas fa-filter"></i>
                        Sākt meklēšanu
                    </button>
                    <span class="hero-note"><i class="fas fa-shield-alt"></i> Pārbaudīti sludinājumi</span>
                </div>
            </div>
            <div class="homes-hero__card">
                <div class="hero-metric">
                    <span class="metric-label">Pārbaudīti sludinājumi</span>
                    <span class="metric-value">1,250+</span>
                    <span class="metric-sub">Aktīvi piedāvājumi</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Pilsētas</span>
                    <span class="metric-value">15+</span>
                    <span class="metric-sub">Visā Latvijā</span>
                </div>
                <div class="hero-metric">
                    <span class="metric-label">Veiksmīgi darījumi</span>
                    <span class="metric-value">380+</span>
                    <span class="metric-sub">Apmierināti klienti</span>
                </div>
            </div>
        </div>
    </header>

    <section class="search-listings-section">
        <div class="filter-shell">
            <div class="filter-header">
                <h3><i class="fas fa-sliders-h"></i> Filtrēt īpašumus</h3>
                <span class="filter-count" id="results-count">0 rezultāti</span>
            </div>
            <div class="filters">
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Pilsēta</label>
                    <select id="filter-city">
                        <option value="">Visas pilsētas</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-home"></i> Tips</label>
                    <select id="filter-type">
                        <option value="">Visi tipi</option>
                        <option value="rent">Īrēt</option>
                        <option value="buy">Pirkt</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-euro-sign"></i> Maks. cena</label>
                    <input type="number" id="filter-price" placeholder="Piem., 200000">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-bed"></i> Guļamistabas</label>
                    <input type="number" id="filter-beds" min="0" max="10" step="1" placeholder="Min. skaits">
                </div>
                <button id="filter-apply" class="btn-filter" type="button">
                    <i class="fas fa-search"></i>
                    Meklēt
                </button>
            </div>
            <div class="filter-hint"><i class="fas fa-info-circle"></i> Rezultāti tiek atjaunoti uzreiz pēc filtra piemērošanas.</div>
        </div>

        <div class="results-header">
            <h2>Pieejamie <span>īpašumi</span></h2>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Režģis"><i class="fas fa-th-large"></i></button>
                <button class="view-btn" data-view="list" title="Saraksts"><i class="fas fa-list"></i></button>
            </div>
        </div>

        <div id="homes-results" class="listing-grid"></div>
        
        <div id="homes-empty" class="search-empty" style="display:none;">
            <i class="fas fa-home"></i>
            <h3>Nav atrasto īpašumu</h3>
            <p>Mēģiniet mainīt filtrus vai meklēt citā pilsētā.</p>
        </div>
    </section>

    <div id="homes-modal" class="listing-modal" style="display:none;">
        <div class="listing-modal__backdrop"></div>
        <div class="listing-modal__content">
            <button class="modal-close" id="homes-modal-close" aria-label="Aizvērt">&times;</button>
            <div class="modal-body">
                <div class="modal-image">
                    <img id="homes-modal-img" src="" alt="">
                    <span id="homes-modal-badge" class="badge"></span>
                </div>
                <div class="modal-info">
                    <h3 id="homes-modal-title"></h3>
                    <p id="homes-modal-location" class="location"><i class="fas fa-map-marker-alt"></i> <span></span></p>
                    <div class="features">
                        <span id="homes-modal-beds"><i class="fas fa-bed"></i> </span>
                        <span id="homes-modal-size"><i class="fas fa-ruler-combined"></i> </span>
                        <span><i class="fas fa-bath"></i> 1 vannas</span>
                    </div>
                    <p id="homes-modal-desc" class="description"></p>
                    <div class="modal-price-row">
                        <span id="homes-modal-price" class="price"></span>
                        <a id="homes-modal-contact" class="btn-contact" href="#">
                            <i class="fas fa-envelope"></i>
                            Sazināties
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <li><i class="fas fa-envelope" style="margin-right: 8px; color: #30b607;"></i> info@homeestate.lv</li>
                        <li><i class="fas fa-phone" style="margin-right: 8px; color: #30b607;"></i> +371 20000000</li>
                        <li><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #30b607;"></i> Rīga, Latvija</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HomeEstate. Visas tiesības aizsargātas.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
