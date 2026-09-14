<?php
// update.php
require_once __DIR__ . '/config/init.php';

global $pdo;
$output = [];
$output[] = "Starting database migration...";

try {
    // 1. Add level column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'level'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `level` VARCHAR(50) DEFAULT NULL AFTER `status`");
        $output[] = "✓ Added 'level' column to users table.";
    } else {
        $output[] = "- 'level' column already exists.";
    }

    // 2. Add mat_no column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'mat_no'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `mat_no` VARCHAR(100) DEFAULT NULL AFTER `level`");
        $output[] = "✓ Added 'mat_no' column to users table.";
    } else {
        $output[] = "- 'mat_no' column already exists.";
    }

    // 3. Add year column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'year'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `year` INT DEFAULT NULL AFTER `mat_no`");
        $output[] = "✓ Added 'year' column to users table.";
    } else {
        $output[] = "- 'year' column already exists.";
    }

    // 4. Backfill existing students with missing data so filters aren't empty
    $updateStmt = $pdo->exec("UPDATE `users` SET `level` = 'M26', `year` = 2026, `mat_no` = 'M.23/ND/CSIT/11533' WHERE `role` = 'student' AND (`level` IS NULL OR `year` IS NULL)");
    if ($updateStmt !== false && $updateStmt > 0) {
        $output[] = "✓ Updated $updateStmt existing student(s) with default level, year, and matric number.";
    } else {
        $output[] = "- No students needed backfilling.";
    }

    $output[] = "Database migration completed successfully!";

} catch (Exception $e) {
    $output[] = "❌ Error: " . $e->getMessage();
}

// Render output in a clean layout matching the app's style
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Migration</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fafafa; padding: 40px; color: #111; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h1 { margin-top: 0; font-size: 24px; border-bottom: 1px solid #eee; padding-bottom: 16px; margin-bottom: 24px; }
        .log-line { padding: 8px 12px; margin-bottom: 8px; border-radius: 6px; font-family: monospace; background: #f9f9f9; }
        .log-line.success { color: #059669; background: #ecfdf5; }
        .log-line.error { color: #dc2626; background: #fef2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migration Logs</h1>
        <?php foreach ($output as $line): ?>
            <?php 
                $class = '';
                if (strpos($line, '✓') === 0 || strpos($line, 'successfully') !== false) $class = 'success';
                if (strpos($line, '❌') === 0) $class = 'error';
            ?>
            <div class="log-line <?= $class ?>"><?= htmlspecialchars($line) ?></div>
        <?php endforeach; ?>
        
        <div style="margin-top: 24px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #111; color: white; text-decoration: none; border-radius: 6px;">Return to Home</a>
        </div>
    </div>
</body>
</html>
