<?php
// student/dashboard.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('student');

global $pdo;

// Fetch student's projects
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.supervisor_status, p.admin_status, p.created_at, c.name as category_name
    FROM projects p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';

// Fetch overview stats
$total_uploads = $pdo->query("SELECT COUNT(*) FROM projects WHERE student_id = {$_SESSION['user_id']}")->fetchColumn();
$approved_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE student_id = {$_SESSION['user_id']} AND supervisor_status = 'approved' AND admin_status = 'published'")->fetchColumn();
$pending_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE student_id = {$_SESSION['user_id']} AND (supervisor_status = 'pending' OR admin_status = 'pending')")->fetchColumn();
$rejected_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE student_id = {$_SESSION['user_id']} AND (supervisor_status = 'rejected' OR admin_status = 'rejected')")->fetchColumn();
?>

<div class="mb-4 flex-between header-actions">
    <div>
        <h3 class="eyebrow mb-1" style="color: var(--accent);">STUDENT</h3>
        <h1 style="font-size: 48px;">DEPARTMENT OVERVIEW</h1>
    </div>
    <a href="upload.php" class="btn">Upload New Project</a>
</div>

<div class="grid-stats mb-4">
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Total Uploads</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px;"><?= h($total_uploads) ?></div>
    </div>
    
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Fully Published</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--success);"><?= h($approved_projects) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Pending Review</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--accent);"><?= h($pending_projects) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Rejected</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--danger);"><?= h($rejected_projects) ?></div>
    </div>
</div>

<div class="grid-auto-fit" style="align-items: stretch;">
    
    <!-- Recent Submissions -->
    <div class="panel">
        <h3 class="eyebrow mb-3">Recent Submissions</h3>
        <?php
        $recent_subs = array_slice($projects, 0, 3);
        if (empty($recent_subs)): ?>
            <p class="subtext">No projects submitted yet.</p>
        <?php else: ?>
            <div class="flex-column" style="gap: 16px;">
                <?php foreach ($recent_subs as $p): ?>
                    <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; border: 1px solid var(--line); border-radius: 8px;">
                        <div style="flex: 1;">
                            <div class="flex-between mb-1">
                                <div style="font-weight: 600; font-size: 15px;"><?= h($p['title']) ?></div>
                                <div>
                                    <?php if ($p['admin_status'] === 'published'): ?>
                                        <span class="badge published" style="font-size: 11px;">PUBLISHED</span>
                                    <?php elseif ($p['admin_status'] === 'rejected' || $p['supervisor_status'] === 'rejected'): ?>
                                        <span class="badge rejected" style="font-size: 11px;">REJECTED</span>
                                    <?php elseif ($p['supervisor_status'] === 'approved'): ?>
                                        <span class="badge approved" style="font-size: 11px;">APPROVED</span>
                                    <?php else: ?>
                                        <span class="badge pending" style="font-size: 11px;">PENDING</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="subtext mb-2" style="font-size: 13px;"><?= h($p['category_name']) ?></div>
                            <a href="project.php?id=<?= $p['id'] ?>" class="btn btn-outline" style="padding: 4px 12px; font-size: 12px;">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($projects) > 3): ?>
                <div class="mt-3 text-center">
                    <a href="projects.php" class="subtext" style="font-size: 13px; text-decoration: underline;">View all submissions</a>
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
                'description' => "You submitted project: " . h($rp['title']),
                'date' => $rp['created_at']
            ];
            if ($rp['supervisor_status'] === 'approved') {
                $activities[] = [
                    'type' => 'status',
                    'description' => "Supervisor approved: " . h($rp['title']),
                    'date' => date('Y-m-d H:i:s', strtotime($rp['created_at']) + 86400) // Dummy time for demo
                ];
            }
            if ($rp['admin_status'] === 'published') {
                $activities[] = [
                    'type' => 'status',
                    'description' => "Admin published: " . h($rp['title']),
                    'date' => date('Y-m-d H:i:s', strtotime($rp['created_at']) + 172800) // Dummy time for demo
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
