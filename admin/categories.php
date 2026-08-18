<?php
// admin/categories.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

global $pdo;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $success = 'Category added successfully.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Integrity constraint violation
                    $error = 'Category already exists.';
                } else {
                    $error = 'An error occurred while adding the category.';
                }
            }
        }
    } else if ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Category deleted successfully.';
        }
    }
}

$categories = get_categories($pdo);

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4">
    <a href="dashboard.php" class="btn btn-outline mb-3" style="padding: 8px 16px;">&larr; Back to Dashboard</a>
    <h1>Manage Categories</h1>
    <p class="subtext">Create or remove categories used by students to classify their projects.</p>
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

<div class="grid-auto-fit">
    <!-- Add Category Panel -->
    <div class="panel">
        <h3 class="mb-3">Add New Category</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label class="label mb-1" style="display:block;">Category Name</label>
                <input type="text" name="name" required placeholder="e.g. Graphic Design">
            </div>
            <button type="submit" style="width: 100%;">Save Category</button>
        </form>
    </div>

    <!-- Category List Panel -->
    <div class="panel">
        <h3 class="mb-3">Existing Categories</h3>
        <?php if (empty($categories)): ?>
            <?= render_empty_state("No Categories", "No categories have been defined yet.") ?>
        <?php else: ?>
            <div class="flex-column">
                <?php foreach ($categories as $cat): ?>
                    <div class="card flex-between" style="padding: 16px;">
                        <span><?= h($cat['name']) ?></span>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure? This will remove the category from all associated projects.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="padding: 6px 12px; border-color: var(--danger); color: var(--danger);">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
