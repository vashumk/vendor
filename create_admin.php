<?php
// create_admin.php - run this ONCE from the browser to create your admin account, then delete this file.
require_once __DIR__ . '/config/db.php';

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($name && $email && $password) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?, 'admin')");
            $stmt->execute([$name, $email, $hash]);
            $done = true;
        }
    } else {
        $error = 'Fill all fields.';
    }
}
?>
<!DOCTYPE html>
<html><head><title>Create Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container" style="max-width:420px;">
<h4>Create Admin Account</h4>
<?php if ($done): ?>
  <div class="alert alert-success">Admin created! You can now login. <b>Delete this file (create_admin.php) now for security.</b></div>
<?php else: ?>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <form method="POST">
    <input class="form-control mb-2" name="name" placeholder="Name" required>
    <input class="form-control mb-2" name="email" placeholder="Email" required>
    <input class="form-control mb-2" type="password" name="password" placeholder="Password" required>
    <button class="btn btn-success w-100">Create Admin</button>
  </form>
<?php endif; ?>
</div>
</body></html>
