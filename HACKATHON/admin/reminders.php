<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminders'])) {
    sendReminderEmails();
    $msg = "Reminder emails queued successfully.";
    auditLog($_SESSION['user_id'], 'REMINDERS_QUEUED', "Admin triggered reminder emails");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_queue'])) {
    $pending = $pdo->query("SELECT q.*, u.email, u.full_name FROM email_queue q JOIN users u ON q.user_id = u.id WHERE q.status = 'pending' LIMIT 50")->fetchAll();
    $sent = 0;
    foreach ($pending as $q) {
        $headers = "From: noreply@school.edu\r\nContent-Type: text/plain; charset=UTF-8";
        if (mail($q['email'], $q['subject'], $q['body'], $headers)) {
            $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$q['id']]);
            $sent++;
        } else {
            $pdo->prepare("UPDATE email_queue SET status = 'failed' WHERE id = ?")->execute([$q['id']]);
        }
    }
    $msg = "Processed $sent emails.";
    auditLog($_SESSION['user_id'], 'EMAILS_PROCESSED', "Processed $sent reminder emails");
}

$pendingCount = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
$sentCount = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Reminders</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="brand">EvalSystem</div>
    <ul>
        <li><a href="../dashboard.php">Dashboard</a></li>
        <li><a href="teachers.php">Teachers</a></li>
        <li><a href="periods.php">Periods</a></li>
        <li><a href="users.php">Users</a></li>
        <li><a href="audit.php">Audit Logs</a></li>
        <li><a href="reminders.php">Reminders</a></li>
        <li><a href="../logout.php">Logout</a></li>
    </ul>
</nav>
<div class="container">
    <h2>Automated Email Reminders</h2>
    <?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
    <div class="cards">
        <div class="card"><h3>Pending</h3><p class="big"><?php echo $pendingCount; ?></p></div>
        <div class="card"><h3>Sent</h3><p class="big"><?php echo $sentCount; ?></p></div>
    </div>
    <form method="POST" action="">
        <button type="submit" name="send_reminders" class="btn">Queue Reminders for Non-Respondents</button>
    </form>
    <form method="POST" action="" style="margin-top:1rem;">
        <button type="submit" name="process_queue" class="btn">Process Pending Emails (Send via mail())</button>
    </form>
    <p class="small">You can also set up a cron job to hit this page or call sendReminderEmails() automatically.</p>
</div>
</body>
</html>

