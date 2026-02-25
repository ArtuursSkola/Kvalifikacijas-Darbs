<?php
// Stripe konfigurācija (Pay-to-List plāni) bez lielās Stripe PHP bibliotēkas

// Definējam vieglas \Stripe klases, lai nebūtu jāizmanto oficiālais SDK
namespace Stripe {
    class Stripe {
        public static $apiKey;
        public static function setApiKey($key) {
            self::$apiKey = $key;
        }
    }

    class ApiRequest {
        public static function send($method, $endpoint, $data = []) {
            $ch = curl_init('https://api.stripe.com/v1/' . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, Stripe::$apiKey . ':');
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                // Stripe requires arrays to be properly encoded like array[key]=value
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response);
            if (isset($result->error)) {
                throw new \Exception($result->error->message);
            }
            return $result;
        }
    }

    class PaymentIntent {
        public static function retrieve($id) {
            return ApiRequest::send('GET', 'payment_intents/' . urlencode($id));
        }
    }
}

namespace Stripe\Checkout {
    class Session {
        public static function create($data) {
            return \Stripe\ApiRequest::send('POST', 'checkout/sessions', $data);
        }
        public static function retrieve($id) {
            return \Stripe\ApiRequest::send('GET', 'checkout/sessions/' . urlencode($id));
        }
    }
}

namespace {
    // 2) Stripe API atslēgas (testa režīms)
    $stripeSecretKey = 'sk_test_51SAuHU75uHE3uiPXz4cfnEoQaJ0P52Bkva6O6U3EsECjOZk6yTwpWes6HMmbRavFPITEHE9LorFHcwgNGCotGFV300xZwHlYcu';
    $stripePublishableKey = 'pk_test_51SAuHU75uHE3uiPXwDEQ1sk86mvAXqY4DaX8wcXIMQ4FlbWB022MHFVIDdLYHZUKFNsFuNTRxnLsQyOaVXF1gLt3000zWAf7F3';

    // 3) Inicializē Stripe
    \Stripe\Stripe::setApiKey($stripeSecretKey);

    // 4) Bāzes URL maksājumiem (pielāgo serverim)
    const PAYMENTS_BASE_URL = 'https://kristovskis.lv/4pt/kliecis/HomeEst/payments/';

    function payments_base_url(): string {
        return PAYMENTS_BASE_URL;
    }
}
?>