<?php
// Test email sending functionality
// Run this file directly in browser: http://localhost/bajwa_app/test_email.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "<h2>Email Test Script</h2>";
echo "<pre>";

// Test 1: Check config file
echo "1. Checking mail_config.php...\n";
$configPath = __DIR__ . '/mail_config.php';
if (file_exists($configPath)) {
    echo "   ✓ Config file exists\n";
    $config = include $configPath;
    if (is_array($config)) {
        echo "   ✓ Config loaded successfully\n";
        echo "   - Host: " . ($config['host'] ?? 'NOT SET') . "\n";
        echo "   - Port: " . ($config['port'] ?? 'NOT SET') . "\n";
        echo "   - Username: " . ($config['username'] ?? 'NOT SET') . "\n";
        echo "   - Password: " . (isset($config['password']) && !empty($config['password']) ? 'SET (hidden)' : 'NOT SET') . "\n";
        echo "   - From Email: " . ($config['from_email'] ?? 'NOT SET') . "\n";
    } else {
        echo "   ✗ Config is not an array\n";
    }
} else {
    echo "   ✗ Config file NOT found at: $configPath\n";
}

// Test 2: Get admin email
echo "\n2. Checking admin email...\n";
$adminEmail = get_admin_email();
echo "   Admin email: $adminEmail\n";

// Test 3: Test email sending
echo "\n3. Testing email send...\n";
$testEmail = $config['from_email'] ?? $config['username'] ?? $adminEmail;
echo "   Sending test email to: $testEmail\n";

$subject = "Test Email from ToDo App - " . date('Y-m-d H:i:s');
$body = "This is a test email.\n\n";
$body .= "If you received this, your email configuration is working correctly!\n\n";
$body .= "Sent at: " . date('Y-m-d H:i:s') . "\n";

$result = send_email_notification($testEmail, $subject, $body);

if ($result) {
    echo "   ✓ Email sent successfully!\n";
    echo "   Please check your inbox (and spam folder) for the test email.\n";
} else {
    echo "   ✗ Email sending failed!\n";
    echo "   Check PHP error logs for details.\n";
}

// Test 4: Check error log location
echo "\n4. Error log information...\n";
$errorLog = ini_get('error_log');
if ($errorLog) {
    echo "   PHP error log: $errorLog\n";
} else {
    echo "   PHP error log: Using default location\n";
}

echo "\n</pre>";
echo "<p><strong>Note:</strong> Check your PHP error logs if email sending fails.</p>";
echo "<p>Common issues:</p>";
echo "<ul>";
echo "<li>Gmail App Password might be incorrect or expired</li>";
echo "<li>Gmail account might have 'Less secure app access' disabled (use App Password instead)</li>";
echo "<li>Firewall or network blocking SMTP port 587</li>";
echo "<li>Check spam/junk folder for test email</li>";
echo "</ul>";
?>
