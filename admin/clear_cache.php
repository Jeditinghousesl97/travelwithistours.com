<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// 2. Clear stat cache
clearstatcache(true);

// 3. Purge LiteSpeed Cache
if (class_exists('LiteSpeed\Purge')) {
    \LiteSpeed\Purge::purge_all();
}
else {
    header("X-LiteSpeed-Purge: *");
}

// 4. APCu Cache
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
}

$page_title = "Clear Cache";
include 'includes/header.php';
?>

<div class="header">
    <h2>Cache Purge</h2>
</div>

<div class="card" style="text-align: center; padding: 50px 20px;">
    <div style="font-size: 60px; color: #28a745; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i>
    </div>
    <h3 style="margin-bottom: 10px;">Cache Cleared Successfully</h3>
    <p style="color: #666; max-width: 500px; margin: 0 auto 30px auto; line-height: 1.6;">
        All server-side caches (OPCache, File Status Cache, LiteSpeed) have been purged. 
        <br><br>
        <strong>Note:</strong> If you updated images and they are still not showing up, your web browser might be caching them. Try pressing <code>Ctrl + F5</code> (Windows) or <code>Cmd + Shift + R</code> (Mac) on your website to hard refresh your browser.
    </p>
    <a href="dashboard.php" class="btn" style="display: inline-flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php include 'includes/footer.php'; ?>
