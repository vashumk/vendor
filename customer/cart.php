<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('customer');

// Remove item
if (isset($_GET['remove'])) {
    $pid = intval($_GET['remove']);
    unset($_SESSION['cart']['items'][$pid]);
    if (empty($_SESSION['cart']['items'])) unset($_SESSION['cart']);
    header('Location: cart.php');
    exit;
}

// Update qty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    foreach ($_POST['qty'] as $pid => $qty) {
        $qty = max(1, intval($qty));
        $_SESSION['cart']['items'][$pid] = $qty;
    }
    header('Location: cart.php');
    exit;
}

$cartItems = [];
$total = 0;
$vendor = null;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])) {
    $vendorId = $_SESSION['cart']['vendor_id'];
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$vendorId]);
    $vendor = $stmt->fetch();

    $ids = array_keys($_SESSION['cart']['items']);
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
        $stmt->execute($ids);
        $prods = $stmt->fetchAll();
        foreach ($prods as $p) {
            $qty = $_SESSION['cart']['items'][$p['id']];
            $subtotal = $qty * $p['price'];
            $total += $subtotal;
            $cartItems[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $subtotal];
        }
    }
}

// Place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cartItems)) {
        flash('error', 'Your basket is empty.');
        header('Location: cart.php');
        exit;
    }
    $lat = $_POST['delivery_lat'] ?? null;
    $lng = $_POST['delivery_lng'] ?? null;
    $address = sanitize($_POST['delivery_address'] ?? '');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, vendor_id, total_amount, delivery_lat, delivery_lng, delivery_address, status) VALUES (?,?,?,?,?,?, 'placed')");
        $stmt->execute([$_SESSION['user_id'], $vendor['id'], $total, $lat, $lng, $address]);
        $orderId = $pdo->lastInsertId();

        foreach ($cartItems as $ci) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, qty, price) VALUES (?,?,?,?,?)");
            $stmt->execute([$orderId, $ci['product']['id'], $ci['product']['name'], $ci['qty'], $ci['product']['price']]);

            // reduce stock
            $stmt = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id = ?");
            $stmt->execute([$ci['qty'], $ci['product']['id']]);
        }

        $stmt = $pdo->prepare("INSERT INTO order_status_log (order_id, status) VALUES (?, 'placed')");
        $stmt->execute([$orderId]);

        $pdo->commit();
        unset($_SESSION['cart']);
        header('Location: track_order.php?id=' . $orderId);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Order failed: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h3 class="mb-3">🛒 Your Basket</h3>

<?php if (empty($cartItems)): ?>
  <div class="alert alert-info">Your basket is empty. <a href="../index.php">Browse vendors</a></div>
<?php else: ?>
  <p class="text-muted">Vendor: <b><?php echo sanitize($vendor['shop_name']); ?></b></p>
  <form method="POST">
    <table class="table bg-white">
      <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cartItems as $ci): ?>
        <tr>
          <td><?php echo sanitize($ci['product']['name']); ?></td>
          <td>LKR <?php echo number_format($ci['product']['price'], 2); ?></td>
          <td style="width:100px;">
            <input type="number" name="qty[<?php echo $ci['product']['id']; ?>]" value="<?php echo $ci['qty']; ?>" min="1" max="<?php echo $ci['product']['stock_qty']; ?>" class="form-control form-control-sm">
          </td>
          <td>LKR <?php echo number_format($ci['subtotal'], 2); ?></td>
          <td><a href="cart.php?remove=<?php echo $ci['product']['id']; ?>" class="text-danger small">Remove</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <button type="submit" name="update_qty" class="btn btn-outline-secondary btn-sm mb-3">Update quantities</button>
  </form>

  <div class="card p-3">
    <h5 class="d-flex justify-content-between">Total <span>LKR <?php echo number_format($total, 2); ?></span></h5>
    <form method="POST" id="orderForm">
      <div class="mb-2">
        <label class="form-label">Delivery address</label>
        <input type="text" name="delivery_address" class="form-control" required>
      </div>
      <input type="hidden" name="delivery_lat" id="delivery_lat">
      <input type="hidden" name="delivery_lng" id="delivery_lng">
      <button type="button" class="btn btn-outline-success btn-sm mb-2" onclick="pickDeliveryLoc()">📍 Use my current location</button>
      <div id="locMsg" class="small text-muted mb-2"></div>
      <button type="submit" name="place_order" class="btn btn-brand w-100">🚴 Place order</button>
    </form>
  </div>

  <script>
  function pickDeliveryLoc() {
      navigator.geolocation.getCurrentPosition(function(pos){
          document.getElementById('delivery_lat').value = pos.coords.latitude;
          document.getElementById('delivery_lng').value = pos.coords.longitude;
          document.getElementById('locMsg').innerText = 'Location captured ✔';
      });
  }
  </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
