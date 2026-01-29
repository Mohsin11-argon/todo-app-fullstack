<?php
// Tiny test script to verify send_email_notification() using local mail_config.php
require_once __DIR__ . '/../db.php';

// Determine recipient: use configured username or the admin user
$config = file_exists(__DIR__ . '/../mail_config.php') ? include __DIR__ . '/../mail_config.php' : [];
$to = $config['username'] ?? get_admin_email();

$subject = 'Test email from ToDo App (' . date('Y-m-d H:i:s') . ')';
$body = "This is a test email sent by tools/send_test_email.php\n\nIf you received this, SMTP is configured correctly.\n";

$ok = send_email_notification($to, $subject, $body);
if ($ok) {
    echo "OK: Test email sent to $to\n";
    exit(0);
} else {
    echo "FAIL: Test email failed to send to $to\n";
    exit(2);
}
