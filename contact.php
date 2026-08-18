<?php
// contact.php
require_once __DIR__ . '/config/init.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success = 'Thank you for reaching out! Your message has been sent successfully.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

require_once __DIR__ . '/components/header.php';
?>

<div class="mb-4">
    <h1 style="font-size: 48px; margin-bottom: 8px;">Contact Us</h1>
    <p class="subtext">Have a question or need support? Send us a message.</p>
</div>

<div class="panel" style="max-width: 800px;">
    <?php if ($error): ?>
        <div class="mb-3" style="color: var(--danger); font-size: 14px; border: 1px solid var(--danger); padding: 12px; border-radius: 8px;">
            <?= h($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-3" style="color: var(--success); font-size: 14px; border: 1px solid var(--success); padding: 12px; border-radius: 8px;">
            <?= h($success) ?>
        </div>
        <div>
            <a href="index.php" class="btn">Return Home</a>
        </div>
    <?php else: ?>
        <form method="POST" action="">
            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Full Name *</label>
                    <input type="text" name="name" required placeholder="John Doe">
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Email Address *</label>
                    <input type="email" name="email" required placeholder="john@student.test">
                </div>
            </div>

            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Subject *</label>
                <input type="text" name="subject" required placeholder="How can we help you?">
            </div>
            
            <div class="mb-4">
                <label class="label mb-1" style="display:block;">Message *</label>
                <textarea name="message" required placeholder="Write your message here..."></textarea>
            </div>

            <button type="submit" style="width: 100%;">Send Message</button>
        </form>
    <?php endif; ?>
</div>

<div style="margin-top: 48px; max-width: 800px; padding: 24px; border-radius: 8px; border: 1px dashed var(--line);">
    <h3 class="eyebrow mb-2">Other Ways to Reach Us</h3>
    <p class="subtext" style="font-size: 14px;">Email: support@dpls.test &nbsp;&bull;&nbsp; Phone: +447979725266</p>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
