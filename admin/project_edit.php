<?php
// admin/project_edit.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/admin/projects.php');

global $pdo;
$error = '';
$success = '';

// Fetch the existing project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/admin/projects.php');
}

// Fetch relationships (students, supervisors, categories)
$stmtStudents = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC");
$students = $stmtStudents->fetchAll();

$stmtSupervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name ASC");
$supervisors = $stmtSupervisors->fetchAll();

$categories = get_categories($pdo);

// Fetch existing tags
$stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN project_tags pt ON t.id = pt.tag_id WHERE pt.project_id = ?");
$stmtTags->execute([$id]);
$existing_tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
$existing_tags_string = implode(', ', $existing_tags);

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
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Update Project
            $stmtUpdate = $pdo->prepare("
                UPDATE projects 
                SET title = ?, abstract = ?, student_id = ?, supervisor_id = ?, category_id = ?, year = ?, language = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$title, $abstract, $student_id, $supervisor_id, $category_id, $year, $language, $id]);
            
            // 2. Handle Tags (Clear and recreate)
            $stmtClearTags = $pdo->prepare("DELETE FROM project_tags WHERE project_id = ?");
            $stmtClearTags->execute([$id]);
            
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
                        $stmtLink->execute([$id, $tag_id]);
                    }
                }
            }
            
            $pdo->commit();
            $success = 'Project metadata updated successfully.';
            
            // Update local variables for immediate display
            $project['title'] = $title;
            $project['abstract'] = $abstract;
            $project['student_id'] = $student_id;
            $project['supervisor_id'] = $supervisor_id;
            $project['category_id'] = $category_id;
            $project['year'] = $year;
            $project['language'] = $language;
            $existing_tags_string = $tags_raw;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update project: " . $e->getMessage();
        }
    }
}

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="project.php?id=<?= $id ?>" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Project Review</a>
    <h1>Edit Project Details</h1>
    <p class="subtext">Modify the metadata, tags, and ownership of this project.</p>
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

<div class="panel" style="max-width: 800px; margin-bottom: 32px;">
    <form method="POST" action="">
        <div class="mb-3">
            <label class="label mb-1" style="display:block;">Project Title *</label>
            <input type="text" name="title" value="<?= h($project['title']) ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="label mb-1" style="display:block;">Abstract *</label>
            <textarea name="abstract" required rows="6"><?= h($project['abstract']) ?></textarea>
        </div>

        <div class="grid-auto-fit mb-3" style="gap: 16px;">
            <div>
                <label class="label mb-1" style="display:block;">Student *</label>
                <select name="student_id" required>
                    <?php foreach ($students as $stu): ?>
                        <option value="<?= $stu['id'] ?>" <?= $project['student_id'] == $stu['id'] ? 'selected' : '' ?>><?= h($stu['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label mb-1" style="display:block;">Supervisor *</label>
                <select name="supervisor_id" required>
                    <?php foreach ($supervisors as $sup): ?>
                        <option value="<?= $sup['id'] ?>" <?= $project['supervisor_id'] == $sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-auto-fit mb-3" style="gap: 16px;">
            <div>
                <label class="label mb-1" style="display:block;">Category *</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $project['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label mb-1" style="display:block;">Academic Year *</label>
                <input type="number" name="year" value="<?= h($project['year']) ?>" required>
            </div>
        </div>
        
        <div class="grid-auto-fit mb-4" style="gap: 16px;">
            <div>
                <label class="label mb-1" style="display:block;">Programming Language</label>
                <input type="text" name="language" value="<?= h($project['language']) ?>" placeholder="e.g. PHP, Python">
            </div>
            <div>
                <label class="label mb-1" style="display:block;">Tags (Comma Separated)</label>
                <input type="text" name="tags" value="<?= h($existing_tags_string) ?>" placeholder="e.g. AI, Database, Security">
            </div>
        </div>

        <button type="submit" style="width: 100%;">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
