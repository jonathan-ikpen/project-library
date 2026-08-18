<?php
// student/project.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('student');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/student/dashboard.php');

global $pdo;

// Fetch project ensuring student owns it
$stmt = $pdo->prepare("
    SELECT p.*, s.name as supervisor_name, c.name as category_name
    FROM projects p
    LEFT JOIN users s ON p.supervisor_id = s.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.student_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/student/dashboard.php');
}

$is_locked = ($project['supervisor_status'] === 'approved' && $project['admin_status'] !== 'rejected');

$error = '';
$success = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_metadata') {
        $title = trim($_POST['title'] ?? '');
        $abstract = trim($_POST['abstract'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $supervisor_id = (int)($_POST['supervisor_id'] ?? 0);
        $year = (int)($_POST['year'] ?? date('Y'));
        $language = trim($_POST['language'] ?? '');
        
        if (empty($title) || empty($abstract) || !$category_id || !$supervisor_id) {
            $error = 'Title, abstract, category, and supervisor are required.';
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE projects SET title = ?, abstract = ?, category_id = ?, supervisor_id = ?, year = ?, language = ?, supervisor_status = 'pending', admin_status = 'pending' WHERE id = ?");
            if ($stmtUpdate->execute([$title, $abstract, $category_id, $supervisor_id, $year, $language, $id])) {
                $success = 'Project details updated. Status reset to pending.';
                $project['title'] = $title;
                $project['abstract'] = $abstract;
                $project['category_id'] = $category_id;
                $project['year'] = $year;
                $project['language'] = $language;
                $project['supervisor_status'] = 'pending';
                $project['admin_status'] = 'pending';
            }
        }
    } elseif ($action === 'delete_file') {
        $file_id = (int)($_POST['file_id'] ?? 0);
        if ($file_id) {
            // Ensure file belongs to this project
            $stmtFile = $pdo->prepare("SELECT file_name FROM files WHERE id = ? AND project_id = ?");
            $stmtFile->execute([$file_id, $id]);
            $file = $stmtFile->fetch();
            
            if ($file) {
                @unlink(__DIR__ . '/../uploads/' . $file['file_name']);
                $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$file_id]);
                $success = 'File deleted successfully.';
            }
        }
    } elseif ($action === 'upload_file') {
        if (!isset($_FILES['new_file']) || $_FILES['new_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Please select a file to upload.';
        } else {
            $file = $_FILES['new_file'];
            $file_type = $_POST['file_type'] ?? 'other';
            
            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 50 * 1024 * 1024) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $secure_name = bin2hex(random_bytes(16)) . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $secure_name)) {
                    $stmtFile = $pdo->prepare("INSERT INTO files (project_id, file_name, original_name, file_type, mime_type) VALUES (?, ?, ?, ?, ?)");
                    $stmtFile->execute([$id, $secure_name, $file['name'], $file_type, $mime]);
                    $success = 'File uploaded successfully.';
                    // Reset status to pending when files change
                    $pdo->prepare("UPDATE projects SET supervisor_status = 'pending', admin_status = 'pending' WHERE id = ?")->execute([$id]);
                    $project['supervisor_status'] = 'pending';
                    $project['admin_status'] = 'pending';
                } else {
                    $error = 'Failed to move uploaded file.';
                }
            } else {
                $error = 'Invalid file or file too large.';
            }
        }
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

// Fetch categories for edit form
$categories = get_categories($pdo);

// Fetch supervisors for edit form
$stmtSupervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name ASC");
$supervisors = $stmtSupervisors->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="dashboard.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
    <h1>Manage Project</h1>
    <p class="subtext">Review and update your submission.</p>
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

<?php if ($is_locked): ?>
    <div class="mb-4" style="color: var(--warning); font-size: 14px; border: 1px solid var(--warning); padding: 12px; border-radius: 8px; background: rgba(179,115,0,0.1);">
        <strong>Project Locked:</strong> This project has been approved by your supervisor and can no longer be edited.
    </div>
<?php endif; ?>

<?php if (!empty($project['supervisor_note'])): ?>
    <div class="panel mb-4" style="border-left: 4px solid var(--accent);">
        <h3 class="eyebrow mb-2">Supervisor Note</h3>
        <p style="white-space: pre-wrap;"><?= h($project['supervisor_note']) ?></p>
    </div>
<?php endif; ?>

<div class="grid-auto-fit">
    <!-- Metadata Panel -->
    <div class="panel">
        <h3 class="mb-3">Project Details</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_metadata">
            
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Project Title</label>
                <input type="text" name="title" value="<?= h($project['title']) ?>" required <?= $is_locked ? 'disabled' : '' ?>>
            </div>
            
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Abstract</label>
                <textarea name="abstract" required <?= $is_locked ? 'disabled' : '' ?>><?= h($project['abstract']) ?></textarea>
            </div>

            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Category</label>
                    <select name="category_id" required <?= $is_locked ? 'disabled' : '' ?>>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $project['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Supervisor</label>
                    <select name="supervisor_id" required <?= $is_locked ? 'disabled' : '' ?>>
                        <?php foreach ($supervisors as $sup): ?>
                            <option value="<?= $sup['id'] ?>" <?= $project['supervisor_id'] == $sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Academic Year</label>
                    <input type="number" name="year" value="<?= h($project['year']) ?>" required <?= $is_locked ? 'disabled' : '' ?>>
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Language</label>
                    <input type="text" name="language" value="<?= h($project['language']) ?>" <?= $is_locked ? 'disabled' : '' ?>>
                </div>
            </div>

            <?php if (!$is_locked): ?>
                <button type="submit" style="width: 100%;">Save Changes</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Files Panel -->
    <div class="panel">
        <h3 class="mb-3">Project Files</h3>
        
        <?php if (!empty($files)): ?>
            <div class="flex-column mb-4">
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
                        <div style="display: flex; gap: 8px;">
                            <a href="../download.php?id=<?= $file['id'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px;">Download</a>
                            <?php if (!$is_locked): ?>
                                <form method="POST" action="" onsubmit="return confirm('Delete this file?');">
                                    <input type="hidden" name="action" value="delete_file">
                                    <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 14px; border-color: var(--danger); color: var(--danger);">Remove</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="subtext mb-4">No files currently attached.</p>
        <?php endif; ?>

        <?php if (!$is_locked): ?>
            <div style="border-top: 1px solid var(--line); padding-top: 24px;">
                <h3 class="eyebrow mb-2">Upload Additional File</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_file">
                    <div class="mb-3">
                        <label class="label mb-1" style="display:block;">File Type</label>
                        <select name="file_type">
                            <option value="document">Document</option>
                            <option value="presentation">Presentation</option>
                            <option value="source_code">Source Code</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3" style="padding: 16px; border: 1px dashed var(--line); border-radius: 8px;">
                        <input type="file" name="new_file" required style="border: none; margin-bottom: 0;">
                    </div>
                    <button type="submit" style="width: 100%;">Upload File</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
