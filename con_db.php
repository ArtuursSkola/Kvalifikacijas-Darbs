<?php

date_default_timezone_set('Europe/Riga');

$host = "sql100.ezyro.com";
$user = "ezyro_41234376";
$pass = "2adfc4c8763c5361";
$db   = "ezyro_41234376_my_database";

$savienojums = mysqli_connect($host, $user, $pass, $db);

if (!$savienojums) {
    die("Pieslēgties neizdevās: " . mysqli_connect_error());
}

mysqli_set_charset($savienojums, "utf8mb4");


$mysqlOffset = date('P');
mysqli_query($savienojums, "SET time_zone = '{$mysqlOffset}'");


function convertUtcToRiga($utcTime) {
    $dt = new DateTime($utcTime, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('Europe/Riga'));
    return $dt->format('Y-m-d H:i:s');
}
