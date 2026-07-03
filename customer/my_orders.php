<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('customer');

$stmt = $pdo->prepare("SELECT o.*, v.shop_name FROM orders o JOIN vendors v ON v.id = o.vendor_id WHERE o.customer_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$statusColor = [
    'placed' => 'secondary', 'preparing' => 'warning', 'delivering' => 'info',
    'delivered' => 'success', 'cancelled' => 'danger'
];

require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">My Orders</h3>

<?php if (empty($orders)): ?>
  <div class="alert alert-info">No orders yet. <a href="../index.php">Browse vendors</a></div>
<?php else: ?>
  <div class="list-group">
  <?php foreach ($orders as $o): ?>
    <a href="track_order.php?id=<?php echo $o['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
      <div>
        <b>Order #<?php echo $o['id']; ?></b> — <?php echo sanitize($o['shop_name']); ?><br>
        <small class="text-muted"><?php echo $o['created_at']; ?> • LKR <?php echo number_format($o['total_amount'], 2); ?></small>
      </div>
      <span class="badge bg-<?php echo $statusColor[$o['status']] ?? 'secondary'; ?>"><?php echo ucfirst($o['status']); ?></span>
    </a>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
