<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$orderId = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT o.*, v.shop_name, v.address AS vendor_address FROM orders o JOIN vendors v ON v.id = o.vendor_id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    header('Location: my_orders.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

$stages = ['placed', 'preparing', 'delivering', 'delivered'];
$currentIndex = array_search($order['status'], $stages);

require_once __DIR__ . '/../includes/header.php';
?>

<h3>Order #<?php echo $order['id']; ?> — <?php echo sanitize($order['shop_name']); ?></h3>

<?php if ($order['status'] === 'cancelled'): ?>
  <div class="alert alert-danger">This order was cancelled.</div>
<?php else: ?>
<ul class="status-track">
  <?php foreach ($stages as $i => $s): ?>
    <li class="<?php echo $i <= $currentIndex ? 'active' : ''; ?>" id="stage-<?php echo $s; ?>">
      <div class="dot"></div><?php echo ucfirst($s); ?>
    </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<div class="card p-3 mb-3">
  <h6>Items</h6>
  <table class="table table-sm">
    <?php foreach ($items as $it): ?>
      <tr><td><?php echo sanitize($it['product_name']); ?></td><td><?php echo $it['qty']; ?></td><td>LKR <?php echo number_format($it['price'] * $it['qty'], 2); ?></td></tr>
    <?php endforeach; ?>
  </table>
  <p class="fw-bold text-end">Total: LKR <?php echo number_format($order['total_amount'], 2); ?></p>
</div>

<div class="card p-3">
  <p><b>Delivery address:</b> <?php echo sanitize($order['delivery_address']); ?></p>
  <p class="mb-0"><b>Status:</b> <span id="statusLabel" class="badge bg-success"><?php echo ucfirst($order['status']); ?></span></p>
</div>

<script>
// Live poll status every 5 seconds
function pollStatus() {
    fetch('../api/get_order_status.php?id=<?php echo $order['id']; ?>')
      .then(r => r.json())
      .then(data => {
          if (!data.success) return;
          document.getElementById('statusLabel').innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1);
          const stages = ['placed','preparing','delivering','delivered'];
          const idx = stages.indexOf(data.status);
          stages.forEach((s, i) => {
              const el = document.getElementById('stage-' + s);
              if (el) el.classList.toggle('active', i <= idx);
          });
      });
}
setInterval(pollStatus, 5000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
