<?php
// supervisor/projects.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('supervisor');

global $pdo;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($project_id > 0) {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND supervisor_id = ?");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $status = '';
            if ($action === 'approve') $status = 'approved';
            elseif ($action === 'reject') $status = 'rejected';
            
            if ($status) {
                $update = $pdo->prepare("UPDATE projects SET supervisor_status = ? WHERE id = ?");
                if ($update->execute([$status, $project_id])) {
                    $success = "Project has been marked as {$status}.";
                } else {
                    $error = "Failed to update project status.";
                }
            }
        }
    }
}

$student_id = (int)($_GET['student_id'] ?? 0);
$query = "
    SELECT p.id, p.title, p.supervisor_status, p.admin_status, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.supervisor_id = ?
";
$params = [$_SESSION['user_id']];

if ($student_id > 0) {
    $query .= " AND p.student_id = ?";
    $params[] = $student_id;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <h3 class="eyebrow mb-1" style="color: var(--accent);">SUPERVISOR</h3>
    <h1 style="font-size: 48px; margin-bottom: 24px;">ASSIGNED PROJECTS</h1>
</div>

<?php if ($success): ?>
    <div class="mb-3" style="color: var(--success); font-size: 14px; border: 1px solid var(--success); padding: 12px; border-radius: 8px;">
        <?= h($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-3" style="color: var(--danger); font-size: 14px; border: 1px solid var(--danger); padding: 12px; border-radius: 8px;">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<?php if (empty($projects)): ?>
    <?= render_empty_state("No Projects Assigned", "There are currently no projects assigned to you for review.") ?>
<?php else: ?>
    <div class="grid-auto-fit">
        <?php foreach ($projects as $project): ?>
            <div class="card flex-column">
                <div class="flex-between">
                    <span class="micro-label"><?= h($project['category_name']) ?></span>
                    <span class="badge <?= h($project['supervisor_status']) ?>"><?= h($project['supervisor_status']) ?></span>
                </div>
                <h3><?= h($project['title']) ?></h3>
                <p class="label mb-3">By <?= h($project['student_name']) ?></p>
                
                <div class="flex-between" style="margin-top: auto;">
                    <a href="review.php?id=<?= $project['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">View Details</a>
                    
                    <?php if ($project['supervisor_status'] === 'pending'): ?>
                        <div style="display: flex; gap: 8px;">
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px; border-color: var(--danger); color: var(--danger);">Reject</button>
                            </form>
                            <form method="POST" action="" style="margin: 0;">
                                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" style="padding: 6px 12px; font-size: 14px; background: var(--success); border-color: var(--success);">Approve</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
