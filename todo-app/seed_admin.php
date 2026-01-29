<?php
// Seed a default admin into the `todo_db` database used by the PHP app.
require_once __DIR__ . '/db.php';

$admin_email = 'zainababbasbajwa17@gmail.com';
$admin_password = 'AdminPass123!';

// Check if admins table exists (if not, inform the user)
if (!$conn) {
    echo "DB connection not available\n";
    exit;
}

// Ensure admins table exists (simple check)
$res = $conn->query("SHOW TABLES LIKE 'admins'");
if ($res->num_rows === 0) {
    echo "Admins table not found. Please import database_todo_db.sql first.\n";
    exit;
}

// Check for existing admin
$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->bind_param('s', $admin_email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Admin already exists: $admin_email\n";
    $stmt->close();
    exit;
}
$stmt->close();

$hashed = password_hash($admin_password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
$stmt->bind_param('ss', $admin_email, $hashed);
if ($stmt->execute()) {
    echo "Seeded admin: $admin_email with password: $admin_password\n";
} else {
    echo "Failed to insert admin: " . $conn->error . "\n";
}
$stmt->close();
?>