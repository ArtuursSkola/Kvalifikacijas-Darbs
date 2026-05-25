<?php


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

    $stripeSecretKey = 'sk_test_51SAuHU75uHE3uiPXz4cfnEoQaJ0P52Bkva6O6U3EsECjOZk6yTwpWes6HMmbRavFPITEHE9LorFHcwgNGCotGFV300xZwHlYcu';
    $stripePublishableKey = 'pk_test_51SAuHU75uHE3uiPXwDEQ1sk86mvAXqY4DaX8wcXIMQ4FlbWB022MHFVIDdLYHZUKFNsFuNTRxnLsQyOaVXF1gLt3000zWAf7F3';


    \Stripe\Stripe::setApiKey($stripeSecretKey);

    function payments_base_url(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        return $protocol . '://' . $host . $path . '/';
    }

    function stripe_friendly_error(string $rawError): string {
        $rawErrorLower = strtolower($rawError);
        if (strpos($rawErrorLower, 'card was declined') !== false || strpos($rawErrorLower, 'card_declined') !== false) {
            return 'Maksājums noraidīts: nederīga karte.';
        }
        if (strpos($rawErrorLower, 'insufficient funds') !== false || strpos($rawErrorLower, 'insufficient_funds') !== false) {
            return 'Maksājums noraidīts: nepietiekami līdzekļi.';
        }
        if (strpos($rawErrorLower, 'expired card') !== false || strpos($rawErrorLower, 'expired_card') !== false) {
            return 'Maksājums noraidīts: kartei beidzies derīguma termiņš.';
        }
        if (strpos($rawErrorLower, 'incorrect cvc') !== false || strpos($rawErrorLower, 'incorrect_cvc') !== false) {
            return 'Maksājums noraidīts: nepareizs CVC kods.';
        }
        if (strpos($rawErrorLower, 'incorrect pin') !== false || strpos($rawErrorLower, 'incorrect_pin') !== false) {
            return 'Maksājums noraidīts: nepareizs PIN kods.';
        }
        return 'Stripe kļūda: ' . $rawError;
    }
}
?>