<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$vendorId = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT v.*, u.phone FROM vendors v JOIN users u ON u.id = v.user_id WHERE v.id = ?");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch();

if (!$vendor) {
    flash('error', 'Vendor not found.');
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? AND stock_qty > 0 ORDER BY category, name");
$stmt->execute([$vendorId]);
$products = $stmt->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    require_login();
    if (current_role() !== 'customer') {
        flash('error', 'Only customers can order.');
    } else {
        $productId = intval($_POST['product_id']);
        $qty = max(1, intval($_POST['qty']));

        if (!isset($_SESSION['cart']) || $_SESSION['cart']['vendor_id'] != $vendorId) {
            $_SESSION['cart'] = ['vendor_id' => $vendorId, 'items' => []];
        }
        if (isset($_SESSION['cart']['items'][$productId])) {
            $_SESSION['cart']['items'][$productId] += $qty;
        } else {
            $_SESSION['cart']['items'][$productId] = $qty;
        }
        flash('success', 'Added to basket.');
    }
    header('Location: vendor_view.php?id=' . $vendorId);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3><?php echo sanitize($vendor['shop_name']); ?></h3>
    <p class="text-muted mb-0"><?php echo sanitize(ucfirst($vendor['category'])); ?> • <?php echo sanitize($vendor['address']); ?></p>
  </div>
  <a href="../index.php" class="btn btn-outline-secondary btn-sm">← Back to map</a>
</div>

<?php if (isset($_SESSION['cart']) && $_SESSION['cart']['vendor_id'] != $vendorId && count($_SESSION['cart']['items']) > 0): ?>
  <div class="alert alert-warning small">Your basket has items from another vendor. Adding items here will start a new basket for this shop.</div>
<?php endif; ?>

<div class="row g-3">
<?php if (count($products) === 0): ?>
  <p class="text-muted">No products available from this vendor right now.</p>
<?php endif; ?>
<?php foreach ($products as $p): ?>
  <div class="col-md-4 col-sm-6">
    <div class="card product-card h-100">
      <img src="<?php echo $p['image'] ? '../assets/uploads/' . sanitize($p['image']) : 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400'; ?>" alt="">
      <div class="card-body">
        <h6 class="mb-1"><?php echo sanitize($p['name']); ?></h6>
        <p class="text-muted small mb-1"><?php echo sanitize($p['category']); ?> • <?php echo intval($p['stock_qty']); ?> <?php echo sanitize($p['unit']); ?> in stock</p>
        <p class="fw-bold text-success">LKR <?php echo number_format($p['price'], 2); ?> / <?php echo sanitize($p['unit']); ?></p>
        <form method="POST" class="d-flex gap-2">
          <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
          <input type="number" name="qty" value="1" min="1" max="<?php echo $p['stock_qty']; ?>" class="form-control form-control-sm" style="width:70px;">
          <button type="submit" name="add_to_cart" class="btn btn-brand btn-sm flex-grow-1">Add</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
