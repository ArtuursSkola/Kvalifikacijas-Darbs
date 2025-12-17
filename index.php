<?php 
session_start();
require_once 'con_db.php';

$isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks';
$plan = $_SESSION['plan'] ?? '';
$canCreate = $isOwner && in_array($plan, ['Silver', 'Gold']);

// Fetch 3 newest PUBLISHED homes from database (drafts need admin approval)
$newestHomes = [];
$sql = "SELECT id, title, city, location_text, type, price, area, bedrooms, bathrooms, main_image 
        FROM est_homes WHERE status = 'published' ORDER BY created_at DESC LIMIT 3";
$result = $savienojums->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $newestHomes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeEstate - Tavs mājoklis</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Enhanced Homepage Styles */
        .hero {
            min-height: 100vh;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(29,39,51,0.7) 0%, rgba(48,182,7,0.3) 100%);
            z-index: 1;
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
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.2);
            animation: fadeInDown 0.8s ease;
        }
        
        .hero-badge i { color: #4cd964; }
        
        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero h1 .highlight {
            color: #4cd964;
            position: relative;
        }
        
        .hero > .hero-content > p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        /* Enhanced Search Bar */
        .search-bar {
            background: rgba(255,255,255,0.98);
            padding: 20px 24px;
            border-radius: 20px;
            display: flex;
            gap: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 950px;
            margin: 0 auto;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .input-group {
            flex: 1;
            display: flex;
            align-items: center;
            background: #f8fafc;
            padding: 14px 18px;
            border-radius: 12px;
            min-width: 180px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .input-group:focus-within {
            border-color: #30b607;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(48,182,7,0.1);
        }
        
        .input-group i {
            color: #30b607;
            font-size: 1.1rem;
            margin-right: 12px;
        }
        
        .input-group input,
        .input-group select {
            border: none;
            background: transparent;
            font-size: 1rem;
            color: #1d2733;
            width: 100%;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .input-group select {
            cursor: pointer;
        }
        
        .search-bar .btn-search {
            background: linear-gradient(135deg, #30b607, #259106);
            color: #fff;
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(48,182,7,0.4);
        }
        
        .search-bar .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(48,182,7,0.5);
        }
        
        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 50px;
            animation: fadeInUp 0.8s ease 0.8s both;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }
        
        .stat-item .number span {
            color: #4cd964;
        }
        
        .stat-item .label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            margin-top: 6px;
        }
        
        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
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
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Enhanced Section Title */
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-label {
            display: inline-block;
            background: rgba(48,182,7,0.1);
            color: #30b607;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }
        
        .section-title-new {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            color: #1d2733;
            margin-bottom: 12px;
        }
        
        .section-subtitle {
            font-size: 1.05rem;
            color: #6b7a8f;
            max-width: 550px;
            margin: 0 auto;
        }
        
        /* Enhanced Listings Section */
        .listings {
            padding: 100px 20px;
            background: #f8fafc;
        }
        
        .listing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Enhanced Cards */
        .card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        }
        
        .card-image {
            position: relative;
            height: 240px;
            overflow: hidden;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .card:hover .card-image img {
            transform: scale(1.1);
        }
        
        .card-image .badge {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.rent {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
        }
        
        .badge.sale {
            background: linear-gradient(135deg, #30b607, #259106);
            color: #fff;
        }
        
        .card-image .favorite-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e74c3c;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }
        
        .card-image .favorite-btn:hover {
            transform: scale(1.1);
            background: #fff;
        }
        
        .card-details {
            padding: 24px;
        }
        
        .card-details h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1d2733;
            margin-bottom: 10px;
        }
        
        .card-details .location {
            color: #6b7a8f;
            font-size: 0.95rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .card-details .location i {
            color: #30b607;
        }
        
        .card-details .features {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-details .features span {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7a8f;
            font-size: 0.9rem;
        }
        
        .card-details .features span i {
            color: #30b607;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price-row .price {
            font-size: 1.4rem;
            font-weight: 800;
            color: #30b607;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #1d2733, #2c3e50);
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #30b607, #259106);
            transform: translateY(-2px);
        }
        
        /* Enhanced Features Section */
        .features-section {
            padding: 100px 20px;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }
        
        .features-section .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-box {
            background: #fff;
            padding: 40px 32px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            transition: all 0.4s;
        }
        
        .feature-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border-color: #30b607;
        }
        
        .feature-box i {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(48,182,7,0.1), rgba(48,182,7,0.05));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #30b607;
            margin: 0 auto 24px;
            transition: all 0.3s;
        }
        
        .feature-box:hover i {
            background: linear-gradient(135deg, #30b607, #4cd964);
            color: #fff;
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-box h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1d2733;
            margin-bottom: 12px;
        }
        
        .feature-box p {
            color: #6b7a8f;
            line-height: 1.7;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #30b607, #259106);
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .cta-section h2 {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
        }
        
        .cta-section p {
            font-size: 1.15rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 32px;
            position: relative;
            z-index: 2;
        }
        
        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            color: #30b607;
            padding: 18px 40px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }
        
        .btn-cta:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }
        
        /* Enhanced Footer */
        footer {
            background: #1d2733;
            color: rgba(255,255,255,0.8);
            padding: 80px 20px 24px;
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
            color: #fff;
            margin-bottom: 20px;
        }
        
        .footer-col h3 span { color: #30b607; }
        
        .footer-col p {
            line-height: 1.8;
        }
        
        .footer-col h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
        }
        
        .footer-col ul li {
            margin-bottom: 12px;
        }
        
        .footer-col ul a {
            color: rgba(255,255,255,0.7);
            transition: all 0.2s;
        }
        
        .footer-col ul a:hover {
            color: #30b607;
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
            color: #fff;
            transition: all 0.3s;
        }
        
        .footer-social a:hover {
            background: #30b607;
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
            .footer-content { grid-template-columns: repeat(2, 1fr); }
            .stats-bar { gap: 40px; }
        }
        
        @media (max-width: 768px) {
            .stats-bar { 
                flex-direction: column; 
                gap: 24px;
            }
            .stat-item .number { font-size: 2rem; }
            .search-bar { flex-direction: column; }
            .input-group { min-width: 100%; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
            .footer-social { justify-content: center; }
            .listing-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="#" class="active">Sākums</a></li>
            <li><a href="homes.php">Meklēt īpašumu</a></li>
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
                <span style="margin-right: 15px; font-weight: 600; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
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

    <header class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-award"></i>
                Nr.1 Nekustamā īpašuma platforma Latvijā
            </div>
            <h1>Atrodi savu <span class="highlight">sapņu mājokli</span> viegli un ātri</h1>
            <p>Ērts un drošs veids, kā īrēt vai iegādāties nekustamo īpašumu. Tūkstošiem īpašumu gaida tevi!</p>
            
            <form class="search-bar" action="homes.php" method="GET">
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" name="city" placeholder="Pilsēta vai rajons">
                </div>
                <div class="input-group">
                    <i class="fas fa-home"></i>
                    <select name="type">
                        <option value="">Visi tipi</option>
                        <option value="buy">Pirkt</option>
                        <option value="rent">Īrēt</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fas fa-euro-sign"></i>
                    <input type="number" name="max_price" placeholder="Maks. cena">
                </div>
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    Meklēt
                </button>
            </form>
            
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="number">1,250<span>+</span></div>
                    <div class="label">Aktīvi sludinājumi</div>
                </div>
                <div class="stat-item">
                    <div class="number">2,700<span>+</span></div>
                    <div class="label">Apmierināti klienti</div>
                </div>
                <div class="stat-item">
                    <div class="number">380<span>+</span></div>
                    <div class="label">Veiksmīgi darījumi</div>
                </div>
            </div>
        </div>
        
        <div class="scroll-indicator">
            <span>Ritini uz leju</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </header>

    <section class="listings">
        <div class="container"> 
            <div class="section-header">
                <span class="section-label">Jaunākie piedāvājumi</span>
                <h2 class="section-title-new">Jaunākie īpašumi</h2>
                <p class="section-subtitle">Izpēti jaunākos piedāvājumus un atrodi savu nākamo mājvietu.</p>
            </div>
            
            <div class="listing-grid">
                <?php if (!empty($newestHomes)): ?>
                    <?php foreach ($newestHomes as $home): 
                        $isRent = $home['type'] === 'rent';
                        $priceDisplay = $isRent 
                            ? number_format($home['price'], 0, ',', ' ') . ' € / mēn'
                            : number_format($home['price'], 0, ',', ' ') . ' €';
                        $badgeClass = $isRent ? 'rent' : 'sale';
                        $badgeText = $isRent ? 'Izīrē' : 'Pārdod';
                        $image = $home['main_image'] ?: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=500&q=60';
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($home['title']); ?>">
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                            <button class="favorite-btn"><i class="far fa-heart"></i></button>
                        </div>
                        <div class="card-details">
                            <h3><?php echo htmlspecialchars($home['title']); ?></h3>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($home['city'] . ', ' . $home['location_text']); ?></p>
                            <div class="features">
                                <span><i class="fas fa-bed"></i> <?php echo (int)$home['bedrooms']; ?> guļamist.</span>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo (int)$home['area']; ?> m²</span>
                                <span><i class="fas fa-bath"></i> <?php echo (int)$home['bathrooms']; ?> vannas</span>
                            </div>
                            <div class="price-row">
                                <span class="price"><?php echo $priceDisplay; ?></span>
                                <a href="home.php?id=<?php echo $home['id']; ?>" class="btn-view">Skatīt <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1; color: #6b7a8f;">Nav pieejamu īpašumu.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="feature-box">
                <i class="fas fa-shield-alt"></i>
                <h3>Droši darījumi</h3>
                <p>Mēs garantējam drošu vidi un pārbaudītus lietotājus. Katrs sludinājums tiek rūpīgi pārbaudīts.</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-search"></i>
                <h3>Ērta meklēšana</h3>
                <p>Plašas filtrēšanas iespējas un viegli lietojams interfeiss, lai atrastu tieši to, ko meklē.</p>
            </div>
            <div class="feature-box">
                <i class="fas fa-headset"></i>
                <h3>Atbalsts 24/7</h3>
                <p>Mūsu profesionālā komanda ir gatava palīdzēt jebkurā laikā un atbildēt uz jautājumiem.</p>
            </div>
        </div>
    </section>
    
    <section class="cta-section">
        <h2>Gatavs sākt savu meklēšanu?</h2>
        <p>Pievienojies tūkstošiem apmierinātu klientu un atrodi savu ideālo mājokli jau šodien.</p>
        <a href="homes.php" class="btn-cta">
            <i class="fas fa-search"></i>
            Sākt meklēšanu
        </a>
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