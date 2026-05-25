<?php


return [
    'enabled' => true,

    'smtp_host' => 'sandbox.smtp.mailtrap.io',
    'smtp_port' => 2525,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,

    'smtp_user' => getenv('MAILTRAP_USER') ?: 'b321bb184f4645',
    'smtp_pass' => getenv('MAILTRAP_PASSWORD') ?: '',

    'from_email' => 'noreply@homeestate.com',
    'from_name' => 'HomeEstate',

    'mailtrap_plain' => false,
    // 'smtp_debug' => 0,
];
