<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $phone = sanitize($_POST['phone']);
    $role = $_POST['role'] === 'vendor' ? 'vendor' : 'customer';

    // vendor-specific fields
    $shop_name = sanitize($_POST['shop_name'] ?? '');
    $category  = $_POST['category'] ?? 'all';
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;
    $address = sanitize($_POST['address'] ?? '');

    if (!$name || !$email || !$password) {
        flash('error', 'Please fill all required fields.');
    } elseif ($role === 'vendor' && (!$shop_name || !$lat || !$lng)) {
        flash('error', 'Vendor accounts must include shop name and location (use the "Pick my location" button).');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'This email is already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,?)");
                $stmt->execute([$name, $email, $hash, $phone, $role]);
                $userId = $pdo->lastInsertId();

                if ($role === 'vendor') {
                    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, category, address, latitude, longitude) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$userId, $shop_name, $category, $address, $lat, $lng]);
                }
                $pdo->commit();
                flash('success', 'Registration successful! Please login.');
                header('Location: login.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Registration failed: ' . $e->getMessage());
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card p-4 shadow-sm">
      <h3 class="mb-3">Create your account</h3>
      <form method="POST" id="regForm">
        <div class="mb-3">
          <label class="form-label">I am a</label>
          <select name="role" id="roleSelect" class="form-select" onchange="toggleVendorFields()">
            <option value="customer">Customer (I want to buy)</option>
            <option value="vendor">Vendor (I want to sell)</option>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Full name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
        </div>
        <div class="mb-3 mt-2">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div id="vendorFields" style="display:none;" class="border-top pt-3 mt-2">
          <h6 class="text-success">Shop details</h6>
          <div class="mb-2">
            <label class="form-label">Shop name</label>
            <input type="text" name="shop_name" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <option value="vegetables">Vegetables</option>
              <option value="fish">Fish</option>
              <option value="bread">Bread</option>
              <option value="all">All / Mixed</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
          </div>
          <button type="button" class="btn btn-outline-success btn-sm mb-2" onclick="pickLocation()">📍 Pick my shop location</button>
          <div id="locStatus" class="small text-muted"></div>
          <input type="hidden" name="latitude" id="latitude">
          <input type="hidden" name="longitude" id="longitude">
        </div>

        <button type="submit" class="btn btn-brand w-100 mt-3">Register</button>
      </form>
      <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
    </div>
  </div>
</div>

<script>
function toggleVendorFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('vendorFields').style.display = role === 'vendor' ? 'block' : 'none';
}
function pickLocation() {
    if (!navigator.geolocation) { alert('Geolocation not supported'); return; }
    document.getElementById('locStatus').innerText = 'Getting location...';
    navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('latitude').value = pos.coords.latitude;
        document.getElementById('longitude').value = pos.coords.longitude;
        document.getElementById('locStatus').innerText = 'Location set: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
    }, function() {
        document.getElementById('locStatus').innerText = 'Could not get location. Please allow location access.';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
