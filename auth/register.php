<?php
// auth/register.php
require_once __DIR__ . '/../config/init.php';

if (is_logged_in()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        global $pdo;
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
            if ($stmt->execute([$name, $email, $hash])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'An error occurred during registration.';
            }
        }
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<div style="max-width: 400px; margin: 64px auto;">
    <div class="panel">
        <h2 style="text-align: center;">Student Registration</h2>
        
        <?php if ($error): ?>
            <div class="mb-3" style="color: var(--danger); font-size: 14px; text-align: center; border: 1px solid var(--danger); padding: 8px; border-radius: 8px;">
                <?= h($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-3" style="color: var(--success); font-size: 14px; text-align: center; border: 1px solid var(--success); padding: 8px; border-radius: 8px;">
                <?= h($success) ?>
            </div>
            <a href="login.php" class="btn" style="width: 100%;">Go to Login</a>
        <?php else: ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Full Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="mb-3">
                    <label class="label mb-1" style="display:block;">Email Address</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="mb-4">
                    <label class="label mb-1" style="display:block;">Password</label>
                    <input type="password" name="password" required minlength="8">
                </div>

                <button type="submit" style="width: 100%;">Register</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
