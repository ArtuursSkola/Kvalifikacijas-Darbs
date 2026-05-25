<?php

declare(strict_types=1);

$cfg = require __DIR__ . '/mail.example.php';

if (is_readable(__DIR__ . '/mail.local.php')) {
    $loc = require __DIR__ . '/mail.local.php';
    if (is_array($loc)) {
        foreach (['enabled', 'smtp_pass', 'smtp_user', 'from_email', 'smtp_host', 'smtp_port', 'smtp_secure', 'mailtrap_plain'] as $k) {
            if (array_key_exists($k, $loc) && $loc[$k] !== '' && $loc[$k] !== null) {
                $cfg[$k] = $loc[$k];
            }
        }
    }
}

return $cfg;
