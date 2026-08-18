<?php
// auth/login.php
require_once __DIR__ . '/../config/init.php';

if (is_logged_in()) {
    redirect('/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended by an administrator.';
            } else {
                // Prevent session fixation
                session_regenerate_id(true);
    
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
    
                // Route based on role
                if ($user['role'] === 'admin') redirect('/admin/dashboard.php');
                if ($user['role'] === 'supervisor') redirect('/supervisor/dashboard.php');
                if ($user['role'] === 'student') redirect('/student/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<div style="max-width: 400px; margin: 64px auto;">
    <div class="panel">
        <h2 style="text-align: center;">Login</h2>
        
        <?php if ($error): ?>
            <div class="mb-3" style="color: var(--danger); font-size: 14px; text-align: center; border: 1px solid var(--danger); padding: 8px; border-radius: 8px;">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Email Address</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="mb-4">
                <label class="label mb-1" style="display:block;">Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" style="width: 100%;">Sign In</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
