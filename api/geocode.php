<?php

header('Content-Type: application/json; charset=utf-8');

function json_out(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) < 3 || strlen($q) > 400) {
    json_out(['ok' => false, 'error' => 'Bad query'], 400);
}

$cacheDir = realpath(__DIR__ . '/../cache/geocode');
if ($cacheDir === false) {
    $cacheDir = __DIR__ . '/../cache/geocode';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    $cacheDir = realpath($cacheDir) ?: $cacheDir;
}

$key = hash('sha256', $q, true);
$file = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . bin2hex($key) . '.json';

if (is_readable($file)) {
    $raw = @file_get_contents($file);
    if ($raw !== false) {
        $cached = json_decode($raw, true);
        if (is_array($cached) && isset($cached['lat'], $cached['lng'], $cached['t'])) {
            if ((time() - (int)$cached['t']) < 86400 * 30) {
                json_out(['ok' => true, 'lat' => (float)$cached['lat'], 'lng' => (float)$cached['lng'], 'cached' => true]);
            }
        }
    }
}

$url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($q);
$ch = curl_init($url);
if ($ch === false) {
    json_out(['ok' => false, 'error' => 'Request failed'], 500);
}
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept-Language: lv,en',
    ],
    CURLOPT_USERAGENT => 'HomeEstate/1.0 (+https://homeestate.lv; contact: info@homeestate.lv)',
    CURLOPT_TIMEOUT => 12,
]);
$body = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $code !== 200) {
    json_out(['ok' => false, 'error' => 'Geocode unavailable'], 502);
}

$data = json_decode($body, true);
if (!is_array($data) || $data === []) {
    json_out(['ok' => false, 'error' => 'Not found'], 404);
}

$row = $data[0];
$lat = isset($row['lat']) ? (float)$row['lat'] : null;
$lng = isset($row['lon']) ? (float)$row['lon'] : null;
if ($lat === null || $lng === null || ($lat === 0.0 && $lng === 0.0)) {
    json_out(['ok' => false, 'error' => 'Not found'], 404);
}

$payload = ['lat' => $lat, 'lng' => $lng, 't' => time()];
@file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);

json_out(['ok' => true, 'lat' => $lat, 'lng' => $lng, 'cached' => false]);
