<?php
require_once '../config/db.php';

// Simple authentication check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $target_dir = "../assets/images/gallery/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($_FILES['file']['error'] == 0) {
        $file_name = time() . '_' . rand(100, 999) . '_' . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $image_path = "assets/images/gallery/" . $file_name;
            $stmt = $pdo->prepare("INSERT INTO gallery (image_path, caption, alt_text) VALUES (?, ?, ?)");
            $stmt->execute([$image_path, '', '']);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'path' => $image_path,
                'id' => $pdo->lastInsertId()
            ]);
            exit;
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['error' => 'Upload failed']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
exit;
?>
