<?php
// signup.php
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Validation
    if ($name === '' || $email === '' || $password === '') {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (!$errors) {
        // Check if email exists in users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
        $stmt->close();

        if (!$errors) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            // Always insert as a normal user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed);

            if ($stmt->execute()) {
                $success = "Signup successful. You can now login.";
            } else {
                $errors[] = "Failed to create account. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - ToDo App</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth">
  <div class="container narrow">
    <h2>Create your account</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="card-form">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn btn-primary" type="submit">Sign Up</button>
      <div class="form-note">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </form>
  </div>
</body>
</html>
