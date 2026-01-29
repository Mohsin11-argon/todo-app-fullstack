<?php
// assign_task.php
// Requires: db.php (which loads mail_config.php internally)

require_once __DIR__ . '/db.php';

// Only admins can access this page
require_role('admin');

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $title      = trim($_POST['title'] ?? '');
    $assignedTo = (int)($_POST['assigned_to'] ?? 0);
    $dueDate    = trim($_POST['due_date'] ?? ''); // YYYY-MM-DD
    $priority   = trim($_POST['priority'] ?? 'Medium');

    // Validation
    $errors = [];

    if ($title === '') {
        $errors[] = 'Task title is required.';
    }

    if ($assignedTo <= 0) {
        $errors[] = 'A valid user must be selected.';
    }

    if ($priority === '') {
        $errors[] = 'Priority is required.';
    }

    if ($dueDate !== '') {
        $parts = explode('-', $dueDate);
        if (
            count($parts) !== 3 ||
            !checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])
        ) {
            $errors[] = 'Due date must be a valid date (YYYY-MM-DD).';
        }
    }

    // If validation fails
    if (!empty($errors)) {
        $_SESSION['assign_errors'] = $errors;
        header('Location: admin.php?tab=assign');
        exit;
    }

    // Insert task
    $stmt = $conn->prepare("
        INSERT INTO tasks (title, assigned_to, due_date, priority, status, created_at)
        VALUES (?, ?, ?, ?, 'assigned', NOW())
    ");
    $stmt->bind_param("siss", $title, $assignedTo, $dueDate, $priority);

    if (!$stmt->execute()) {
        $_SESSION['assign_errors'] = ['Failed to assign task. Please try again.'];
        header('Location: admin.php?tab=assign');
        exit;
    }

    $taskId = $stmt->insert_id;
    $stmt->close();

    // SEND EMAIL TO ASSIGNED USER
    $subject = "New Task Assigned: {$title}";

    $body  = "Hello,\n\n";
    $body .= "A new task has been assigned to you.\n\n";
    $body .= "Task ID: {$taskId}\n";
    $body .= "Title: {$title}\n";
    $body .= "Priority: {$priority}\n";

    if ($dueDate !== '') {
        $body .= "Due Date: {$dueDate}\n";
    }

    $body .= "\nPlease log in to the ToDo App to view and complete this task.\n\n";
    $body .= "Regards,\nAdmin";

    // Best-effort email (task assignment should succeed even if email fails)
    $emailSent = notify_recipient_by_email(
        'user',
        $assignedTo,
        $subject,
        $body
    );

    // Feedback message
    $_SESSION['assign_success'] = $emailSent
        ? "Task #{$taskId} assigned successfully and email sent to user."
        : "Task #{$taskId} assigned successfully, but email could not be sent.";

    header('Location: admin.php?tab=assign');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Task</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<h1>Assign Task</h1>

<?php if (!empty($_SESSION['assign_errors'])): ?>
    <div class="error">
        <ul>
            <?php foreach ($_SESSION['assign_errors'] as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['assign_errors']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['assign_success'])): ?>
    <div class="success">
        <?php echo e($_SESSION['assign_success']); ?>
    </div>
    <?php unset($_SESSION['assign_success']); ?>
<?php endif; ?>

<form method="post" action="assign_task.php">

    <label>Task Title</label>
    <input type="text" name="title" required>

    <label>Assign To (User)</label>
    <select name="assigned_to" required>
        <option value="">Select user</option>
        <?php
        $res = $conn->query("SELECT id, name, email FROM users ORDER BY name ASC");
        while ($u = $res->fetch_assoc()):
        ?>
            <option value="<?php echo (int)$u['id']; ?>">
                <?php echo e(($u['name'] ?? 'User') . ' - ' . $u['email']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Due Date</label>
    <input type="date" name="due_date">

    <label>Priority</label>
    <select name="priority" required>
        <option value="Low">Low</option>
        <option value="Medium" selected>Medium</option>
        <option value="High">High</option>
        <option value="Urgent">Urgent</option>
    </select>

    <button type="submit">Save Task</button>
</form>

</body>
</html>
