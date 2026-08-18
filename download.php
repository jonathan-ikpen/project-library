<?php
// download.php
require_once __DIR__ . '/config/init.php';

$file_id = (int)($_GET['id'] ?? 0);
if (!$file_id) {
    die("Invalid request.");
}

global $pdo;
$stmt = $pdo->prepare("
    SELECT f.file_name, f.original_name, f.mime_type, p.admin_status, p.supervisor_status, p.student_id, p.supervisor_id 
    FROM files f 
    JOIN projects p ON f.project_id = p.id 
    WHERE f.id = ?
");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    die("File not found.");
}

// Access Control
$can_download = false;

// If it's published, anyone can download
if ($file['admin_status'] === 'published') {
    $can_download = true;
} else if (is_logged_in()) {
    // Admins can download anything
    if (has_role('admin')) {
        $can_download = true;
    } 
    // Supervisors can download if assigned to them
    else if (has_role('supervisor') && $_SESSION['user_id'] == $file['supervisor_id']) {
        $can_download = true;
    }
    // Students can download their own files
    else if (has_role('student') && $_SESSION['user_id'] == $file['student_id']) {
        $can_download = true;
    }
}

if (!$can_download) {
    die("Unauthorized access to file.");
}

$file_path = __DIR__ . '/uploads/' . $file['file_name'];

if (!file_exists($file_path)) {
    die("File no longer exists on the server.");
}

// Serve file securely
header('Content-Description: File Transfer');
header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
flush();
readfile($file_path);
exit;
