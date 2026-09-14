<?php
// admin/students.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_student') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $mat_no = trim($_POST['mat_no'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Name, Email, and Password are required.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, level, mat_no, year) VALUES (?, ?, ?, 'student', ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hash, $level, $mat_no, $year ?: null])) {
                    $success = 'Student account created successfully.';
                } else {
                    $error = 'Database error.';
                }
            }
        }
    } elseif ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = "User account deleted successfully.";
            } else {
                $error = "Failed to delete user account.";
            }
        }
    } elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id === $_SESSION['user_id']) {
            $error = "You cannot suspend your own account.";
        } else {
            // Get current status
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_status = $stmt->fetchColumn();
            
            if ($current_status) {
                $new_status = ($current_status === 'active') ? 'suspended' : 'active';
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                if ($stmt->execute([$new_status, $user_id])) {
                    $success = "User status updated to $new_status.";
                } else {
                    $error = "Failed to update user status.";
                }
            }
        }
    }
}

// Fetch supervisors for filter
$stmtSupervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name ASC");
$supervisors = $stmtSupervisors->fetchAll();

// Fetch available years from users and merge with predefined
$predefined_years = range(date('Y'), date('Y') - 5);
$stmtYears = $pdo->query("SELECT DISTINCT year FROM users WHERE role = 'student' AND year IS NOT NULL ORDER BY year DESC");
$db_years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
$years = array_unique(array_merge($predefined_years, $db_years));
rsort($years);

// Fetch available levels and merge with predefined
$predefined_levels = ['M22', 'M23', 'M24', 'M25', 'M26'];
$stmtLevels = $pdo->query("SELECT DISTINCT level FROM users WHERE role = 'student' AND level IS NOT NULL AND level != '' ORDER BY level ASC");
$db_levels = $stmtLevels->fetchAll(PDO::FETCH_COLUMN);
$levels = array_unique(array_merge($predefined_levels, $db_levels));
sort($levels);

// Filters
$q = trim($_GET['q'] ?? '');
$level_filter = trim($_GET['level'] ?? '');
$year_filter = trim($_GET['year'] ?? '');
$supervisor_id = (int)($_GET['supervisor_id'] ?? 0);

$query = "SELECT DISTINCT u.id, u.name, u.email, u.status, u.created_at, u.level FROM users u ";
$params = [];

if ($supervisor_id) {
    $query .= " LEFT JOIN projects p ON p.student_id = u.id ";
}

$query .= " WHERE u.role = 'student' ";

if ($q) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?) ";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($level_filter) {
    $query .= " AND u.level = ? ";
    $params[] = $level_filter;
}
if ($year_filter) {
    $query .= " AND u.year = ? ";
    $params[] = $year_filter;
}
if ($supervisor_id) {
    $query .= " AND p.supervisor_id = ? ";
    $params[] = $supervisor_id;
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="dashboard.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
    <h1>Manage Students</h1>
    <p class="subtext">View existing students and provision new student accounts.</p>
</div>

<?php if ($error): ?>
    <div class="mb-3" style="color: var(--danger); font-size: 14px; border: 1px solid var(--danger); padding: 12px; border-radius: 8px;">
        <?= h($error) ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-3" style="color: var(--success); font-size: 14px; border: 1px solid var(--success); padding: 12px; border-radius: 8px;">
        <?= h($success) ?>
    </div>
<?php endif; ?>

<div class="grid-auto-fit mb-4">
    <div class="panel">
        <h3 class="mb-3">Create Student</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create_student">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Email Address</label>
                    <input type="email" name="email" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Matriculation No.</label>
                    <input type="text" name="mat_no" placeholder="e.g. M.23/ND/CSIT/11533" required>
                </div>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Level</label>
                    <select name="level" required>
                        <option value="">Select</option>
                        <?php foreach ($levels as $l): ?>
                            <option value="<?= h($l) ?>"><?= h($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Year</label>
                    <input type="number" name="year" placeholder="e.g. 2024" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Temporary Password</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <button type="submit" style="width: 100%;">Create Account</button>
        </form>
    </div>
</div>

<form method="GET" action="" class="mb-4 filter-form" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: end;">
    <div style="flex: 1; min-width: 200px;">
        <label class="label mb-1" style="display:block;">Search Students</label>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name or email..." style="margin-bottom: 0; height: 54px; padding: 0 20px; box-sizing: border-box; width: 100%;">
    </div>
    <div style="min-width: 120px;">
        <label class="label mb-1" style="display:block;">Level</label>
        <select name="level" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <?php foreach ($levels as $l): ?>
                <option value="<?= h($l) ?>" <?= $level_filter === $l ? 'selected' : '' ?>><?= h($l) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width: 120px;">
        <label class="label mb-1" style="display:block;">Year</label>
        <select name="year" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= h($y) ?>" <?= (string)$year_filter === (string)$y ? 'selected' : '' ?>><?= h($y) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width: 150px;">
        <label class="label mb-1" style="display:block;">Supervisor</label>
        <select name="supervisor_id" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Supervisors</option>
            <?php foreach ($supervisors as $sup): ?>
                <option value="<?= $sup['id'] ?>" <?= $supervisor_id == $sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn" style="height: 54px; padding: 0 32px; margin-bottom: 0; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;">Filter</button>
</form>

<div class="panel">
    <h3 class="mb-3">All Students</h3>
    <?php if (empty($users)): ?>
        <?= render_empty_state("No Students Found", "There are currently no student accounts.") ?>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--line);">
                        <th style="padding: 12px 8px;" class="eyebrow">Name</th>
                        <th style="padding: 12px 8px;" class="eyebrow">Email</th>
                        <th style="padding: 12px 8px;" class="eyebrow">Level</th>
                        <th style="padding: 12px 8px;" class="eyebrow">Status</th>
                        <th style="padding: 12px 8px;" class="eyebrow">Joined</th>
                        <th style="padding: 12px 8px; text-align: right;" class="eyebrow">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid var(--line);">
                            <td style="padding: 16px 8px; font-weight: 500;"><?= h($user['name']) ?></td>
                            <td style="padding: 16px 8px; color: var(--text-secondary);"><?= h($user['email']) ?></td>
                            <td style="padding: 16px 8px; color: var(--text-secondary);"><?= h($user['level'] ?? '-') ?></td>
                            <td style="padding: 16px 8px;">
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge" style="background: var(--success); color: white; border-color: var(--success);">Active</span>
                                <?php else: ?>
                                    <span class="badge" style="background: var(--danger); color: white; border-color: var(--danger);">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px 8px; color: var(--text-secondary); font-size: 14px;"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px;">Edit</a>
                                    
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px;">Suspend</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px; color: var(--success); border-color: var(--success);">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <form method="POST" action="" style="margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete this user? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 13px; color: var(--danger); border-color: var(--danger);">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
