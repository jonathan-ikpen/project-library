<?php
// student/projects.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('student');

global $pdo;

// Fetch student's projects
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.supervisor_status, p.admin_status, c.name as category_name
    FROM projects p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <h3 class="eyebrow mb-1" style="color: var(--accent);">STUDENT</h3>
    <h1 style="font-size: 48px; margin-bottom: 24px;">SUBMITTED PROJECTS</h1>
</div>

<?php if (empty($projects)): ?>
    <?= render_empty_state("No Projects Yet", "You haven't uploaded any projects. Click 'Upload Project' to get started.") ?>
<?php else: ?>
    <div class="grid-auto-fit">
        <?php foreach ($projects as $project): ?>
            <div class="card flex-column">
                <div class="flex-between">
                    <span class="micro-label"><?= h($project['category_name']) ?></span>
                    <div>
                        <?php if ($project['admin_status'] === 'published'): ?>
                            <span class="badge published">PUBLISHED</span>
                        <?php elseif ($project['admin_status'] === 'rejected' || $project['supervisor_status'] === 'rejected'): ?>
                            <span class="badge rejected">REJECTED</span>
                        <?php elseif ($project['supervisor_status'] === 'approved'): ?>
                            <span class="badge approved">APPROVED</span>
                        <?php else: ?>
                            <span class="badge pending">PENDING</span>
                        <?php endif; ?>
                    </div>
                </div>
                <h3 class="mb-3"><?= h($project['title']) ?></h3>
                
                <div style="margin-top: auto;">
                    <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px; width: 100%; display: block; text-align: center;">View / Edit Submission</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
