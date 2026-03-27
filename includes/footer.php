<?php
// includes/footer.php
require_once __DIR__ . '/../routes/main.php';
?>
    <footer>
        <div class="footer-content">
            <div class="footer-col">
                <h3>Home<span>Estate</span></h3>
                <p>Mēs palīdzam cilvēkiem atrast viņu sapņu mājokļus kopš 2024. gada. Uzticama platforma visiem nekustamā īpašuma darījumiem.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Ātrās saites</h4>
                <ul>
                    <li><a href="<?php echo main_route('home'); ?>">Sākums</a></li>
                    <li><a href="<?php echo main_route('property.list'); ?>">Meklēt īpašumu</a></li>
                    <li><a href="<?php echo main_route('about'); ?>">Par mums</a></li>
                    <li><a href="<?php echo main_route('owner'); ?>">Īpašniekiem</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Atbalsts</h4>
                <ul>
                    <li><a href="#">Biežāk uzdotie jautājumi</a></li>
                    <li><a href="#">Privātuma politika</a></li>
                    <li><a href="#">Lietošanas noteikumi</a></li>
                    <li><a href="#">Kontakti</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Kontakti</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@homeestate.lv</li>
                    <li><i class="fas fa-phone"></i> +371 2000 0000</li>
                    <li><i class="fas fa-map-marker-alt"></i> Rīga, Latvija</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> HomeEstate. Visas tiesības aizsargātas.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script src="<?php echo asset_path('script.js'); ?>"></script>
</body>
</html>