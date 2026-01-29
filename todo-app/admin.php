<?php
// admin.php
require_once 'db.php';
require_role('admin');

// Handle task submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new task
    if (isset($_POST['title'])) {
        $title = trim($_POST['title']);
        $assigned_to = (int)$_POST['assigned_to'];
        $due_date = $_POST['due_date'] ?: null;
        $priority = $_POST['priority'];

        if ($title && $assigned_to > 0 && in_array($priority, ['Low','Medium','High'])) {
          $stmt = $conn->prepare("INSERT INTO tasks (title, assigned_to, due_date, priority) VALUES (?, ?, ?, ?)");
          $stmt->bind_param("siss", $title, $assigned_to, $due_date, $priority);
          if ($stmt->execute()) {
            $task_id = $conn->insert_id;
            $stmt->close();

            // Create a messages row to notify the user in-app (best-effort)
            $admin_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
            $msg_text = sprintf("You have been assigned a new task: %s (Task ID: %d)", $title, $task_id);
            $mstmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, recipient_role, recipient_user_id, message) VALUES ('admin', ?, 'user', ?, ?)");
            if ($mstmt) {
              $mstmt->bind_param("iis", $admin_id, $assigned_to, $msg_text);
              $mstmt->execute();
              $mstmt->close();
            }

            // Send an email notification to the assigned user (best-effort)
            notify_recipient_by_email('user', $assigned_to, sprintf('New task assigned: %s', $title), $msg_text);
          } else {
            $stmt->close();
          }
        }
    }

    // Mark task as completed
    if (isset($_POST['complete_id'])) {
        $id = (int)$_POST['complete_id'];
        $stmt = $conn->prepare("UPDATE tasks SET status = 'success' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Delete task
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
// Fetch users
$users = [];
$stmt = $conn->prepare("SELECT id, name FROM users");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $users[] = $row;
}
$stmt->close();

// Fetch recent tasks (include assignee id for messaging)
$tasks = [];
$stmt = $conn->prepare("
    SELECT t.id, t.title, u.name AS assignee, t.assigned_to AS assignee_id, t.due_date, t.priority, t.status
    FROM tasks t
    JOIN users u ON u.id = t.assigned_to
    ORDER BY t.created_at DESC
    LIMIT 50
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

// Fetch recent messages sent to admin (for initial display)
$adminMessages = [];
$stmt = $conn->prepare("SELECT id, sender_role, sender_id, message, created_at FROM messages WHERE recipient_role = 'admin' ORDER BY created_at DESC LIMIT 20");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $adminMessages[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Assign Task</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .icon-btn {
      background: none;
      border: none;
      font-size: 18px;
      cursor: pointer;
      margin: 0 4px;
    }
    .icon-btn.complete:hover { color: limegreen; }
    .icon-btn.delete:hover { color: red; }
  </style>
</head>
<body class="panel">
  <header class="topbar">
    <div class="brand">Admin Panel</div>
    <div class="right">
      <span class="user-pill"><?= e($_SESSION['name']) ?> (<?= e($_SESSION['role']) ?>)</span>
     
      <a class="btn btn-danger" href="logout.php">Logout</a>
    </div>
  </header>

  <div class="container">
    <h2>Assign Task</h2>
    <form class="card form" method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label>Task Title</label>
          <input type="text" name="title" placeholder="e.g., Prepare report" required>
        </div>
        <div class="form-group">
          <label>Assign To</label>
          <select name="assigned_to" required>
            <option value="">Select user</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" name="due_date">
        </div>
        <div class="form-group">
          <label>Priority</label>
          <select name="priority" required>
            <option value="Low">Low</option>
            <option value="Medium" selected>Medium</option>
            <option value="High">High</option>
          </select>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Save Task</button>
    </form>

    <h3 style="margin-top:32px;">Recent Tasks</h3>
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Assignee</th>
            <th>Due</th>
            <th>Priority</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$tasks): ?>
            <tr><td colspan="5" class="muted">No tasks yet.</td></tr>
          <?php else: ?>
            <?php foreach ($tasks as $t): ?>
              <tr>
                <td><?= e($t['title']) ?></td>
                <td><?= e($t['assignee']) ?></td>
                <td><?= e($t['due_date']) ?: '—' ?></td>
                <td><?= e($t['priority']) ?></td>
                <td>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="complete_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="icon-btn complete" title="Mark as completed">✔</button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="icon-btn delete" title="Delete task">🗑</button>
                  </form>

                  <!-- Admin -> User message -->
                  <div style="display:inline-block; margin-left:8px; vertical-align:middle;">
                    <button class="icon-btn" onclick="toggleAdminMsgForm(<?= $t['assignee_id'] ?>, <?= $t['id'] ?>)" title="Message user">💬</button>
                    <form method="POST" action="send_message.php" id="admin-msg-<?= $t['id'] ?>" style="display:none; margin-top:6px;">
                      <input type="hidden" name="recipient_role" value="user">
                      <input type="hidden" name="recipient_user_id" value="<?= $t['assignee_id'] ?>">
                      <textarea name="message" rows="2" placeholder="Message to user" style="width:240px"></textarea>
                      <div><button class="btn" type="submit">Send</button></div>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h3 style="margin-top:32px;">Messages to Admin</h3>
    <div id="messages" class="card">
      <ul>
        <?php if (!$adminMessages): ?>
          <li class="muted">No messages yet.</li>
        <?php else: ?>
          <?php foreach ($adminMessages as $m): ?>
            <li><strong><?= e($m['created_at']) ?></strong> — <?= e($m['message']) ?></li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>

  </div>

  <script>
    function toggleAdminMsgForm(userId, taskId) {
      var f = document.getElementById('admin-msg-' + taskId);
      if (!f) return;
      f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }

    // Poll for new messages every 2s
    setInterval(function(){
      fetch('fetch_messages.php')
        .then(res => res.json())
        .then(json => {
          if (json.ok && Array.isArray(json.messages)) {
            json.messages.forEach(m => {
              console.log('msg:', m.message);
              // append to UI
              var ul = document.querySelector('#messages ul');
              if (ul) {
                var li = document.createElement('li');
                li.textContent = m.created_at + ' - ' + m.message;
                ul.insertBefore(li, ul.firstChild);
              }
            });
          }
        }).catch(err => console.error(err));
    }, 2000);
  </script>

</body>
</html>
