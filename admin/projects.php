<?php
// admin/projects.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;

// Filters
$q = trim($_GET['q'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$status = trim($_GET['status'] ?? '');
$level_filter = trim($_GET['level'] ?? '');
$year_filter = trim($_GET['year'] ?? '');
$supervisor_id = (int)($_GET['supervisor_id'] ?? 0);

$query = "
    SELECT p.id, p.title, p.abstract, p.admin_status, p.supervisor_status, p.supervisor_note, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.supervisor_status = 'approved'
";
$params = [];

if ($q) {
    $query .= " AND (p.title LIKE ? OR p.abstract LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($category_id) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if ($status) {
    $query .= " AND p.admin_status = ?";
    $params[] = $status;
}
if ($level_filter) {
    $query .= " AND u.level = ?";
    $params[] = $level_filter;
}
if ($year_filter) {
    $query .= " AND p.year = ?";
    $params[] = $year_filter;
}
if ($supervisor_id) {
    $query .= " AND p.supervisor_id = ?";
    $params[] = $supervisor_id;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$categories = get_categories($pdo);

// Fetch supervisors for filter
$stmtSupervisors = $pdo->query("SELECT id, name FROM users WHERE role = 'supervisor' ORDER BY name ASC");
$supervisors = $stmtSupervisors->fetchAll();

// Fetch available years from projects and merge with predefined
$predefined_years = range(date('Y'), date('Y') - 5);
$stmtYears = $pdo->query("SELECT DISTINCT year FROM projects ORDER BY year DESC");
$db_years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
$years = array_unique(array_merge($predefined_years, $db_years));
rsort($years);

// Fetch available levels and merge with predefined
$predefined_levels = ['M22', 'M23', 'M24', 'M25', 'M26'];
$stmtLevels = $pdo->query("SELECT DISTINCT level FROM users WHERE role = 'student' AND level IS NOT NULL AND level != '' ORDER BY level ASC");
$db_levels = $stmtLevels->fetchAll(PDO::FETCH_COLUMN);
$levels = array_unique(array_merge($predefined_levels, $db_levels));
sort($levels);

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4 flex-between header-actions" style="align-items: flex-start;">
    <div>
        <a href="dashboard.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
        <h1>Project Moderation</h1>
        <p class="subtext">Review projects approved by supervisors and publish them to the public gateway.</p>
    </div>
    <div>
        <a href="project_add.php" class="btn" style="padding: 8px 16px;">Add New Project</a>
    </div>
</div>

<form method="GET" action="" class="mb-4 filter-form" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: end;">
    <div style="flex: 1; min-width: 200px;">
        <label class="label mb-1" style="display:block;">Search Projects</label>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search titles or abstracts..." style="margin-bottom: 0; height: 54px; padding: 0 20px; box-sizing: border-box; width: 100%;">
    </div>
    <div style="min-width: 140px;">
        <label class="label mb-1" style="display:block;">Category</label>
        <select name="category" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width: 140px;">
        <label class="label mb-1" style="display:block;">Status</label>
        <select name="status" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Review</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
    </div>
    <div style="min-width: 120px;">
        <label class="label mb-1" style="display:block;">Level</label>
        <select name="level" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <?php foreach ($levels as $l): ?>
                <option value="<?= h($l) ?>" <?= $level_filter === $l ? 'selected' : '' ?>><?= h($l) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width: 120px;">
        <label class="label mb-1" style="display:block;">Year</label>
        <select name="year" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Years</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= h($y) ?>" <?= (string)$year_filter === (string)$y ? 'selected' : '' ?>><?= h($y) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="min-width: 150px;">
        <label class="label mb-1" style="display:block;">Supervisor</label>
        <select name="supervisor_id" style="margin-bottom: 0; width: 100%;" onchange="this.form.submit()">
            <option value="">All Supervisors</option>
            <?php foreach ($supervisors as $sup): ?>
                <option value="<?= $sup['id'] ?>" <?= $supervisor_id == $sup['id'] ? 'selected' : '' ?>><?= h($sup['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn" style="height: 54px; padding: 0 32px; margin-bottom: 0; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;">Filter</button>
</form>

<?php if (empty($projects)): ?>
    <?= render_empty_state("No Projects Found", "There are no projects matching your current filters.") ?>
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
    <div class="grid-auto-fit">
        <?php foreach ($projects as $project): ?>
            <div class="card flex-column">
                <div class="flex-between">
                    <span class="micro-label"><?= h($project['category_name']) ?></span>
                    <span class="badge <?= $project['admin_status'] ?>"><?= h($project['admin_status']) ?></span>
                </div>
                <h3 class="mt-2 mb-1"><?= h($project['title']) ?></h3>
                <div class="label mb-3" style="font-size: 13px;">By <?= h($project['student_name']) ?></div>
                
                <p class="subtext mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                    <?= h($project['abstract']) ?>
                </p>
                
                <?php if (!empty($project['supervisor_note'])): ?>
                    <div style="background: var(--bg); padding: 12px; border: 1px solid var(--line); border-radius: 8px; margin-bottom: 16px;">
                        <span class="micro-label" style="display:block; margin-bottom: 4px;">Supervisor Note:</span>
                        <span style="font-size: 14px; color: var(--text-secondary);"><?= h(substr($project['supervisor_note'], 0, 100)) ?><?= strlen($project['supervisor_note']) > 100 ? '...' : '' ?></span>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: auto; display: flex; justify-content: flex-end;">
                    <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-outline" style="padding: 6px 16px; font-size: 14px;">Review Project &rarr;</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
