<?php
// admin/messages.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id && $action === 'read') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Message marked as read.';
    } elseif ($id && $action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Message deleted successfully.';
    }
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <h3 class="eyebrow mb-1" style="color: var(--accent);">ADMIN</h3>
    <h1 style="font-size: 48px; margin-bottom: 24px;">CONTACT MESSAGES</h1>
</div>

<?php if ($success): ?>
    <div class="mb-4" style="color: var(--success); font-size: 14px; border: 1px solid var(--success); padding: 12px; border-radius: 8px;">
        <?= h($success) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <?php if (empty($messages)): ?>
        <p class="subtext">No messages found.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($messages as $msg): ?>
                <div class="card" style="padding: 24px; border: 1px solid var(--line); border-left: 4px solid <?= $msg['status'] === 'unread' ? 'var(--accent)' : 'transparent' ?>;">
                    <div class="flex-between mb-2">
                        <div>
                            <strong><?= h($msg['name']) ?></strong> 
                            <span class="subtext">&lt;<?= h($msg['email']) ?>&gt;</span>
                        </div>
                        <span class="micro-label"><?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?></span>
                    </div>
                    
                    <h4 style="margin-bottom: 12px;"><?= h($msg['subject']) ?></h4>
                    <p style="white-space: pre-wrap; font-size: 14px; margin-bottom: 24px; color: var(--text-secondary);"><?= h($msg['message']) ?></p>
                    
                    <div style="display: flex; gap: 8px;">
                        <?php if ($msg['status'] === 'unread'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="read">
                                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px;">Mark as Read</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; color: var(--danger); border-color: var(--danger);">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
