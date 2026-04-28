<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = trim($_POST['email']);
    $name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $programId = $_POST['program_id'] ?? null;
    $deptId = $_POST['department_id'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, role, program_id, department_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$email, $password, $name, $role, $programId ?: null, $deptId ?: null]);
    auditLog($_SESSION['user_id'], 'USER_CREATE', "Created user $email as $role");
    $msg = "User added.";
}

if (isset($_GET['toggle'])) {
    $uid = intval($_GET['toggle']);
    $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$uid]);
    auditLog($_SESSION['user_id'], 'USER_TOGGLE', "Toggled user $uid");
    header("Location: users.php"); exit;
}

$users = $pdo->query("SELECT u.*, p.name as program_name, d.name as dept_name FROM users u LEFT JOIN programs p ON u.program_id = p.id LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.id DESC")->fetchAll();
$programs = $pdo->query("SELECT * FROM programs")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
    <h2>Manage Users</h2>
    <?php if ($msg): ?><div class="alert success"><?php echo $msg; ?></div><?php endif; ?>
    <form method="POST" action="" class="filter-form">
        <div><label>Email</label><input type="email" name="email" required></div>
        <div><label>Full Name</label><input type="text" name="full_name" required></div>
        <div><label>Password</label><input type="password" name="password" required></div>
        <div><label>Role</label>
            <select name="role" required>
                <option value="student">Student</option>
                <option value="program_head">Program Head</option>
                <option value="dean">Dean</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div><label>Program</label>
            <select name="program_id"><option value="">None</option>
                <?php foreach ($programs as $pr): ?><option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label>Department</label>
            <select name="department_id"><option value="">None</option>
                <?php foreach ($departments as $de): ?><option value="<?php echo $de['id']; ?>"><?php echo htmlspecialchars($de['name']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit" name="add_user" class="btn">Add User</button></div>
    </form>
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Program</th><th>Dept</th><th>Active</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo ucfirst($u['role']); ?></td>
            <td><?php echo htmlspecialchars($u['program_name'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($u['dept_name'] ?? '-'); ?></td>
            <td><?php echo $u['is_active'] ? 'Yes' : 'No'; ?></td>
            <td><a class="btn" href="?toggle=<?php echo $u['id']; ?>">Toggle</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>

