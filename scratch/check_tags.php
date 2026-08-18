<?php
require 'config/connect.php';

$stmt = $pdo->prepare("SELECT id, title FROM projects");
$stmt->execute();
$projects = $stmt->fetchAll();

echo "Projects:\n";
foreach ($projects as $p) {
    echo "{$p['id']}: {$p['title']}\n";
    
    $stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN project_tags pt ON t.id = pt.tag_id WHERE pt.project_id = ?");
    $stmtTags->execute([$p['id']]);
    $tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
    echo "  Tags: " . implode(', ', $tags) . "\n";
}
