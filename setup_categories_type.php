<?php
require_once 'config/db.php';
// Add 'type' column to categories table if not exists with default 'tour'
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN type ENUM('tour', 'blog') NOT NULL DEFAULT 'tour'");
    echo "Added 'type' column to categories table.\n";
}
catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "'type' column already exists in categories table.\n";
    }
    else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
