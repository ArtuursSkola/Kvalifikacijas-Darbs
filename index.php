<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/routes/main.php';

$pageTitle = 'HomeEstate - Tavs mājoklis';
$extraStyles = ['index'];
include 'includes/header.php';


$newestHomes = [];
$sql = "SELECT id, title, city, location_text, type, price, area, bedrooms, bathrooms, main_image 
        FROM est_homes WHERE status = 'Aktivs' ORDER BY created_at DESC LIMIT 3";
$result = $savienojums->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $newestHomes[] = $row;
    }
}
$activeHomesCount = 0;
$resActive = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status = 'Aktivs'");
if ($resActive && $row = $resActive->fetch_row()) $activeHomesCount = (int)$row[0];

$totalUsersCount = 0;
$resUsers = $savienojums->query("SELECT COUNT(*) FROM est_lietotaji");
if ($resUsers && $row = $resUsers->fetch_row()) $totalUsersCount = (int)$row[0];

$dealsCount = 0;
$resDeals = $savienojums->query("SELECT COUNT(*) FROM est_homes WHERE status = 'Pardots'");
if ($resDeals && $row = $resDeals->fetch_row()) $dealsCount = (int)$row[0];
?>

    <header class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-award"></i>
                Nr.1 Nekustamā īpašuma platforma Latvijā
            </div>
            <h1>Atrodi savu <span class="highlight">sapņu mājokli</span> viegli un ātri</h1>
            <p>Ērts un drošs veids, kā īrēt vai iegādāties nekustamo īpašumu. Tūkstošiem īpašumu gaida tevi!</p>
            
            <form class="search-bar" action="<?php echo main_route('property.list'); ?>" method="GET">
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
                    <div class="number"><?php echo number_format($activeHomesCount, 0, ',', ' '); ?><span>+</span></div>
                    <div class="label">Aktīvi sludinājumi</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($totalUsersCount, 0, ',', ' '); ?><span>+</span></div>
                    <div class="label">Apmierināti klienti</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo number_format($dealsCount, 0, ',', ' '); ?><span>+</span></div>
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
                                <a href="<?php echo main_route('property.show', ['id' => $home['id']]); ?>" class="btn-view">Skatīt <i class="fas fa-arrow-right"></i></a>
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

    <section class ="help-section">
    <h2>Vai ir kādi jautājumi, vai vajadzīga palīdzība?</h2>
        <p>Mēs atbildam uz visiem jūsu dotajiem jautājumiem un jums ir iespēja apskatīt biežāk uzdotos jautājumus</p>
        <a href="" class="btn-cta">
            <i class="fa-solid fa-question"></i>
                Palīdzības centrs
        </a>
    </section>
    
    <section class="cta-section">
        <h2>Gatavs sākt savu meklēšanu?</h2>
        <p>Pievienojies tūkstošiem apmierinātu klientu un atrodi savu ideālo mājokli jau šodien.</p>
        <a href="<?php echo main_route('property.list'); ?>" class="btn-cta">
            <i class="fas fa-search"></i>
            Sākt meklēšanu
        </a>
    </section>


    <?php include 'includes/footer.php'; ?>