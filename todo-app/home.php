<?php
// SAFE session start (prevents notice)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Redirect if already logged in
if (!empty($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'user.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome | ToDo App</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="homepage">

  <!-- NAVBAR -->
  <header class="navbar">
    <div class="logo">
      <!-- <img src="1.jpg" alt="ToDo App Logo"> -->
    </div>

    <nav class="nav-right">
      <ul>
        <li><a class="btn btn-primary" href="login.php">Login</a></li>
        <li><a class="btn btn-secondary" href="signup.php">Sign Up</a></li>
      </ul>
    </nav>
  </header>

  <!-- HERO -->
  <main class="hero center-text">
    <h1>Welcome ToDo App</h1>
    <p class="intro">
      Our ToDo App helps teams stay organized and productive.
      Admins can assign tasks, track progress,and manage priorities
      all in one place.
    </p>
    <a href="#" class="btn-learn">LEARN MORE</a>
  </main>

</body>
</html>
