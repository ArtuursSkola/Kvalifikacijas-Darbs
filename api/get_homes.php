<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../con_db.php';
require_once __DIR__ . '/../routes/main.php';

if (!isset($savienojums) || !$savienojums instanceof mysqli) {
    echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Some environments may not have all columns yet. We try a full query first,
// then fall back to a minimal query if it fails so homes.php still works.
$selectFull = "SELECT id, owner_id, title, city, address, location_text, type, price, area, bedrooms, bathrooms,
        floor_info, description, main_image, thumb1, thumb2, thumb3,
        rent_price, utilities_price, total_price, status, property_category
    FROM est_homes";

$selectMin = "SELECT id, owner_id, title, city, location_text, type, price, area, bedrooms, bathrooms,
        description, main_image, status
    FROM est_homes";

$where = $userId > 0 ? " WHERE status = 'Aktivs' OR owner_id = ?" : " WHERE status = 'Aktivs'";
$order = " ORDER BY created_at DESC";

$stmt = null;
$result = false;

// Try full query.
if ($userId > 0) {
    $stmt = $savienojums->prepare($selectFull . $where . $order);
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
} else {
    $result = $savienojums->query($selectFull . $where . $order);
}

// Fall back to minimal query.
if ($result === false) {
    if ($stmt) {
        $stmt->close();
        $stmt = null;
    }

    if ($userId > 0) {
        $stmt = $savienojums->prepare($selectMin . $where . $order);
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
        }
    } else {
        $result = $savienojums->query($selectMin . $where . $order);
    }
}

$homes = [];
$fallbackImg = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70';

if ($result && $result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $desc = (string)($row['description'] ?? '');
        $descShort = '';
        if ($desc !== '') {
            $slice = function_exists('mb_substr') ? mb_substr($desc, 0, 150) : substr($desc, 0, 150);
            $descShort = $slice . '...';
        }
        $type = (string)($row['type'] ?? '');
        $city = (string)($row['city'] ?? '');
        $locText = (string)($row['location_text'] ?? '');

        $homes[] = [
            'id' => (int)($row['id'] ?? 0),
            'status' => (string)($row['status'] ?? ''),
            'is_own' => $userId > 0 && (int)($row['owner_id'] ?? 0) === $userId,
            'title' => (string)($row['title'] ?? ''),
            'city' => $city,
            'location' => $city . ($locText !== '' ? ', ' . $locText : ''),
            'type' => $type,
            'price' => (float)($row['price'] ?? 0),
            'beds' => (int)($row['bedrooms'] ?? 0),
            'baths' => (int)($row['bathrooms'] ?? 0),
            'size' => (int)($row['area'] ?? 0),
            'badge' => $type === 'rent' ? 'Izīrē' : 'Pārdod',
            'image' => media_absolute_url(!empty($row['main_image']) ? (string)$row['main_image'] : $fallbackImg),
            'desc' => $descShort,
            'category' => (string)($row['property_category'] ?? ''),
            'rent_price' => (float)($row['rent_price'] ?? 0),
            'utilities_price' => (float)($row['utilities_price'] ?? 0),
            'total_price' => (float)($row['total_price'] ?? 0),
        ];
    }
}

if ($stmt) {
    $stmt->close();
}
$savienojums->close();

echo json_encode($homes, JSON_UNESCAPED_UNICODE);
