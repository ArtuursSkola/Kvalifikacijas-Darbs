<?php session_start(); ?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeEstate - Tavs mājoklis</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php $isOwner = isset($_SESSION['role']) && $_SESSION['role'] === 'ipasnieks'; ?>

    <nav class="navbar" id="navbar">
        <div class="logo">Home<span>Estate</span></div>
        <ul class="nav-links">
            <li><a href="#" class="active">Sākums</a></li>
            <li><a href="#">Meklēt īpašumu</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="owner.php">Kļūsti par īpašnieku</a></li>
            <?php endif; ?>
            <li><a href="about.php">Par mums</a></li>
        </ul>
<div class="auth-buttons">
    <?php if (isset($_SESSION['user_id'])): ?>
        <span style="margin-right: 15px; font-weight: bold; color: inherit;">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <a href="logout.php" class="btn-register" style="background-color: #c0392b;">Iziet</a>
    <?php else: ?>
        <a href="login.php" class="btn-login">Ielogoties</a>
        <a href="register.php" class="btn-register">Reģistrēties</a>
    <?php endif; ?>
</div>
        <div class="hamburger">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-content fade-in">
            <h1>Atrodi savu sapņu mājokli</h1>
            <p>Ērts un drošs veids, kā īrēt vai iegādāties nekustamo īpašumu.</p>
            
            <form class="search-bar">
                <div class="input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" placeholder="Pilsēta vai rajons">
                </div>
                <div class="input-group">
                    <i class="fas fa-home"></i>
                    <select>
                        <option value="buy">Pirkt</option>
                        <option value="rent">Īrēt</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fas fa-euro-sign"></i>
                    <input type="number" placeholder="Maks. cena">
                </div>
                <button type="submit" class="btn-search">Meklēt</button>
            </form>
        </div>
    </header>

    <section class="listings container">
        <h2 class="section-title">Populārākie īpašumi</h2>
        <div class="listing-grid">
            
            <div class="card fade-in">
                <div class="card-image">
                    <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="Dzīvoklis">
                    <span class="badge rent">Izīrē</span>
                </div>
                <div class="card-details">
                    <h3>Moderns dzīvoklis centrā</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> Rīga, Brīvības iela</p>
                    <div class="features">
                        <span><i class="fas fa-bed"></i> 2 guļamist.</span>
                        <span><i class="fas fa-ruler-combined"></i> 55 m²</span>
                    </div>
                    <div class="price-row">
                        <span class="price">450 € / mēn</span>
                        <a href="#" class="btn-view">Skatīt</a>
                    </div>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-image">
                    <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="Māja">
                    <span class="badge sale">Pārdod</span>
                </div>
                <div class="card-details">
                    <h3>Ģimenes māja Pierīgā</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> Mārupe, Ziedu iela</p>
                    <div class="features">
                        <span><i class="fas fa-bed"></i> 4 guļamist.</span>
                        <span><i class="fas fa-ruler-combined"></i> 180 m²</span>
                    </div>
                    <div class="price-row">
                        <span class="price">210 000 €</span>
                        <a href="#" class="btn-view">Skatīt</a>
                    </div>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-image">
                    <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="Loft">
                    <span class="badge rent">Izīrē</span>
                </div>
                <div class="card-details">
                    <h3>Lofta tipa studija</h3>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> Liepāja, Jūras iela</p>
                    <div class="features">
                        <span><i class="fas fa-bed"></i> 1 guļamist.</span>
                        <span><i class="fas fa-ruler-combined"></i> 40 m²</span>
                    </div>
                    <div class="price-row">
                        <span class="price">300 € / mēn</span>
                        <a href="#" class="btn-view">Skatīt</a>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="feature-box fade-in">
                <i class="fas fa-shield-alt"></i>
                <h3>Droši darījumi</h3>
                <p>Mēs garantējam drošu vidi un pārbaudītus lietotājus.</p>
            </div>
            <div class="feature-box fade-in">
                <i class="fas fa-search"></i>
                <h3>Ērta meklēšana</h3>
                <p>Plašas filtrēšanas iespējas, lai atrastu tieši to, ko meklē.</p>
            </div>
            <div class="feature-box fade-in">
                <i class="fas fa-headset"></i>
                <h3>Atbalsts 24/7</h3>
                <p>Mūsu komanda ir gatava palīdzēt jebkurā laikā.</p>
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
                    <li><a href="#">Par mums</a></li>
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