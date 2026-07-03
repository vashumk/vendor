<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$orderId = intval($_GET['id'] ?? 0);
if (!is_logged_in()) {
    echo json_encode(['success' => false]); exit;
}

$stmt = $pdo->prepare("SELECT o.status, o.updated_at FROM orders o WHERE o.id = ? AND (o.customer_id = ? OR o.vendor_id IN (SELECT id FROM vendors WHERE user_id = ?))");
$stmt->execute([$orderId, $_SESSION['user_id'], $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false]); exit;
}

echo json_encode(['success' => true, 'status' => $order['status'], 'updated_at' => $order['updated_at']]);
