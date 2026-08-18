<?php
// project.php
require_once __DIR__ . '/config/init.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/');

global $pdo;

$stmt = $pdo->prepare("
    SELECT p.*, u.name as student_name, s.name as supervisor_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN users s ON p.supervisor_id = s.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.admin_status = 'published'
");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    redirect('/');
}

// Fetch files
$stmtFiles = $pdo->prepare("SELECT id, file_name, original_name, file_type FROM files WHERE project_id = ?");
$stmtFiles->execute([$id]);
$files = $stmtFiles->fetchAll();

// Fetch tags
$stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN project_tags pt ON t.id = pt.tag_id WHERE pt.project_id = ?");
$stmtTags->execute([$id]);
$tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/components/header.php';
?>

<div style="margin-bottom: 32px;">
    <a href="index.php" class="btn btn-outline" style="padding: 8px 16px;">&larr; Back to Search</a>
</div>

<div class="panel">
    <div class="flex-between mb-2">
        <span class="micro-label"><?= h($project['category_name'] ?? 'Uncategorized') ?> &bull; <?= h($project['year']) ?></span>
        <div>
            <?php foreach ($tags as $tag): ?>
                <span class="badge"><?= h($tag) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <h1 style="font-size: 36px; margin-bottom: 8px;"><?= h($project['title']) ?></h1>
    <div class="label mb-4" style="font-size: 15px;">By <strong><?= h($project['student_name'] ?? 'Unknown') ?></strong> &bull; Supervised by <?= h($project['supervisor_name'] ?? 'Unknown') ?></div>
    
    <div style="margin-bottom: 32px;">
        <h3 class="eyebrow mb-2">Abstract</h3>
        <p style="white-space: pre-wrap; color: var(--text-secondary); line-height: 1.6;"><?= h($project['abstract']) ?></p>
    </div>

    <div class="grid-auto-fit mt-4" style="gap: 16px;">
        <div>
            <h3 class="eyebrow mb-1">Academic Year</h3>
            <div style="font-size: 14px; font-weight: 500;"><?= h($project['year']) ?></div>
        </div>
        <div>
            <h3 class="eyebrow mb-1">Language/Tech</h3>
            <div style="font-size: 14px; font-weight: 500;"><?= h($project['language']) ?: 'N/A' ?></div>
        </div>
    </div>
</div>

<?php if (!empty($files)): ?>
    <div class="panel mt-4">
        <h3 class="eyebrow mb-3">Attached Files</h3>
        <div class="flex-column" style="max-width: 600px;">
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
                    <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-outline" style="padding: 6px 16px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Download
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/components/footer.php'; ?>
