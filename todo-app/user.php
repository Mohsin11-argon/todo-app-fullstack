<?php
// user.php
require_once 'db.php';
require_role('user');

$user_id = $_SESSION['user_id'];

// Handle status updates and messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User sends a custom message to admin (from UI textarea)
    if (isset($_POST['send_message']) && trim($_POST['message'] ?? '') !== '') {
        $message = trim($_POST['message']);
        $stmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, recipient_role, message) VALUES ('user', ?, 'admin', ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
      // send email notification to admin (best-effort)
      $subject = sprintf("Message from %s", e($_SESSION['name']));
      notify_recipient_by_email('admin', 0, $subject, $message);
      $_SESSION['flash_status_report'] = "Message sent to administration.";
        header("Location: user.php");
        exit;
    }

    if (isset($_POST['complete_id'])) {
        $id = (int)$_POST['complete_id'];
        // Update status
        $stmt = $conn->prepare("UPDATE tasks SET status = 'success' WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Fetch title for message
        $title = '';
        $stmt = $conn->prepare("SELECT title FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $title = $r['title'];
        $stmt->close();

        // Insert admin notification
        $msg = sprintf("User %s completed task #%d: %s", e($_SESSION['name']), $id, $title);
        $stmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, recipient_role, message) VALUES ('user', ?, 'admin', ?)");
        $stmt->bind_param("is", $user_id, $msg);
        $stmt->execute();
        $stmt->close();

        // notify admin by email (best-effort)
        notify_recipient_by_email('admin', 0, sprintf("Task update from %s", e($_SESSION['name'])), $msg);

        // Show JS alert on user page
        $_SESSION['flash_status_report'] = "Report submitted to the administration";
        header("Location: user.php");
        exit;
    }

    if (isset($_POST['process_id'])) {
        $id = (int)$_POST['process_id'];
        $stmt = $conn->prepare("UPDATE tasks SET status = 'in_process' WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Fetch title for message
        $title = '';
        $stmt = $conn->prepare("SELECT title FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) $title = $r['title'];
        $stmt->close();

        // Insert admin notification
        $msg = sprintf("User %s reported task #%d: %s as NOT completed", e($_SESSION['name']), $id, $title);
        $stmt = $conn->prepare("INSERT INTO messages (sender_role, sender_id, recipient_role, message) VALUES ('user', ?, 'admin', ?)");
        $stmt->bind_param("is", $user_id, $msg);
        $stmt->execute();
        $stmt->close();

        // notify admin by email (best-effort)
        notify_recipient_by_email('admin', 0, sprintf("Task update from %s", e($_SESSION['name'])), $msg);

        $_SESSION['flash_status_report'] = "Report submitted to the administration";
        header("Location: user.php");
        exit;
    }
}

// Fetch pending tasks
$pendingTasks = [];
$stmt = $conn->prepare("
    SELECT id, title, status, due_date
    FROM tasks
    WHERE assigned_to = ? AND status = 'pending'
    ORDER BY due_date ASC, created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pendingTasks[] = $row;
}
$stmt->close();

// Fetch completed/in-process tasks
$pastTasks = [];
$stmt = $conn->prepare("
    SELECT title, status, DATE(created_at) AS created_on
    FROM tasks
    WHERE assigned_to = ? AND status != 'pending'
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pastTasks[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Panel - ToDo App</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .icon-btn {
      background: none;
      border: none;
      cursor: pointer;
      margin: 0 4px;
    }
    .complete-btn:hover { color: limegreen; }
    .process-btn:hover { color: orange; }
  </style>
</head>
<body class="panel dark">
  <header class="topbar dark">
    <div class="brand">To Do List</div>
    <div class="right">
      <span class="user-pill"><?= e($_SESSION['name']) ?> (<?= e($_SESSION['role']) ?>)</span>
      <a class="btn btn-danger" href="logout.php">Logout</a>
    </div>
  </header>

  <div class="container">
    <section class="card dark">
      <h2>Pending Tasks</h2>
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Title</th>
            <th>Due Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$pendingTasks): ?>
            <tr><td colspan="4" class="muted">No pending tasks.</td></tr>
          <?php else: ?>
            <?php foreach ($pendingTasks as $t): ?>
              <tr>
                <td><?= e($_SESSION['name']) ?></td>
                <td><?= e($t['title']) ?></td>
                <td><?= e($t['due_date']) ?: '—' ?></td>
                <td>
                  <!-- Complete checkbox -->
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="complete_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="icon-btn complete-btn" title="Mark completed"><i class="fa-solid fa-check"></i></button>
                  </form>
                  <!-- In Process checkbox -->
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="process_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="icon-btn process-btn" title="Report not completed"><i class="fa-solid fa-xmark"></i></button>
                  </form>

                  <!-- Send message to admin -->
                  <div style="display:inline-block; margin-left:8px; vertical-align:middle;">
                    <button class="icon-btn" onclick="toggleMsgForm(<?= $t['id'] ?>)" title="Message admin">💬</button>
                    <form method="POST" id="msgform-<?= $t['id'] ?>" style="display:none; margin-top:6px;">
                      <input type="hidden" name="send_message" value="1">
                      <textarea name="message" rows="2" placeholder="Message to admin about this task" style="width:240px"></textarea>
                      <div><button class="btn" type="submit">Send</button></div>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <br>
      <a class="btn btn-danger" href="logout.php">submit</a>
      
    </section>
    <section class="card dark">

      <h2>Past Activity</h2>
      <ul class="activity">
        <?php if (!$pastTasks): ?>
          <li class="muted">No past tasks found.</li>
        <?php else: ?>
          <?php foreach ($pastTasks as $t): ?>
            <li>
              <span class="date"><?= e($t['created_on']) ?></span> - 
              <span class="title"><?= e($t['title']) ?></span>
              <span class="status"> (<?= e($t['status']) ?>)</span>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </section>
  </div>

  <script>
    function toggleMsgForm(id) {
      var f = document.getElementById('msgform-' + id);
      if (!f) return;
      f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
  </script>

  <?php if (!empty($_SESSION['flash_status_report'])): ?>
    <script>
      (function(){
        var msg = "<?= e($_SESSION['flash_status_report']) ?>";
        var container = document.querySelector('.container');
        if (container) {
          var note = document.createElement('div');
          note.className = 'notice';
          note.textContent = msg;
          note.style.padding = '8px 12px';
          note.style.background = '#eef7ff';
          note.style.border = '1px solid #b9e0ff';
          note.style.marginBottom = '12px';
          container.insertBefore(note, container.firstChild);
        } else {
          console.log(msg);
        }
      })();
    </script>
  <?php unset($_SESSION['flash_status_report']); endif; ?>

</body>
</html>
