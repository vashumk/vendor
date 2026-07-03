<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// Toggle vendor active/inactive
if (isset($_GET['toggle_vendor'])) {
    $vid = intval($_GET['toggle_vendor']);
    $pdo->prepare("UPDATE vendors SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$vid]);
    header('Location: dashboard.php');
    exit;
}

$totalUsers = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch()['c'];
$totalVendors = $pdo->query("SELECT COUNT(*) c FROM vendors")->fetch()['c'];
$totalOrders = $pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE status='delivered'")->fetch()['s'];

$vendors = $pdo->query("SELECT v.*, u.name AS owner_name, u.email FROM vendors v JOIN users u ON u.id = v.user_id ORDER BY v.created_at DESC")->fetchAll();
$recentOrders = $pdo->query("SELECT o.*, v.shop_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id=o.vendor_id JOIN users u ON u.id=o.customer_id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h3>Admin Panel</h3>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card p-3 text-center"><h4><?php echo $totalUsers; ?></h4><small>Customers</small></div></div>
  <div class="col-md-3"><div class="card p-3 text-center"><h4><?php echo $totalVendors; ?></h4><small>Vendors</small></div></div>
  <div class="col-md-3"><div class="card p-3 text-center"><h4><?php echo $totalOrders; ?></h4><small>Total Orders</small></div></div>
  <div class="col-md-3"><div class="card p-3 text-center"><h4>LKR <?php echo number_format($totalRevenue,2); ?></h4><small>Delivered Revenue</small></div></div>
</div>

<h5>Vendors</h5>
<table class="table bg-white">
  <thead><tr><th>Shop</th><th>Owner</th><th>Category</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($vendors as $v): ?>
    <tr>
      <td><?php echo sanitize($v['shop_name']); ?></td>
      <td><?php echo sanitize($v['owner_name']); ?> (<?php echo sanitize($v['email']); ?>)</td>
      <td><?php echo sanitize($v['category']); ?></td>
      <td><span class="badge bg-<?php echo $v['status']=='active'?'success':'secondary'; ?>"><?php echo $v['status']; ?></span></td>
      <td><a href="dashboard.php?toggle_vendor=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-secondary">Toggle</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h5 class="mt-4">Recent Orders</h5>
<table class="table bg-white">
  <thead><tr><th>#</th><th>Customer</th><th>Vendor</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach ($recentOrders as $o): ?>
    <tr>
      <td><?php echo $o['id']; ?></td>
      <td><?php echo sanitize($o['customer_name']); ?></td>
      <td><?php echo sanitize($o['shop_name']); ?></td>
      <td>LKR <?php echo number_format($o['total_amount'],2); ?></td>
      <td><?php echo ucfirst($o['status']); ?></td>
      <td><?php echo $o['created_at']; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
