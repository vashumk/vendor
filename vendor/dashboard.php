<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('vendor');

$stmt = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch();

if (!$vendor) {
    flash('error', 'Vendor profile not found.');
    header('Location: ../index.php');
    exit;
}

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $unit = sanitize($_POST['unit']);
    $stock = intval($_POST['stock_qty']);

    $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, category, price, unit, stock_qty) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$vendor['id'], $name, $category, $price, $unit, $stock]);
    flash('success', 'Product added.');
    header('Location: dashboard.php');
    exit;
}

// Update stock/price inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $pid = intval($_POST['product_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock_qty']);
    $stmt = $pdo->prepare("UPDATE products SET price = ?, stock_qty = ? WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$price, $stock, $pid, $vendor['id']]);
    flash('success', 'Product updated.');
    header('Location: dashboard.php');
    exit;
}

// Delete product
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$pid, $vendor['id']]);
    flash('success', 'Product removed.');
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY created_at DESC");
$stmt->execute([$vendor['id']]);
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h3><?php echo sanitize($vendor['shop_name']); ?> — Dashboard</h3>
<p class="text-muted"><?php echo sanitize(ucfirst($vendor['category'])); ?> • <?php echo sanitize($vendor['address']); ?></p>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6>➕ Add product</h6>
      <form method="POST">
        <input type="text" name="name" class="form-control mb-2" placeholder="Product name" required>
        <select name="category" class="form-select mb-2">
          <option value="vegetables">Vegetables</option>
          <option value="fish">Fish</option>
          <option value="bread">Bread</option>
        </select>
        <div class="row g-2 mb-2">
          <div class="col-6"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price (LKR)" required></div>
          <div class="col-6"><input type="text" name="unit" class="form-control" placeholder="Unit (kg/pcs)" value="kg"></div>
        </div>
        <input type="number" name="stock_qty" class="form-control mb-2" placeholder="Stock quantity" required>
        <button type="submit" name="add_product" class="btn btn-brand w-100">Add product</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <h6>Your products</h6>
    <?php if (empty($products)): ?>
      <p class="text-muted">No products yet. Add your first product.</p>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <div class="card p-3 mb-2">
        <form method="POST" class="row g-2 align-items-center">
          <div class="col-md-3"><b><?php echo sanitize($p['name']); ?></b><br><small class="text-muted"><?php echo sanitize($p['category']); ?></small></div>
          <div class="col-md-3">
            <label class="form-label small mb-0">Price (LKR/<?php echo sanitize($p['unit']); ?>)</label>
            <input type="number" step="0.01" name="price" value="<?php echo $p['price']; ?>" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-0">Stock qty</label>
            <input type="number" name="stock_qty" value="<?php echo $p['stock_qty']; ?>" class="form-control form-control-sm">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
            <button type="submit" name="update_product" class="btn btn-outline-success btn-sm">Save</button>
            <a href="dashboard.php?delete=<?php echo $p['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
