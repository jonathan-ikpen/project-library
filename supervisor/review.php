<?php
// supervisor/review.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('supervisor');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/supervisor/projects.php');

global $pdo;

// Fetch project ensuring supervisor owns it
$stmt = $pdo->prepare("
    SELECT p.*, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.supervisor_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/supervisor/projects.php');
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($project['admin_status'] !== 'published') {
        $action = $_POST['action'] ?? '';
        $note = trim($_POST['supervisor_note'] ?? '');
        $status = $project['supervisor_status'];
        
        if ($action === 'approve') $status = 'approved';
        elseif ($action === 'reject') $status = 'rejected';
        elseif ($action === 'revoke') $status = 'pending';
        
        $update = $pdo->prepare("UPDATE projects SET supervisor_status = ?, supervisor_note = ? WHERE id = ?");
        if ($update->execute([$status, $note, $id])) {
            $project['supervisor_status'] = $status;
            $project['supervisor_note'] = $note;
            $success = "Project details and status updated.";
        } else {
            $error = "Failed to update project.";
        }
    } else {
        $error = "Action denied: Project is published by the Admin and cannot be modified.";
    }
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

<div style="margin-bottom: 32px;">
    <a href="projects.php" class="btn btn-outline" style="padding: 8px 16px;">&larr; Back to Assigned Projects</a>
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
            <div class="flex-between mb-2">
                <span class="micro-label"><?= h($project['category_name'] ?? 'Uncategorized') ?> &bull; <?= h($project['year']) ?></span>
                <span class="badge <?= $project['supervisor_status'] ?>"><?= h($project['supervisor_status']) ?></span>
            </div>
            
            <h1 style="font-size: 36px; margin-bottom: 8px;"><?= h($project['title']) ?></h1>
            <p class="label mb-4">By <strong><?= h($project['student_name'] ?? 'Unknown') ?></strong> &bull; Language: <?= h($project['language'] ?: 'N/A') ?></p>
            
            <h3 class="eyebrow mb-2">Abstract</h3>
            <p style="white-space: pre-wrap; line-height: 1.6; color: var(--text-secondary);"><?= h($project['abstract']) ?></p>
            
            <?php if (!empty($tags)): ?>
                <div class="mt-4">
                    <h3 class="eyebrow mb-2">Tags</h3>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php foreach ($tags as $tag): ?>
                            <span style="background: var(--surface); padding: 4px 12px; border-radius: 16px; font-size: 12px; color: var(--text-secondary); border: 1px solid var(--line);"><?= h($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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

    <!-- Column 2: Action Pane -->
    <div class="flex-column" style="gap: 24px;">
        <div class="panel" style="border: 2px solid var(--text-primary);">
            <h3 class="eyebrow mb-3" style="color: var(--text-primary);">Supervisor Action</h3>
            
            <?php if ($project['admin_status'] === 'published'): ?>
                <div class="mb-4" style="color: var(--danger); font-size: 14px; font-weight: 500; border: 1px solid var(--danger); padding: 12px; border-radius: 6px;">
                    This project has been published by an Admin and can no longer be modified.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php $disabled = ($project['admin_status'] === 'published') ? 'disabled' : ''; ?>
                <div class="mb-4">
                    <label class="label mb-1" style="display:block;">Review Note</label>
                    <textarea name="supervisor_note" rows="5" placeholder="Add a note for the student and admin..." <?= $disabled ?>><?= h($project['supervisor_note'] ?? '') ?></textarea>
                </div>
                
                <?php if ($project['admin_status'] !== 'published'): ?>
                    <div class="flex-column" style="gap: 12px;">
                        <?php if ($project['supervisor_status'] === 'pending'): ?>
                            <button type="submit" name="action" value="approve" style="width: 100%; font-size: 15px; background: var(--success); border-color: var(--success);">Approve Project</button>
                            <button type="submit" name="action" value="reject" class="btn btn-outline" style="width: 100%; font-size: 15px; border-color: var(--danger); color: var(--danger);">Reject Project</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="update_note" style="width: 100%; font-size: 15px; background: var(--surface); color: var(--text-primary); border: 1px solid var(--line);">Save Note</button>
                            
                            <?php if ($project['supervisor_status'] === 'approved'): ?>
                                <button type="submit" name="action" value="reject" class="btn btn-outline" style="width: 100%; font-size: 15px; border-color: var(--danger); color: var(--danger);">Change to Rejected</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="approve" style="width: 100%; font-size: 15px; background: var(--success); border-color: var(--success);">Change to Approved</button>
                            <?php endif; ?>
                            
                            <button type="submit" name="action" value="revoke" class="btn btn-outline" style="width: 100%; font-size: 15px; border-color: var(--text-secondary); color: var(--text-secondary);">Revoke Decision (Pending)</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <button type="button" disabled style="width: 100%; font-size: 15px; opacity: 0.5;">Controls Disabled</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
