<?php
require __DIR__ . '/../config/connect.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active'");
    echo "Column 'status' added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'status' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
