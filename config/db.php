<?php
// Database configuration
$host = 'sdb-83.hosting.stackcp.net';
$dbname = 'gpslankatravels-35303934b956';
$username = 'gpslankatravels-35303934b956';
$password = 'o2eafwoqh5';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
