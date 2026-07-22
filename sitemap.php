<?php
require_once 'config/db.php';

header("Content-Type: application/xml; charset=utf-8");

// Fetch Base URL
$base_url = '';
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_base_url'");
$base_url = $stmt->fetchColumn();

if (!$base_url) {
    // Fallback to current domain
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
}
$base_url = rtrim($base_url, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?php echo $base_url; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/about.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/all-services.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/all-tours.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/gallery.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/destinations.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/contact.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?php echo $base_url; ?>/review.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Dynamic Tour Pages -->
    <?php
$tours = $pdo->query("SELECT id, slug FROM tours")->fetchAll();
foreach ($tours as $tour):
    $url = $base_url . '/tour-details.php?id=' . $tour['id'];
    // If you use slugs later: $url = $base_url . '/tour/' . $tour['slug'];
?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
endforeach; ?>

    <!-- Dynamic Post Pages -->
    <?php
$posts = $pdo->query("SELECT id FROM posts WHERE status = 'published'")->fetchAll();
foreach ($posts as $post):
    $url = $base_url . '/post.php?id=' . $post['id'];
?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php
endforeach; ?>

    <!-- Dynamic Service Pages -->
    <?php
$services = $pdo->query("SELECT id FROM services")->fetchAll();
foreach ($services as $service):
    $url = $base_url . '/service-details.php?id=' . $service['id'];
?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php
endforeach; ?>
</urlset>
