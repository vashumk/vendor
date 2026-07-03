<?php
// api/get_nearby_vendors.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 5;
$category = $_GET['category'] ?? 'all';

if ($lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Location required']);
    exit;
}

// Haversine formula directly in SQL, returns distance_km per vendor
$sql = "SELECT v.id, v.shop_name, v.category, v.address, v.latitude, v.longitude, u.phone,
        (6371 * ACOS(
            COS(RADIANS(:lat)) * COS(RADIANS(v.latitude)) *
            COS(RADIANS(v.longitude) - RADIANS(:lng)) +
            SIN(RADIANS(:lat)) * SIN(RADIANS(v.latitude))
        )) AS distance_km
        FROM vendors v
        JOIN users u ON u.id = v.user_id
        WHERE v.status = 'active'";

$params = [':lat' => $lat, ':lng' => $lng];

if ($category !== 'all') {
    $sql .= " AND (v.category = :category OR v.category = 'all')";
    $params[':category'] = $category;
}

$sql .= " HAVING distance_km <= :radius ORDER BY distance_km ASC";
$params[':radius'] = $radius;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendors = $stmt->fetchAll();

foreach ($vendors as &$v) {
    $v['distance_km'] = round($v['distance_km'], 2);
    $countStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM products WHERE vendor_id = ? AND stock_qty > 0");
    $countStmt->execute([$v['id']]);
    $v['product_count'] = $countStmt->fetch()['c'];
}

echo json_encode(['success' => true, 'count' => count($vendors), 'vendors' => $vendors]);
