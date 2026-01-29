<?php
require_once 'db.php';
require_role('admin');

header('Content-Type: application/json');

// Fetch unread admin messages
$stmt = $conn->prepare("SELECT id, sender_role, sender_id, message, created_at FROM messages WHERE recipient_role = 'admin' AND is_read = 0 ORDER BY created_at ASC LIMIT 50");
$stmt->execute();
$res = $stmt->get_result();
$msgs = [];
$ids = [];
while ($r = $res->fetch_assoc()) {
    $msgs[] = $r;
    $ids[] = (int)$r['id'];
}
$stmt->close();

if (!empty($ids)) {
    $in = implode(',', array_map('intval', $ids));
    // Mark as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE id IN ($in)");
}

echo json_encode(['ok' => true, 'messages' => $msgs]);
