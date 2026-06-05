<?php
// admin/upload_image.php
require_once '../config/db.php'; // Ensure session/auth check if in db.php or header

// Basic Session Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// Allowed origins (optional, for safety)
// header("Access-Control-Allow-Origin: *");

$imageFolder = "../assets/images/content/";

if (!file_exists($imageFolder)) {
    mkdir($imageFolder, 0777, true);
}

reset($_FILES);
$temp = current($_FILES);

if (is_uploaded_file($temp['tmp_name'])) {

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png", "webp", "jpeg"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    // Accept upload
    $filetowrite = $imageFolder . time() . "_" . $temp['name'];

    // Move
    if (move_uploaded_file($temp['tmp_name'], $filetowrite)) {
        // Respond with JSON location
        // Note: We need the web path, not the file system path
        // Script is in /admin/, image is in /assets/images/content/
        // So web path is ../assets/images/content/filename relative to admin, 
        // OR better: absolute path from root. 
        // Assuming site root is /lankaleisureparadise/

        // Let's return relative path from the page that uses it (tour-edit.php in admin)
        // So "../assets/images/content/..." is valid for the img src if displayed in admin.
        // But on frontend (root/tour-details.php), "../assets..." is NOT valid. It should be "assets/..."

        // Best practice: Use relative to domain root or standard relative path that works.
        // If we return "assets/images/content/...", it works for frontend.
        // For admin (in /admin/), we need to ensure the TinyMCE preview works. 
        // TinyMCE in /admin/ will see "assets/..." and look for /admin/assets/... which is wrong.
        // It needs "../assets/..."

        // COMPROMISE: Store as "assets/images/content/..." in DB.
        // TinyMCE needs to be configured to prepend "../" for preview? 
        // Or we just return the full relative path "../assets/images/content/..." 
        // AND when rendering on Frontend, we shouldn't have to change it if it's relative?
        // Wait, if I save "../assets/..." in DB:
        // Frontend is in /index.php. "../assets" goes to parent of root? No.

        // Let's use absolute path from web root? "/lankaleisureparadise/assets/..."
        // But local dev path might differ from production.

        // Let's stick to returning the path relative to the Calling Script?
        // Actually, TinyMCE `images_upload_handler` generally expects a location.

        // Let's try returning the path that works for the Admin preview first: "../assets/images/content/filename"
        // When saving to DB, we might want to strip "../" ?
        // Or just save it as is.
        // Frontend: <img src="../assets/..." > inside `tour-details.php` (root).
        // `assets` is in root. `../assets` would go up one level out of htdocs?

        // OK. Let's return the path relative to the PROJECT ROOT: "assets/images/content/filename"
        // And then in TinyMCE, we configure `document_base_url` or `relative_urls`.

        // Let's try: "assets/images/content/file.jpg"
        // And we will use a leading slash if we can auto-detect project root.

        // Simple fix: Store "../assets/images/content/file.jpg" because that works in Admin.
        // In Frontend `tour-details.php`, we can do a str_replace('../assets', 'assets', $content).

        $filename = time() . "_" . $temp['name'];
        $webPath = "../assets/images/content/" . $filename;

        echo json_encode(array('location' => $webPath));
    }
    else {
        header("HTTP/1.1 500 Upload failed.");
    }
}
else {
    header("HTTP/1.1 500 No file uploaded.");
}
?>
