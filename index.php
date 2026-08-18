<?php
// index.php
require_once __DIR__ . '/config/init.php';

$search = trim($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$year_filter = (int)($_GET['year'] ?? 0);
$language_filter = trim($_GET['language'] ?? '');

global $pdo;

$query = "
    SELECT p.id, p.title, p.abstract, p.year, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.admin_status = 'published'
";
$params = [];

if ($search !== '') {
    // Basic search on title and abstract, plus tag joining
    $query .= " AND (MATCH(p.title, p.abstract) AGAINST(? IN BOOLEAN MODE) OR p.title LIKE ? OR p.id IN (
        SELECT pt.project_id FROM project_tags pt 
        JOIN tags t ON pt.tag_id = t.id 
        WHERE t.name LIKE ?
    ))";
    $params[] = '*' . $search . '*';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($year_filter > 0) {
    $query .= " AND p.year = ?";
    $params[] = $year_filter;
}

if ($language_filter !== '') {
    $query .= " AND p.language LIKE ?";
    $params[] = '%' . $language_filter . '%';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$categories = get_categories($pdo);

require_once __DIR__ . '/components/header.php';
?>

<div class="hero-section">
    <h1 class="hero-title">Centralized Academic Repository</h1>
    <p class="subtext" style="max-width: 600px; margin: 0 auto;">Discover final academic projects, research papers, and source code implementations submitted by our student body.</p>
</div>

<div class="panel mb-4">
    <form method="GET" action="index.php" class="flex-between filter-form" style="flex-wrap: wrap; gap: 16px;">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search titles, abstracts, or tags..." style="flex: 1; margin-bottom: 0;">
        <div style="display: flex; gap: 16px; flex: 2; min-width: 300px;">
            <select name="category" style="margin-bottom: 0;">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_filter === $cat['id'] ? 'selected' : '' ?>>
                        <?= h($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="year" style="margin-bottom: 0;">
                <option value="0">All Years</option>
                <?php 
                $currentYear = (int)date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $year_filter === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <input type="text" name="language" value="<?= h($language_filter) ?>" placeholder="Language (e.g. PHP)" style="margin-bottom: 0;">
        </div>
        <button type="submit" style="margin-bottom: 0;">Search</button>
    </form>
</div>

<?php if (empty($projects)): ?>
    <?= render_empty_state("No projects found", "We couldn't find any published projects matching your criteria.") ?>
<?php else: ?>
    <div class="flex-between mb-3">
        <span class="label"><?= count($projects) ?> project(s) found</span>
        <div style="display: flex; gap: 8px;">
            <button type="button" id="view-grid" class="btn btn-outline" style="padding: 8px; line-height: 1;" title="Grid View">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </button>
            <button type="button" id="view-list" class="btn btn-outline" style="padding: 8px; line-height: 1; opacity: 0.5;" title="List View">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </button>
        </div>
    </div>
    
    <div class="grid-auto-fit list-view">
        <?php foreach ($projects as $project): ?>
            <div class="card flex-column">
                <span class="micro-label"><?= h($project['category_name'] ?? 'Uncategorized') ?> &bull; <?= h($project['year']) ?></span>
                <h3 class="mt-2 mb-0"><?= h($project['title']) ?></h3>
                <p class="subtext mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= h($project['abstract']) ?>
                </p>
                <div class="flex-between" style="margin-top: auto;">
                    <span class="label">By <?= h($project['student_name'] ?? 'Unknown Student') ?></span>
                    <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-outline" style="padding: 8px 16px;">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/components/footer.php'; ?>
