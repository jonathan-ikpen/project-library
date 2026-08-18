<?php
// admin/project_add.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;
$error = '';
$success = '';

// Fetch students
$stmtStudents = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC");
$students = $stmtStudents->fetchAll();

// Fetch supervisors
$stmtSupervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name ASC");
$supervisors = $stmtSupervisors->fetchAll();

// Fetch categories
$categories = get_categories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $student_id = (int)($_POST['student_id'] ?? 0);
    $supervisor_id = (int)($_POST['supervisor_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $year = (int)($_POST['year'] ?? date('Y'));
    $language = trim($_POST['language'] ?? '');
    $tags_raw = trim($_POST['tags'] ?? '');
    
    // Validate inputs
    if (empty($title) || empty($abstract) || !$student_id || !$supervisor_id || !$category_id) {
        $error = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['file_document']) || $_FILES['file_document']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload the main Project Document.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert Project (Admin bypasses approval)
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, abstract, student_id, supervisor_id, category_id, year, language, supervisor_status, admin_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 'published')
            ");
            $stmt->execute([$title, $abstract, $student_id, $supervisor_id, $category_id, $year, $language]);
            $project_id = $pdo->lastInsertId();
            
            // 2. Handle Tags
            if (!empty($tags_raw)) {
                $tags = array_unique(array_map('trim', explode(',', $tags_raw)));
                foreach ($tags as $tag_name) {
                    if (empty($tag_name)) continue;
                    
                    $stmtTag = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                    $stmtTag->execute([$tag_name]);
                    
                    $stmtTagId = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmtTagId->execute([$tag_name]);
                    $tag_id = $stmtTagId->fetchColumn();
                    
                    if ($tag_id) {
                        $stmtLink = $pdo->prepare("INSERT IGNORE INTO project_tags (project_id, tag_id) VALUES (?, ?)");
                        $stmtLink->execute([$project_id, $tag_id]);
                    }
                }
            }
            
            // 3. Process Files Function
            function process_file($file, $project_id, $file_type, $pdo) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) return true;
                if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Error uploading file: " . $file['name']);
                if ($file['size'] > 50 * 1024 * 1024) throw new Exception("File exceeds 50MB: " . $file['name']);
                
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $secure_name = bin2hex(random_bytes(16)) . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $secure_name)) {
                    $stmtFile = $pdo->prepare("
                        INSERT INTO files (project_id, file_name, original_name, file_type, mime_type) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtFile->execute([$project_id, $secure_name, $file['name'], $file_type, $mime]);
                    return true;
                }
                throw new Exception("Failed to move uploaded file: " . $file['name']);
            }
            
            // Handle Single Files
            process_file($_FILES['file_document'], $project_id, 'document', $pdo);
            
            if (isset($_FILES['file_presentation']) && $_FILES['file_presentation']['error'] !== UPLOAD_ERR_NO_FILE) {
                process_file($_FILES['file_presentation'], $project_id, 'presentation', $pdo);
            }
            if (isset($_FILES['file_code']) && $_FILES['file_code']['error'] !== UPLOAD_ERR_NO_FILE) {
                process_file($_FILES['file_code'], $project_id, 'source_code', $pdo);
            }
            
            // Handle Multiple Files (Others)
            if (isset($_FILES['file_others'])) {
                $total_others = count($_FILES['file_others']['name']);
                for ($i = 0; $i < $total_others; $i++) {
                    if ($_FILES['file_others']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                    
                    $single_file = [
                        'name' => $_FILES['file_others']['name'][$i],
                        'type' => $_FILES['file_others']['type'][$i],
                        'tmp_name' => $_FILES['file_others']['tmp_name'][$i],
                        'error' => $_FILES['file_others']['error'][$i],
                        'size' => $_FILES['file_others']['size'][$i]
                    ];
                    process_file($single_file, $project_id, 'other', $pdo);
                }
            }
            
            $pdo->commit();
            $success = 'Project uploaded and published successfully!';
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="projects.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Projects</a>
    <h1>Add New Project</h1>
    <p class="subtext">Manually upload a project to the platform on behalf of a student.</p>
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
    <a href="projects.php" class="btn">Return to Projects</a>
<?php else: ?>
    <div class="panel" style="max-width: 800px;">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Project Title *</label>
                <input type="text" name="title" required>
            </div>
            
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Abstract *</label>
                <textarea name="abstract" required rows="5"></textarea>
            </div>

            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Student *</label>
                    <select name="student_id" required>
                        <option value="">Select a student</option>
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= $stu['id'] ?>"><?= h($stu['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Supervisor *</label>
                    <select name="supervisor_id" required>
                        <option value="">Select a supervisor</option>
                        <?php foreach ($supervisors as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= h($sup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Category *</label>
                    <select name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Academic Year *</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" required>
                </div>
            </div>
            
            <div class="grid-auto-fit mb-3" style="gap: 16px;">
                <div>
                    <label class="label mb-1" style="display:block;">Programming Language</label>
                    <input type="text" name="language" placeholder="e.g. PHP, Python">
                </div>
                <div>
                    <label class="label mb-1" style="display:block;">Tags (Comma Separated)</label>
                    <input type="text" name="tags" placeholder="e.g. AI, Database, Security">
                </div>
            </div>

            <h3 class="mb-3 mt-4">File Uploads</h3>
            
            <div class="mb-3" style="padding: 16px; border: 1px dashed var(--line); border-radius: 8px;">
                <label class="label mb-1" style="display:block; color: var(--text-primary); font-weight: 700;">Project Document (PDF/DOCX) *</label>
                <input type="file" name="file_document" required style="border: none; margin-bottom: 0;">
            </div>

            <div class="mb-3" style="padding: 16px; border: 1px dashed var(--line); border-radius: 8px;">
                <label class="label mb-1" style="display:block; color: var(--text-primary); font-weight: 700;">Presentation (PPT/PPTX) [Optional]</label>
                <input type="file" name="file_presentation" style="border: none; margin-bottom: 0;">
            </div>

            <div class="mb-3" style="padding: 16px; border: 1px dashed var(--line); border-radius: 8px;">
                <label class="label mb-1" style="display:block; color: var(--text-primary); font-weight: 700;">Source Code (ZIP) [Optional]</label>
                <input type="file" name="file_code" style="border: none; margin-bottom: 0;">
            </div>

            <div class="mb-4" style="padding: 16px; border: 1px dashed var(--line); border-radius: 8px;">
                <label class="label mb-1" style="display:block; color: var(--text-primary); font-weight: 700;">Other Files (Multiselect) [Optional]</label>
                <input type="file" name="file_others[]" multiple style="border: none; margin-bottom: 0;">
            </div>

            <button type="submit" style="width: 100%;">Add & Publish Project</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
