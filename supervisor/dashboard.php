<?php
// supervisor/dashboard.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('supervisor');

global $pdo;
$success = '';


$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.supervisor_status, p.admin_status, p.created_at, u.name as student_name, c.name as category_name
    FROM projects p
    LEFT JOIN users u ON p.student_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.supervisor_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';

// Fetch overview stats
$assigned_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE supervisor_id = {$_SESSION['user_id']}")->fetchColumn();
$pending_reviews = $pdo->query("SELECT COUNT(*) FROM projects WHERE supervisor_id = {$_SESSION['user_id']} AND supervisor_status = 'pending'")->fetchColumn();
$approved_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE supervisor_id = {$_SESSION['user_id']} AND supervisor_status = 'approved'")->fetchColumn();
$rejected_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE supervisor_id = {$_SESSION['user_id']} AND supervisor_status = 'rejected'")->fetchColumn();
?>

<div class="mb-4">
    <h3 class="eyebrow mb-1" style="color: var(--accent);">SUPERVISOR</h3>
    <h1 style="font-size: 48px; margin-bottom: 24px;">DEPARTMENT OVERVIEW</h1>
</div>

<div class="grid-stats mb-4">
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Assigned Projects</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px;"><?= h($assigned_projects) ?></div>
    </div>
    
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Pending Review</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--accent);"><?= h($pending_reviews) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Approved</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--success);"><?= h($approved_projects) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Rejected</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--danger);"><?= h($rejected_projects) ?></div>
    </div>
</div>

<div class="grid-auto-fit" style="align-items: stretch;">
    
    <!-- Pending Reviews -->
    <div class="panel">
        <h3 class="eyebrow mb-3">Pending Reviews</h3>
        <?php
        $pending_list = array_filter($projects, fn($p) => $p['supervisor_status'] === 'pending');
        $pending_list = array_slice($pending_list, 0, 5);
        
        if (empty($pending_list)): ?>
            <p class="subtext">No projects pending review.</p>
        <?php else: ?>
            <div class="flex-column" style="gap: 16px;">
                <?php foreach ($pending_list as $p): ?>
                    <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; border: 1px solid var(--line); border-radius: 8px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;"><?= h($p['title']) ?></div>
                            <div class="subtext mb-2" style="font-size: 13px;">By <?= h($p['student_name']) ?> &bull; <?= h($p['category_name']) ?></div>
                            <a href="review.php?id=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 4px 12px; font-size: 12px;">Review Project</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count(array_filter($projects, fn($p) => $p['supervisor_status'] === 'pending')) > 5): ?>
                <div class="mt-3 text-center">
                    <a href="projects.php" class="subtext" style="font-size: 13px; text-decoration: underline;">View all pending</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Recent Activities -->
    <div class="panel">
        <h3 class="eyebrow mb-3">Recent Activities</h3>
        <?php
        $activities = [];
        foreach ($projects as $rp) {
            $activities[] = [
                'type' => 'project',
                'description' => "Project assigned to you: " . h($rp['title']) . " by " . h($rp['student_name']),
                'date' => $rp['created_at']
            ];
            if ($rp['supervisor_status'] === 'approved') {
                $activities[] = [
                    'type' => 'status',
                    'description' => "You approved project: " . h($rp['title']),
                    'date' => date('Y-m-d H:i:s', strtotime($rp['created_at']) + 86400) // Dummy time for demo
                ];
            } elseif ($rp['supervisor_status'] === 'rejected') {
                $activities[] = [
                    'type' => 'status',
                    'description' => "You rejected project: " . h($rp['title']),
                    'date' => date('Y-m-d H:i:s', strtotime($rp['created_at']) + 86400) // Dummy time for demo
                ];
            }
        }
        
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        $activities = array_slice($activities, 0, 7);
        
        if (empty($activities)): ?>
            <p class="subtext">No recent activity.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($activities as $act): ?>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="margin-top: 2px; color: var(--text-secondary);">
                            <?php if ($act['type'] === 'project'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <?php elseif ($act['type'] === 'status'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; color: var(--text-primary); line-height: 1.4;"><?= $act['description'] ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?= date('M j, g:i A', strtotime($act['date'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
