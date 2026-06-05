<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$filename = "inquiries_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for Excel compatibility with UTF-8
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header Row
fputcsv($output, ['ID', 'Date', 'Type', 'Name', 'Email', 'Phone', 'Message']);

// Fetch Rows
$stmt = $pdo->query("SELECT id, created_at, type, name, email, phone, message FROM inquiries ORDER BY created_at DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
