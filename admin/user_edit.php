<?php
// admin/user_edit.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('/admin/dashboard.php');
}

$error = '';
$success = '';

// Fetch the user before handling POST so we know their original details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/admin/dashboard.php');
}

$role_display = ucfirst($user['role']);
$back_url = ($user['role'] === 'student') ? 'students.php' : 'supervisors.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $mat_no = trim($_POST['mat_no'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error = 'Name and Email are required fields.';
        } else {
            // Check if email belongs to another user
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmtCheck->execute([$email, $id]);
            if ($stmtCheck->fetch()) {
                $error = 'Email address is already in use by another account.';
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        $error = 'Password must be at least 8 characters long.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $level = $user['role'] === 'student' ? $level : $user['level'];
                        $mat_no = $user['role'] === 'student' ? $mat_no : $user['mat_no'];
                        $year = $user['role'] === 'student' ? ($year ?: null) : $user['year'];
                        $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, email = ?, level = ?, mat_no = ?, year = ?, password_hash = ? WHERE id = ?");
                        if ($stmtUpdate->execute([$name, $email, $level, $mat_no, $year, $hash, $id])) {
                            $success = 'Profile and password updated successfully.';
                            $user['name'] = $name;
                            $user['email'] = $email;
                            $user['level'] = $level;
                            $user['mat_no'] = $mat_no;
                            $user['year'] = $year;
                        } else {
                            $error = 'Database error updating profile.';
                        }
                    }
                } else {
                    $level = $user['role'] === 'student' ? $level : $user['level'];
                    $mat_no = $user['role'] === 'student' ? $mat_no : $user['mat_no'];
                    $year = $user['role'] === 'student' ? ($year ?: null) : $user['year'];
                    $stmtUpdate = $pdo->prepare("UPDATE users SET name = ?, email = ?, level = ?, mat_no = ?, year = ? WHERE id = ?");
                    if ($stmtUpdate->execute([$name, $email, $level, $mat_no, $year, $id])) {
                        $success = 'Profile updated successfully.';
                        $user['name'] = $name;
                        $user['email'] = $email;
                        $user['level'] = $level;
                        $user['mat_no'] = $mat_no;
                        $user['year'] = $year;
                    } else {
                        $error = 'Database error updating profile.';
                    }
                }
            }
        }
    }
}

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="<?= $back_url ?>" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to <?= $role_display ?>s</a>
    <h1>Edit <?= $role_display ?></h1>
    <p class="subtext">Modify the details or reset the password for this user.</p>
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
        <div class="flex-between mb-4">
            <h3 class="mb-0">Profile Information</h3>
            <span class="badge"><?= h($user['role']) ?></span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Full Name</label>
                <input type="text" name="name" value="<?= h($user['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Email Address</label>
                <input type="email" name="email" value="<?= h($user['email']) ?>" required>
            </div>
            
            <?php if ($user['role'] === 'student'): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Matriculation No.</label>
                    <input type="text" name="mat_no" value="<?= h($user['mat_no'] ?? '') ?>" placeholder="e.g. M.23/ND/CSIT/11533" required>
                </div>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Level</label>
                    <select name="level" required>
                        <option value="">Select Level</option>
                        <option value="M22" <?= ($user['level'] ?? '') === 'M22' ? 'selected' : '' ?>>M22</option>
                        <option value="M23" <?= ($user['level'] ?? '') === 'M23' ? 'selected' : '' ?>>M23</option>
                        <option value="M24" <?= ($user['level'] ?? '') === 'M24' ? 'selected' : '' ?>>M24</option>
                        <option value="M25" <?= ($user['level'] ?? '') === 'M25' ? 'selected' : '' ?>>M25</option>
                        <option value="M26" <?= ($user['level'] ?? '') === 'M26' ? 'selected' : '' ?>>M26</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Admission Year</label>
                    <input type="number" name="year" value="<?= h($user['year'] ?? '') ?>" placeholder="e.g. 2024" required>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="border-top: 1px solid var(--line); margin-top: 32px; padding-top: 32px; margin-bottom: 24px;">
                <h3 class="eyebrow mb-2">Security Override</h3>
                <p class="subtext mb-3">Leave the password field blank unless you wish to force a password reset for this user.</p>
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep unchanged">
                </div>
            </div>
            
            <button type="submit" style="width: 100%;">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
