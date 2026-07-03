<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fresh Link - Nearby Fresh Food Marketplace</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌿</text></svg>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#1f7a1f;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>/index.php">🌿 Fresh Link</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Home</a></li>

        <?php if (is_logged_in()): ?>
            <?php if (current_role() === 'customer'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/customer/cart.php">🛒 Basket</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/customer/my_orders.php">My Orders</a></li>
            <?php elseif (current_role() === 'vendor'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/vendor/dashboard.php">Vendor Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/vendor/orders.php">Orders</a></li>
            <?php elseif (current_role() === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
            <?php endif; ?>
            <li class="nav-item">
                <span class="nav-link text-white-50">Hi, <?php echo sanitize($_SESSION['name']); ?></span>
            </li>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
            <li class="nav-item"><a class="btn btn-light btn-sm text-success fw-bold" href="<?php echo BASE_URL; ?>/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-3">
<?php
$err = flash('error');
$ok = flash('success');
if ($err): ?>
    <div class="alert alert-danger"><?php echo sanitize($err); ?></div>
<?php endif;
if ($ok): ?>
    <div class="alert alert-success"><?php echo sanitize($ok); ?></div>
<?php endif; ?>
</div>