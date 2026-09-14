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
    $level = trim($_POST['level'] ?? '');
    $mat_no = trim($_POST['mat_no'] ?? '');
    $year = trim($_POST['year'] ?? '');
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
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, level, mat_no, year) VALUES (?, ?, ?, 'student', ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hash, $level, $mat_no, $year ?: null])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'An error occurred during registration.';
            }
        }
    }
}

require_once __DIR__ . '/../components/header.php';
?>

<div style="max-width: 700px; margin: 64px auto;">
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

                <div style="display: grid; grid-template-columns: 1fr 160px 160px; gap: 16px;">
                    <div class="mb-3">
                        <label class="label mb-1" style="display:block;">Matriculation No.</label>
                        <input type="text" name="mat_no" placeholder="e.g. M.23/ND/CSIT/11533" required>
                    </div>
                    <div class="mb-3">
                        <label class="label mb-1" style="display:block;">Level</label>
                        <select name="level" required>
                            <option value="">Select</option>
                            <option value="M22">M22</option>
                            <option value="M23">M23</option>
                            <option value="M24">M24</option>
                            <option value="M25">M25</option>
                            <option value="M26">M26</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="label mb-1" style="display:block;">Year</label>
                        <input type="number" name="year" placeholder="e.g. 2024" required>
                    </div>
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
