<?php
// Stripe konfigurācija (Pay-to-List plāni)
// Ja izmanto Composer, pārliecinies, ka vendor/autoload.php ir klāt. Pretējā gadījumā iekopē Stripe PHP SDK mapē payments/stripe-php/.

// 1) Autoload Stripe SDK
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',                 // composer install in payments/
    __DIR__ . '/stripe-php/init.php',                 // manual SDK folder "stripe-php"
    __DIR__ . '/stripe-php-master/init.php',          // manual SDK folder "stripe-php-master"
    dirname(__DIR__) . '/stripe-php/init.php',        // fallback one level up
    dirname(__DIR__) . '/stripe-php-master/init.php'  // fallback one level up
];

$loaded = false;
foreach ($autoloadPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die('Stripe PHP SDK nav atrasts. Iekopē Stripe PHP mapē payments/stripe-php (vai stripe-php-master) vai palaid composer install payments/ direktorijā.');
}

// 2) Stripe API atslēgas (testa režīms)
$stripeSecretKey = 'sk_test_51SAuHU75uHE3uiPXz4cfnEoQaJ0P52Bkva6O6U3EsECjOZk6yTwpWes6HMmbRavFPITEHE9LorFHcwgNGCotGFV300xZwHlYcu';
$stripePublishableKey = 'pk_test_51SAuHU75uHE3uiPXwDEQ1sk86mvAXqY4DaX8wcXIMQ4FlbWB022MHFVIDdLYHZUKFNsFuNTRxnLsQyOaVXF1gLt3000zWAf7F3';

// 3) Inicializē Stripe
\Stripe\Stripe::setApiKey($stripeSecretKey);

// 4) Bāzes URL maksājumiem (pielāgo serverim)
// Ja esi uz kristovskis.lv, šis ir pareizais ceļš; ja maini domēnu/ceļu, nomaini šo konstanti.
const PAYMENTS_BASE_URL = 'https://kristovskis.lv/4pt/kliecis/HomeEst/payments/';

function payments_base_url(): string {
    return PAYMENTS_BASE_URL;
}

?>