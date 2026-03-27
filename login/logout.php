<?php
session_start();
require_once __DIR__ . '/../routes/main.php';
session_unset();
session_destroy();
header("Location: " . main_route('home'));
exit;
?>
