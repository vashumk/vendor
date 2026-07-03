<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('vendor');

$stmt = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $status = $_POST['status'];
    $allowed = ['placed', 'preparing', 'delivering', 'delivered', 'cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$status, $orderId, $vendor['id']]);
        $stmt = $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, ?)");
        $stmt->execute([$orderId, $status]);
        flash('success', 'Order #' . $orderId . ' status updated to ' . $status . '.');
    }
    header('Location: orders.php');
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.phone FROM orders o JOIN users u ON u.id = o.customer_id WHERE o.vendor_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$vendor['id']]);
$orders = $stmt->fetchAll();

$statusColor = ['placed' => 'secondary', 'preparing' => 'warning', 'delivering' => 'info', 'delivered' => 'success', 'cancelled' => 'danger'];

require_once __DIR__ . '/../includes/header.php';
?>

<h3>Incoming Orders</h3>

<?php if (empty($orders)): ?>
  <p class="text-muted">No orders yet.</p>
<?php endif; ?>

<?php foreach ($orders as $o): ?>
  <?php
    $stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt2->execute([$o['id']]);
    $items = $stmt2->fetchAll();
  ?>
  <div class="card p-3 mb-3">
    <div class="d-flex justify-content-between">
      <div>
        <b>Order #<?php echo $o['id']; ?></b> — <?php echo sanitize($o['customer_name']); ?> (<?php echo sanitize($o['phone']); ?>)<br>
        <small class="text-muted"><?php echo $o['created_at']; ?> • <?php echo sanitize($o['delivery_address']); ?></small>
      </div>
      <span class="badge bg-<?php echo $statusColor[$o['status']]; ?> align-self-start"><?php echo ucfirst($o['status']); ?></span>
    </div>
    <ul class="small mt-2 mb-2">
      <?php foreach ($items as $it): ?>
        <li><?php echo sanitize($it['product_name']); ?> x <?php echo $it['qty']; ?> — LKR <?php echo number_format($it['price'] * $it['qty'], 2); ?></li>
      <?php endforeach; ?>
    </ul>
    <p class="fw-bold">Total: LKR <?php echo number_format($o['total_amount'], 2); ?></p>

    <?php if (!in_array($o['status'], ['delivered', 'cancelled'])): ?>
    <form method="POST" class="d-flex gap-2">
      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
      <select name="status" class="form-select form-select-sm" style="max-width:200px;">
        <option value="placed" <?php echo $o['status']=='placed'?'selected':''; ?>>Placed</option>
        <option value="preparing" <?php echo $o['status']=='preparing'?'selected':''; ?>>Preparing</option>
        <option value="delivering" <?php echo $o['status']=='delivering'?'selected':''; ?>>Delivering</option>
        <option value="delivered" <?php echo $o['status']=='delivered'?'selected':''; ?>>Delivered</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button type="submit" name="update_status" class="btn btn-brand btn-sm">Update status</button>
    </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
