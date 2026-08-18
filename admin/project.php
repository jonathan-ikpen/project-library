<?php
// admin/project.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/admin/projects.php');

global $pdo;

$error = '';
$success = '';

// Handle Admin Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $status = '';
    if ($action === 'publish') $status = 'published';
    elseif ($action === 'reject') $status = 'rejected';
    
    if ($status) {
        $stmt = $pdo->prepare("UPDATE projects SET admin_status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $success = "Project has been successfully marked as {$status}.";
        } else {
            $error = "Failed to update project status.";
        }
    }
}

// Fetch project details
$stmt = $pdo->prepare("
    SELECT p.*, s.name as supervisor_name, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users s ON p.supervisor_id = s.id
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/admin/projects.php');
}

// Fetch files
$stmtFiles = $pdo->prepare("SELECT id, file_name, original_name, file_type FROM files WHERE project_id = ?");
$stmtFiles->execute([$id]);
$files = $stmtFiles->fetchAll();

// Fetch tags
$stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN project_tags pt ON t.id = pt.tag_id WHERE pt.project_id = ?");
$stmtTags->execute([$id]);
$tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4 flex-between header-actions" style="align-items: flex-start;">
    <div>
        <a href="projects.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Projects</a>
        <h1>Review Project</h1>
        <p class="subtext">Examine project details and make publication decisions.</p>
    </div>
    <div>
        <a href="project_edit.php?id=<?= $id ?>" class="btn" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Edit Details
        </a>
    </div>
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

<div class="grid-auto-fit" style="grid-template-columns: 2fr 1fr; align-items: start;">
    
    <!-- Column 1: Project Details -->
    <div class="flex-column" style="gap: 24px;">
        <div class="panel">
            <div class="flex-between mb-3">
                <span class="micro-label"><?= h($project['category_name']) ?></span>
                <span class="badge <?= $project['admin_status'] ?>"><?= h($project['admin_status']) ?></span>
            </div>
            
            <h2 class="mb-2"><?= h($project['title']) ?></h2>
            <div class="label mb-4">By <?= h($project['student_name']) ?></div>
            
            <h3 class="eyebrow mb-2">Abstract</h3>
            <p style="white-space: pre-wrap; line-height: 1.6; color: var(--text-secondary);"><?= h($project['abstract']) ?></p>
            
            <div class="grid-auto-fit mt-4" style="gap: 16px;">
                <div>
                    <h3 class="eyebrow mb-1">Academic Year</h3>
                    <div style="font-size: 14px; font-weight: 500;"><?= h($project['year']) ?></div>
                </div>
                <div>
                    <h3 class="eyebrow mb-1">Language/Tech</h3>
                    <div style="font-size: 14px; font-weight: 500;"><?= h($project['language']) ?: 'N/A' ?></div>
                </div>
                <?php if (!empty($tags)): ?>
                    <div style="grid-column: 1 / -1;">
                        <h3 class="eyebrow mb-2">Tags</h3>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($tags as $tag): ?>
                                <span style="background: var(--surface); padding: 4px 12px; border-radius: 16px; font-size: 12px; color: var(--text-secondary); border: 1px solid var(--line);"><?= h($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel">
            <h3 class="eyebrow mb-3">Attached Files</h3>
            <?php if (!empty($files)): ?>
                <div class="flex-column">
                    <?php foreach ($files as $file): ?>
                        <div class="card flex-between" style="padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div class="icon-box">
                                    <?php if ($file['file_type'] === 'document'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <?php elseif ($file['file_type'] === 'presentation'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                    <?php elseif ($file['file_type'] === 'source_code'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php
                                        $type_labels = [
                                            'document' => 'Main Project Document',
                                            'presentation' => 'Presentation Slides',
                                            'source_code' => 'Source Code Archive',
                                            'other' => 'Additional File'
                                        ];
                                        $display_label = $type_labels[$file['file_type']] ?? 'Attached File';
                                    ?>
                                    <strong><?= h($display_label) ?></strong>
                                    <div class="subtext" style="font-size: 13px;"><?= h($file['original_name']) ?></div>
                                </div>
                            </div>
                            <a href="../download.php?id=<?= $file['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">Download</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="subtext">No files currently attached.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Column 2: Admin Actions & Notes -->
    <div class="flex-column" style="gap: 24px;">
        <div class="panel" style="border: 2px solid var(--text-primary);">
            <h3 class="eyebrow mb-3" style="color: var(--text-primary);">Admin Decision</h3>
            
            <?php if ($project['admin_status'] === 'published'): ?>
                <p class="subtext mb-4">This project is currently LIVE on the public gateway.</p>
                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to unpublish and reject this project?');">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-outline" style="width: 100%; border-color: var(--danger); color: var(--danger);">Revoke Publication (Reject)</button>
                </form>
            <?php else: ?>
                <p class="subtext mb-4">Approve this project to publish it to the public gateway, or reject it.</p>
                <div class="flex-column" style="gap: 12px;">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="publish">
                        <button type="submit" style="width: 100%; font-size: 15px;">Publish Project</button>
                    </form>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reject this project?');">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-outline" style="width: 100%; font-size: 15px; border-color: var(--danger); color: var(--danger);">Reject Project</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($project['supervisor_note'])): ?>
            <div class="panel" style="border-left: 4px solid var(--accent); background: var(--surface);">
                <h3 class="eyebrow mb-2">Supervisor's Note</h3>
                <div class="label mb-3" style="font-size: 13px;">By <?= h($project['supervisor_name']) ?></div>
                <p style="white-space: pre-wrap; font-size: 14px; line-height: 1.5;"><?= h($project['supervisor_note']) ?></p>
            </div>
        <?php else: ?>
            <div class="panel">
                <h3 class="eyebrow mb-2">Supervisor's Note</h3>
                <p class="subtext">No notes provided by supervisor.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
