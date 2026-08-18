<?php
// archive.php
require_once __DIR__ . '/config/init.php';

global $pdo;

// Fetch all published projects ordered by title ASC
$stmt = $pdo->query("
    SELECT p.id, p.title, p.year, u.name as student_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    WHERE p.admin_status = 'published'
    ORDER BY p.title ASC
");
$projects = $stmt->fetchAll();

// Group by alphabet (first letter)
$grouped = [];
foreach ($projects as $project) {
    $first_letter = strtoupper(substr($project['title'], 0, 1));
    if (!preg_match('/[A-Z]/', $first_letter)) {
        $first_letter = '#'; // Group numbers and symbols
    }
    if (!isset($grouped[$first_letter])) {
        $grouped[$first_letter] = [];
    }
    $grouped[$first_letter][] = $project;
}

require_once __DIR__ . '/components/header.php';
?>

<div class="hero-section">
    <h1 class="hero-title">Directory Archive</h1>
    <p class="subtext" style="max-width: 800px; margin: 0 auto; font-size: 18px;">
        Welcome to the official academic archive. This directory provides a complete, alphabetical index of all published projects, research papers, and software implementations submitted by our student body. Use the quick jumper below to navigate directly to a specific section.
    </p>
</div>

<div style="text-align: center; margin-bottom: 48px; display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <?php foreach (range('A', 'Z') as $char): ?>
        <?php $isActive = isset($grouped[$char]); ?>
        <?php if ($isActive): ?>
            <a href="#letter-<?= $char ?>" class="badge jumper-link" style="padding: 8px 12px; font-size: 14px; text-decoration: none; border-color: var(--accent); color: var(--text-primary); background: transparent;"><?= $char ?></a>
        <?php else: ?>
            <span class="badge" style="padding: 8px 12px; font-size: 14px; opacity: 0.3; border-color: var(--line); color: var(--text-secondary);"><?= $char ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (isset($grouped['#'])): ?>
        <a href="#letter-hash" class="badge jumper-link" style="padding: 8px 12px; font-size: 14px; text-decoration: none; border-color: var(--accent); color: var(--text-primary); background: transparent;">#</a>
    <?php endif; ?>
</div>

<div class="panel archive-panel" style="margin: 0 auto;">
    <?php if (empty($grouped)): ?>
        <p class="subtext text-center">No projects have been published yet.</p>
    <?php else: ?>
        <?php foreach ($grouped as $letter => $letter_projects): ?>
            <details id="letter-<?= $letter === '#' ? 'hash' : $letter ?>" style="margin-bottom: 24px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg);">
                <summary style="padding: 16px 24px; font-family: 'Anton', sans-serif; font-size: 24px; cursor: pointer; user-select: none; border-bottom: 1px solid var(--line); outline: none;">
                    <?= h($letter) ?> <span style="font-family: 'Inter', sans-serif; font-size: 14px; color: var(--text-secondary); margin-left: 8px;">(<?= count($letter_projects) ?>)</span>
                </summary>
                <div style="padding: 24px;">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($letter_projects as $project): ?>
                            <li style="margin-bottom: 16px; display: flex; gap: 8px 16px; align-items: baseline; flex-wrap: wrap;">
                                <span style="color: var(--text-secondary); font-variant-numeric: tabular-nums; flex-shrink: 0;">[<?= h(sprintf("%04d", $project['id'])) ?>]</span>
                                <a href="project.php?id=<?= $project['id'] ?>" style="font-weight: 500; flex: 1 1 200px;">
                                    <?= h($project['title']) ?>
                                </a>
                                <span class="subtext" style="font-size: 14px; flex-shrink: 0;"><?= h($project['year']) ?> &bull; <?= h($project['student_name']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.archive-panel {
    padding: 48px;
}
@media (max-width: 768px) {
    .archive-panel {
        padding: 16px;
    }
}
.jumper-link {
    transition: all 0.2s ease;
}
.jumper-link:hover {
    background: var(--accent) !important;
    color: var(--bg) !important;
}
details > summary::marker {
    display: none;
}
details > summary::-webkit-details-marker {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Open details tag when hash is navigated to
    const openHashDetails = () => {
        if (window.location.hash) {
            const target = document.querySelector(window.location.hash);
            if (target && target.tagName === 'DETAILS') {
                target.open = true;
                // Optional: add a brief highlight effect
                target.style.borderColor = 'var(--accent)';
                setTimeout(() => { target.style.borderColor = 'var(--line)'; }, 1000);
            }
        }
    };
    
    // Check on load
    openHashDetails();
    
    // Check on hash change
    window.addEventListener('hashchange', openHashDetails);
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>
