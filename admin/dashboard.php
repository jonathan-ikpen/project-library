<?php
// admin/dashboard.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('admin');

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';

// Fetch overview stats
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_supervisors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'supervisor'")->fetchColumn();
$total_projects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$pending_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE admin_status = 'pending' AND supervisor_status = 'approved'")->fetchColumn();
?>

<div class="mb-4">
    <h3 class="eyebrow mb-1" style="color: var(--accent);">ADMIN</h3>
    <h1 style="font-size: 48px; margin-bottom: 24px;">DEPARTMENT OVERVIEW</h1>
</div>

<div class="grid-stats mb-4">
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Total Students</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px;"><?= h($total_students) ?></div>
    </div>
    
    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Total Supervisors</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px;"><?= h($total_supervisors) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Total Projects</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px;"><?= h($total_projects) ?></div>
    </div>

    <div class="card" style="padding: 24px; text-align: left; border: 1px solid var(--line);">
        <div class="subtext mb-2">Pending Approvals</div>
        <div style="font-family: 'Anton', sans-serif; font-size: 32px; color: var(--danger);"><?= h($pending_projects) ?></div>
    </div>
</div>

<div class="grid-auto-fit">
    <div class="panel">
        <h3 class="eyebrow mb-3">Recent Projects Submitted</h3>
        <?php
        $recent_projects = $pdo->query("SELECT p.id, p.title, p.admin_status, u.name as student_name, p.created_at FROM projects p JOIN users u ON p.student_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();
        if (empty($recent_projects)): ?>
            <p class="subtext">No projects submitted yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--line);">
                            <th style="padding: 12px 0; color: var(--text-secondary); font-weight: 500;">Project</th>
                            <th style="padding: 12px 0; color: var(--text-secondary); font-weight: 500;">Student</th>
                            <th style="padding: 12px 0; color: var(--text-secondary); font-weight: 500;">Date</th>
                            <th style="padding: 12px 0; color: var(--text-secondary); font-weight: 500;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_projects as $rp): ?>
                            <tr style="border-bottom: 1px solid var(--line);">
                                <td style="padding: 12px 0; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <a href="project.php?id=<?= $rp['id'] ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;"><?= h($rp['title']) ?></a>
                                </td>
                                <td style="padding: 12px 0;"><?= h($rp['student_name']) ?></td>
                                <td style="padding: 12px 0; color: var(--text-secondary); font-size: 14px;"><?= date('M j, Y', strtotime($rp['created_at'])) ?></td>
                                <td style="padding: 12px 0;">
                                    <span class="badge <?= h($rp['admin_status']) ?>" style="font-size: 11px;"><?= h($rp['admin_status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 24px;">
                <a href="projects.php" class="btn btn-outline" style="font-size: 14px;">View All Projects</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3 class="eyebrow mb-3">Recent Activities</h3>
        <?php
        // Construct dynamic activity feed
        $activities = [];
        // 1. Recent Users
        $users = $pdo->query("SELECT id, name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($users as $u) {
            $activities[] = [
                'type' => 'user',
                'description' => "New " . h($u['role']) . " registered: " . h($u['name']),
                'date' => $u['created_at']
            ];
        }
        // 2. Recent Projects
        foreach ($recent_projects as $rp) {
            $activities[] = [
                'type' => 'project',
                'description' => "Project submitted: " . h($rp['title']),
                'date' => $rp['created_at']
            ];
        }
        // 3. Recent Messages
        $messages = $pdo->query("SELECT id, name, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
        foreach ($messages as $m) {
            $activities[] = [
                'type' => 'message',
                'description' => "New message from " . h($m['name']) . ": " . h($m['subject']),
                'date' => $m['created_at']
            ];
        }
        
        // Sort and slice top 7
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
                            <?php if ($act['type'] === 'user'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <?php elseif ($act['type'] === 'project'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                            <?php elseif ($act['type'] === 'message'): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
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
