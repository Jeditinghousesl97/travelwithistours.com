<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    // Check if we are in a subdirectory (like includes/) or root admin/
    // Since this file is in admin/includes/, it will be included by files in admin/
    // So 'login.php' is correct relative to the including file (e.g. admin/tour-edit.php)
    header("Location: login.php");
    exit;
}
?>
