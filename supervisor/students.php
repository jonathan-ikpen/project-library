<?php
// supervisor/students.php
require_once __DIR__ . '/../middleware/role-guard.php';
require_role('supervisor');

global $pdo;

// Fetch students assigned to this supervisor via their submitted projects
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, 
           COUNT(p.id) as total_projects,
           SUM(CASE WHEN p.supervisor_status = 'pending' THEN 1 ELSE 0 END) as pending_projects
    FROM users u 
    JOIN projects p ON u.id = p.student_id 
    WHERE p.supervisor_id = ?
    GROUP BY u.id, u.name, u.email
    ORDER BY u.name ASC
");
$stmt->execute([$_SESSION['user_id']]);
$students = $stmt->fetchAll();

$use_dashboard_layout = true;
require_once __DIR__ . '/../components/header.php';
?>

<div class="mb-4 flex-between header-actions">
    <div>
        <h3 class="eyebrow mb-1" style="color: var(--accent);">SUPERVISOR</h3>
        <h1>My Students</h1>
        <p class="subtext">Students who have submitted projects under your supervision.</p>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="panel" style="text-align: center; padding: 48px 24px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-secondary); margin-bottom: 16px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <h3 class="mb-2">No Students Assigned</h3>
        <p class="subtext">You will see students here once they select you as a supervisor for their projects.</p>
    </div>
<?php else: ?>
    <div class="panel">
        <div style="overflow-x: auto;">
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--line);">
                        <th style="padding: 12px 16px; color: var(--text-secondary); font-weight: 500;">Student</th>
                        <th style="padding: 12px 16px; color: var(--text-secondary); font-weight: 500; text-align: center;">Total Submissions</th>
                        <th style="padding: 12px 16px; color: var(--text-secondary); font-weight: 500; text-align: center;">Pending Review</th>
                        <th style="padding: 12px 16px; color: var(--text-secondary); font-weight: 500; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php
                            $words = explode(' ', $student['name']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        ?>
                        <tr style="border-bottom: 1px solid var(--line);">
                            <td style="padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--surface); border: 1px solid var(--line); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; color: var(--text-secondary);">
                                        <?= h($initials) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: var(--text-primary); margin-bottom: 2px;"><?= h($student['name']) ?></div>
                                        <div class="subtext" style="font-size: 13px;"><?= h($student['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px; text-align: center; font-weight: 600; font-size: 15px;">
                                <?= h($student['total_projects']) ?>
                            </td>
                            <td style="padding: 16px; text-align: center; font-weight: 600; font-size: 15px; color: <?= $student['pending_projects'] > 0 ? 'var(--accent)' : 'var(--text-primary)' ?>;">
                                <?= h($student['pending_projects']) ?>
                            </td>
                            <td style="padding: 16px; text-align: right;">
                                <a href="projects.php?student_id=<?= $student['id'] ?>" class="btn btn-outline" style="padding: 6px 16px; font-size: 13px;">View Projects</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
