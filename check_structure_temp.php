<?php
require_once 'config/db.php';
try {
    echo "Columns in tours table:\n";
    $stmt = $pdo->query("DESCRIBE tours");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);

    echo "\nSample Data (first 5):\n";
    $stmt = $pdo->query("SELECT id, name, tour_type FROM tours LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);

    echo "\nDistinct Types:\n";
    $stmt = $pdo->query("SELECT DISTINCT tour_type FROM tours");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($types);

}
catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
