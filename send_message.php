<?php
require_once 'db.php';
require_login();

$sender_role = $_SESSION['role'];
$sender_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Empty message']);
    } else {
        $_SESSION['flash_status_report'] = 'Message cannot be empty.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'user.php'));
    }
    exit;
}
$recipient_role = $_POST['recipient_role'] ?? 'admin';
$recipient_user_id = isset($_POST['recipient_user_id']) && is_numeric($_POST['recipient_user_id']) ? (int)$_POST['recipient_user_id'] : 0;

$stmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, recipient_role, recipient_user_id, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sisis", $sender_role, $sender_id, $recipient_role, $recipient_user_id, $message);
if ($stmt->execute()) {
    $stmt->close();
    // Send notification email to recipient (best-effort)
    $sender_name = !empty($_SESSION['name']) ? $_SESSION['name'] : $sender_role;
    $subject = sprintf("New message from %s", $sender_name);
    $body = sprintf("You have received a new message from %s (%s):\n\n%s\n\n--\nVisit the app to reply.", $sender_name, $sender_role, $message);
    // best-effort: do not change user-visible flow if mail fails
    notify_recipient_by_email($recipient_role, $recipient_user_id, $subject, $body);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    } else {
        $_SESSION['flash_status_report'] = 'Message sent.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? ($recipient_role === 'admin' ? 'user.php' : 'admin.php')));
    }
    exit;
} else {
    $stmt->close();
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $conn->error]);
    } else {
        $_SESSION['flash_status_report'] = 'Failed to send message.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'user.php'));
    }
    exit;
}
