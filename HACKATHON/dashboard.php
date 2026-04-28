<?php
require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireRole(['student','program_head','dean','admin']);

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$periodStmt = $pdo->query("SELECT * FROM evaluation_periods WHERE is_active = 1 ORDER BY end_date DESC LIMIT 1");
$activePeriod = $periodStmt->fetch();
$periodId = $activePeriod['id'] ?? null;

$teachers = [];
if ($role === 'student') {
    $stmt = $pdo->prepare("SELECT t.* FROM teachers t JOIN users u ON t.program_id = u.program_id WHERE u.id = ? AND t.is_active = 1");
    $stmt->execute([$userId]);
    $teachers = $stmt->fetchAll();
} elseif ($role === 'program_head') {
    $stmt = $pdo->prepare("SELECT t.* FROM teachers t JOIN users u ON t.department_id = u.department_id WHERE u.id = ? AND t.is_active = 1");
    $stmt->execute([$userId]);
    $teachers = $stmt->fetchAll();
} elseif (in_array($role, ['dean','admin'])) {
    $teachers = $pdo->query("SELECT * FROM teachers WHERE is_active = 1")->fetchAll();
}

// Compute scores for dashboard charts
$scoreData = [];
foreach ($teachers as $t) {
    $scoreData[$t['id']] = getTeacherScore($t['id'], $periodId);
}

// Check if user already evaluated each teacher
$evaluated = [];
if ($role !== 'admin') {
    $stmt = $pdo->prepare("SELECT teacher_id FROM evaluations WHERE rater_id = ? AND evaluation_period_id = ?");
    $stmt->execute([$userId, $periodId]);
    $evaluated = array_column($stmt->fetchAll(), 'teacher_id');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Teacher Evaluation</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar">
    <div class="brand">EvalSystem</div>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <?php if ($role === 'admin'): ?>
        <li><a href="admin/teachers.php">Teachers</a></li>
        <li><a href="admin/periods.php">Periods</a></li>
        <li><a href="admin/users.php">Users</a></li>
        <li><a href="admin/audit.php">Audit Logs</a></li>
        <li><a href="admin/reminders.php">Reminders</a></li>
        <?php endif; ?>
        <?php if ($role !== 'student'): ?>
        <li><a href="reports.php">Reports</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a></li>
    </ul>
</nav>

<div class="container">
    <h2>Dashboard</h2>
    <p>Role: <strong><?php echo ucfirst($role); ?></strong>
    <?php if ($activePeriod): ?> | Active Period: <strong><?php echo htmlspecialchars($activePeriod['title']); ?></strong> (<?php echo $activePeriod['start_date']; ?> to <?php echo $activePeriod['end_date']; ?>)<?php endif; ?></p>

    <?php if ($role === 'admin'): ?>
    <div class="cards">
        <div class="card">
            <h3>Total Teachers</h3>
            <p class="big"><?php echo count($teachers); ?></p>
        </div>
        <div class="card">
            <h3>Evaluations Submitted</h3>
            <p class="big"><?php echo $pdo->query("SELECT COUNT(*) FROM evaluations" . ($periodId ? " WHERE evaluation_period_id = $periodId" : ""))->fetchColumn(); ?></p>
        </div>
        <div class="card">
            <h3>Pending Emails</h3>
            <p class="big"><?php echo $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn(); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <h3>Teachers</h3>
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Department</th><th>Program</th>
            <?php if ($role !== 'student'): ?><th>Student Avg</th><th>PH Avg</th><th>Dean Avg</th><th>Composite</th><?php endif; ?>
            <th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($teachers as $t): 
            $dept = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
            $dept->execute([$t['department_id']]);
            $deptName = $dept->fetchColumn() ?? 'N/A';
            $prog = $pdo->prepare("SELECT name FROM programs WHERE id = ?");
            $prog->execute([$t['program_id'] ?? 0]);
            $progName = $prog->fetchColumn() ?? 'All';
            $sc = $scoreData[$t['id']] ?? null;
        ?>
        <tr>
            <td><?php echo htmlspecialchars($t['full_name']); ?></td>
            <td><?php echo htmlspecialchars($deptName); ?></td>
            <td><?php echo htmlspecialchars($progName); ?></td>
            <?php if ($role !== 'student'): ?>
            <td><?php echo $sc ? $sc['student_avg'] . " (n=" . $sc['student_count'] . ")" : '-'; ?></td>
            <td><?php echo $sc ? $sc['ph_avg'] . " (n=" . $sc['ph_count'] . ")" : '-'; ?></td>
            <td><?php echo $sc ? $sc['dean_avg'] . " (n=" . $sc['dean_count'] . ")" : '-'; ?></td>
            <td><strong><?php echo $sc ? $sc['composite'] : '-'; ?></strong></td>
            <?php endif; ?>
            <td>
                <?php if ($role !== 'admin'): ?>
                    <?php if (in_array($t['id'], $evaluated)): ?>
                        <span class="badge success">Evaluated</span>
                    <?php else: ?>
                        <a class="btn" href="evaluate.php?teacher_id=<?php echo $t['id']; ?>">Evaluate</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn" href="reports.php?teacher_id=<?php echo $t['id']; ?>">View Report</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (in_array($role, ['admin','dean','program_head'])): ?>
    <h3>Performance Overview</h3>
    <div class="chart-container">
        <canvas id="scoreChart"></canvas>
    </div>
    <script>
    const ctx = document.getElementById('scoreChart').getContext('2d');
    const labels = <?php echo json_encode(array_map(fn($t)=>$t['full_name'], $teachers)); ?>;
    const compositeScores = <?php echo json_encode(array_map(fn($t)=>($scoreData[$t['id']]['composite'] ?? 0), $teachers)); ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Composite Score',
                data: compositeScores,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 5 } }
        }
    });
    </script>
    <?php endif; ?>
</div>
</body>
</html>

