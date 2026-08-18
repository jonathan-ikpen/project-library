<?php
// auth/profile.php
require_once __DIR__ . '/../middleware/auth-check.php';

global $pdo;
$error = '';
$success = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        // Check if email is already taken by someone else
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $_SESSION['user_id']]);
        if ($checkStmt->fetch()) {
            $error = 'This email is already in use.';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters long.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
                    if ($updateStmt->execute([$name, $email, $hash, $_SESSION['user_id']])) {
                        $success = 'Profile updated successfully.';
                        $_SESSION['name'] = $name;
                        $user['name'] = $name;
                        $user['email'] = $email;
                    }
                }
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                if ($updateStmt->execute([$name, $email, $_SESSION['user_id']])) {
                    $success = 'Profile updated successfully.';
                    $_SESSION['name'] = $name;
                    $user['name'] = $name;
                    $user['email'] = $email;
                }
            }
        }
    }
}

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <?php
    $back_link = '/';
    if ($user['role'] === 'admin') $back_link = '/admin/dashboard.php';
    if ($user['role'] === 'supervisor') $back_link = '/supervisor/dashboard.php';
    if ($user['role'] === 'student') $back_link = '/student/dashboard.php';
    ?>
    <a href="<?= BASE_URL . ltrim($back_link, '/') ?>" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
    <h1>Profile Settings</h1>
    <p class="subtext">Update your account details and password.</p>
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

<div class="panel" style="max-width: 600px;">
    <form method="POST" action="">
        <div class="mb-3">
            <label class="label mb-1" style="display:block;">Full Name</label>
            <input type="text" name="name" value="<?= h($user['name']) ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="label mb-1" style="display:block;">Email Address</label>
            <input type="email" name="email" value="<?= h($user['email']) ?>" required>
        </div>
        
        <div class="mb-4">
            <label class="label mb-1" style="display:block;">New Password (leave blank to keep current)</label>
            <input type="password" name="password" minlength="8">
        </div>

        <button type="submit" style="width: 100%;">Update Profile</button>
    </form>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
