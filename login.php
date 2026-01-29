<?php
// login.php
require_once 'db.php';

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = "Email and password are required.";
    } else {
        // Try admin table first. Note: admins table only stores email/password (no name).
        $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $role = 'admin';

        if (!$user) {
            // Try user table
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            $role = 'user';
        }

        if ($user && password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = (int)$user['id'];
          // Admins table does not have a 'name' field; set a sensible display name
          $_SESSION['name'] = $role === 'admin' ? 'Administrator' : ($user['name'] ?? $user['email']);
          $_SESSION['email'] = $user['email'];
          $_SESSION['role'] = $role;

            // ✅ Redirect to correct panel
            header("Location: " . ($role === 'admin' ? 'admin.php' : 'user.php'));
            exit;
        } else {
            $errors[] = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - ToDo App</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth">
  <div class="container narrow">
    <h2>Login to your account</h2>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="card-form">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="off" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary" type="submit">Login</button>
      <div class="form-note">
        New here? <a href="signup.php">Create an account</a>
      </div>
    </form>
  </div>
</body>
</html>
