<?php

date_default_timezone_set('Europe/Riga');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: '';

if ($user === '' || $db === '') {
    die('DB nav konfigurēts. Izveido con_db.php vai iestati DB_HOST, DB_USER, DB_PASS, DB_NAME.');
}

$savienojums = mysqli_connect($host, $user, $pass, $db);

if (!$savienojums) {
    die('Pieslēgties neizdevās: ' . mysqli_connect_error());
}

mysqli_set_charset($savienojums, 'utf8mb4');

$mysqlOffset = date('P');
mysqli_query($savienojums, "SET time_zone = '{$mysqlOffset}'");

function convertUtcToRiga($utcTime)
{
    $dt = new DateTime($utcTime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Europe/Riga'));
    return $dt->format('Y-m-d H:i:s');
}
