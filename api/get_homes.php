<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../con_db.php';

$sql = "SELECT id, title, city, address, location_text, type, price, area, bedrooms, bathrooms, 
               floor_info, description, main_image, thumb1, thumb2, thumb3, 
               rent_price, utilities_price, total_price, status, property_category
        FROM est_homes 
        WHERE status = 'active' 
        ORDER BY created_at DESC";

$result = $savienojums->query($sql);

$homes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $homes[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'city' => $row['city'],
            'location' => $row['city'] . ', ' . $row['location_text'],
            'type' => $row['type'],
            'price' => (float)$row['price'],
            'beds' => (int)$row['bedrooms'],
            'baths' => (int)$row['bathrooms'],
            'size' => (int)$row['area'],
            'badge' => $row['type'] === 'rent' ? 'Izīrē' : 'Pārdod',
            'image' => $row['main_image'] ?: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=70',
            'desc' => mb_substr($row['description'], 0, 150) . '...',
            'category' => $row['property_category'],
            'rent_price' => (float)$row['rent_price'],
            'utilities_price' => (float)$row['utilities_price'],
            'total_price' => (float)$row['total_price']
        ];
    }
}

$savienojums->close();

echo json_encode($homes, JSON_UNESCAPED_UNICODE);
?>
