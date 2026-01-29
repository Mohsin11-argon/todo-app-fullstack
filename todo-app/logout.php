<?php
// logout.php
require_once 'db.php';
session_unset();
session_destroy();

// Redirect to homepage
header("Location: home.php"); // or "index.php" if that's your homepage
exit;
