<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Optional: Delete the image file from server associated with this tour
    // $stmt = $pdo->prepare("SELECT thumbnail FROM tours WHERE id = ?");
    // $stmt->execute([$id]);
    // $tour = $stmt->fetch();
    // if($tour && $tour['thumbnail'] && file_exists("../" . $tour['thumbnail'])) {
    //     unlink("../" . $tour['thumbnail']);
    // }

    $stmt = $pdo->prepare("DELETE FROM tours WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: tours.php?msg=deleted");
    }
    else {
        header("Location: tours.php?error=failed");
    }
}
else {
    header("Location: tours.php");
}
?>
