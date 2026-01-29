<?php
// complete_task.php
// Requires: db.php, mail.config.php, tasks table with status column
require_once __DIR__ . '/db.php';

// Both admin and user can trigger completion depending on your rules.
// If only users can mark complete, enforce:
require_role('user');

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId  = intval($_POST['task_id'] ?? 0);
    $userId  = intval($_SESSION['user_id'] ?? 0);

    if ($taskId <= 0 || $userId <= 0) {
        $_SESSION['complete_error'] = 'Invalid task or user.';
        header('Location: user.php'); // adjust to your user panel route
        exit;
    }

    // Ensure the task belongs to this user (security check)
    $stmt = $conn->prepare("SELECT title, assigned_to FROM tasks WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $res = $stmt->get_result();
    $task = $res->fetch_assoc();
    $stmt->close();

    if (!$task) {
        $_SESSION['complete_error'] = 'Task not found.';
        header('Location: user.php');
        exit;
    }
    if ((int)$task['assigned_to'] !== $userId) {
        $_SESSION['complete_error'] = 'You are not authorized to complete this task.';
        header('Location: user.php');
        exit;
    }

    // Mark as completed
    $stmt = $conn->prepare("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $stmt->execute();
    $stmt->close();

    // Notify admin via Gmail
    $subject = "Task Completed: {$task['title']}";
    $body    = "Hello Admin,\n\nA task has been completed.\n\n"
             . "Title: {$task['title']}\n"
             . "Completed by User ID: {$userId}\n"
             . "Task ID: {$taskId}\n\n"
             . "Please log in to the ToDo App to review details.\n\n"
             . "Regards,\nToDo App";

    $sent = notify_recipient_by_email('admin', null, $subject, $body);

    $_SESSION['complete_success'] = $sent
        ? "Task #{$taskId} marked completed. Email sent to admin."
        : "Task #{$taskId} marked completed, but email could not be sent.";

    header('Location: user.php'); // adjust to your user panel route
    exit;
}

// If GET, render a minimal completion form (optional)
require_role('user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Complete Task</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<h1>Complete Task</h1>

<?php if (!empty($_SESSION['complete_error'])): ?>
<div class="error">
    <?php echo e($_SESSION['complete_error']); unset($_SESSION['complete_error']); ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['complete_success'])): ?>
<div class="success">
    <?php echo e($_SESSION['complete_success']); unset($_SESSION['complete_success']); ?>
</div>
<?php endif; ?>

<form method="post" action="complete_task.php">
    <label>Task</label>
    <select name="task_id" required>
        <option value="">Select a task</option>
        <?php
        // List tasks assigned to the logged-in user that are not yet completed
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $res = $conn->prepare("SELECT id, title FROM tasks WHERE assigned_to = ? AND (status IS NULL OR status != 'completed') ORDER BY id DESC");
        $res->bind_param("i", $uid);
        $res->execute();
        $rows = $res->get_result();
        while ($t = $rows->fetch_assoc()):
        ?>
            <option value="<?php echo (int)$t['id']; ?>">
                <?php echo e($t['title']); ?>
            </option>
        <?php endwhile; $res->close(); ?>
    </select>

    <button type="submit">Mark Completed</button>
</form>
</body>
</html>
