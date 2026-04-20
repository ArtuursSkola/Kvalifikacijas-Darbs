<?php
$host = "sql100.ezyro.com";
$user = "ezyro_41234376";
$pass = "2adfc4c8763c5361";
$db   = "ezyro_41234376_my_database";

$savienojums = mysqli_connect($host, $user, $pass, $db);

if (!$savienojums) {
    die("Pieslēgties neizdevās: " . mysqli_connect_error());
}


mysqli_set_charset($savienojums, "utf8mb4");
?>
