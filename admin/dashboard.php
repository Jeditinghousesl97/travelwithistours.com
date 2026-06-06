<?php
require_once '../config/db.php';
require_once '../includes/short_videos.php';
ensure_short_videos_schema($pdo);
include 'includes/header.php';

// Fetch Counts
try {
    $stats = [
        'tours' => ['count' => $pdo->query("SELECT COUNT(*) FROM tours")->fetchColumn(), 'label' => 'Total Tours', 'icon' => 'fa-plane', 'color' => '#007bff', 'bg' => '#e3f2fd', 'link' => 'tours.php'],
        'inquiries' => ['count' => $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn(), 'label' => 'Inquiries', 'icon' => 'fa-envelope', 'color' => '#28a745', 'bg' => '#e8f5e9', 'link' => 'inquiries.php'],
        'posts' => ['count' => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(), 'label' => 'Blog Posts', 'icon' => 'fa-newspaper', 'color' => '#6f42c1', 'bg' => '#f3e5f5', 'link' => 'posts.php'],
        'testimonials' => ['count' => $pdo->query("SELECT COUNT(*) FROM testimonials")->fetchColumn(), 'label' => 'Testimonials', 'icon' => 'fa-star', 'color' => '#ffc107', 'bg' => '#fff8e1', 'link' => 'testimonials.php'],
        'services' => ['count' => $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn(), 'label' => 'Services', 'icon' => 'fa-concierge-bell', 'color' => '#17a2b8', 'bg' => '#e0f7fa', 'link' => 'services.php'],
        'gallery' => ['count' => $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn(), 'label' => 'Gallery Images', 'icon' => 'fa-images', 'color' => '#e83e8c', 'bg' => '#fce4ec', 'link' => 'gallery.php'],
        'partners' => ['count' => $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn(), 'label' => 'Partners', 'icon' => 'fa-handshake', 'color' => '#fd7e14', 'bg' => '#fff3e0', 'link' => 'partners.php'],
        'hero_slides' => ['count' => $pdo->query("SELECT COUNT(*) FROM hero_slides")->fetchColumn(), 'label' => 'Hero Slides', 'icon' => 'fa-layer-group', 'color' => '#6c757d', 'bg' => '#e9ecef', 'link' => 'slider.php'],
        'short_videos' => ['count' => $pdo->query("SELECT COUNT(*) FROM short_videos")->fetchColumn(), 'label' => 'Short Videos', 'icon' => 'fa-film', 'color' => '#20c997', 'bg' => '#e8fff7', 'link' => 'short-videos.php'],
    ];
}
catch (Exception $e) {
    // Fallback if a table doesn't exist
    $stats = [];
    $error = "Error loading stats: " . $e->getMessage();
}

// Fetch Recent Inquiries
$stmt = $pdo->prepare("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_inquiries = $stmt->fetchAll();
?>

<style>
    /* Enhanced Dashboard Styles */
    .welcome-banner {
        background: linear-gradient(135deg, #00bcd4, #0097a7);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 188, 212, 0.2);
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner h2 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    
    .welcome-banner p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card-new {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card-new:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    
    .stat-content h3 {
        font-size: 32px;
        margin: 0 0 5px 0;
        font-weight: 700;
        color: #333;
    }
    
    .stat-content span {
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .recent-inquiries {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .section-header h3 {
        margin: 0;
        font-size: 18px;
        color: #333;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-booking { background: #e3f2fd; color: #1565c0; }
    .badge-contact { background: #f3e5f5; color: #7b1fa2; }
    
    .action-row {
        margin-top: 30px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .quick-action-btn {
        background: #fff;
        border: 1px solid #eee;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        color: #555;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.2s;
    }
    
    .quick-action-btn:hover {
        background: #f8f9fa;
        border-color: #ddd;
        color: #00bcd4;
    }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .stat-card-new, .recent-inquiries {
        animation: fadeIn 0.4s ease-out forwards;
    }
    
    .stat-card-new:nth-child(1) { animation-delay: 0.05s; }
    .stat-card-new:nth-child(2) { animation-delay: 0.1s; }
    .stat-card-new:nth-child(3) { animation-delay: 0.15s; }
    .stat-card-new:nth-child(4) { animation-delay: 0.2s; }
    .stat-card-new:nth-child(5) { animation-delay: 0.25s; }
    .stat-card-new:nth-child(6) { animation-delay: 0.3s; }
    .stat-card-new:nth-child(7) { animation-delay: 0.35s; }
    .stat-card-new:nth-child(8) { animation-delay: 0.4s; }

</style>

<div class="welcome-banner">
    <div>
        <h2>Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! 👋</h2>
        <p>Welcome to your dashboard. Here's an overview of your website currently.</p>
    </div>
    <i class="fas fa-chart-line" style="position: absolute; right: 30px; bottom: -20px; font-size: 150px; opacity: 0.1;"></i>
</div>

<div class="stats-grid">
    <?php foreach ($stats as $key => $stat): ?>
    <a href="<?php echo $stat['link']; ?>" class="stat-card-new">
        <div class="stat-content">
            <h3><?php echo number_format($stat['count']); ?></h3>
            <span><?php echo $stat['label']; ?></span>
        </div>
        <div class="stat-icon" style="background-color: <?php echo $stat['bg']; ?>; color: <?php echo $stat['color']; ?>;">
            <i class="fas <?php echo $stat['icon']; ?>"></i>
        </div>
    </a>
    <?php
endforeach; ?>
</div>

<div class="recent-inquiries">
    <div class="section-header">
        <h3>Recent Inquiries</h3>
        <a href="inquiries.php" class="btn" style="font-size: 12px; padding: 6px 12px;">View All</a>
    </div>
    
    <div class="table-responsive">
        <?php if (count($recent_inquiries) > 0): ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #eee;">
                    <th style="padding: 12px; color: #888; font-weight: 500;">Date</th>
                    <th style="padding: 12px; color: #888; font-weight: 500;">Name</th>
                    <th style="padding: 12px; color: #888; font-weight: 500;">Type</th>
                    <th style="padding: 12px; color: #888; font-weight: 500;">Message Preview</th>
                    <th style="padding: 12px; text-align: right; color: #888; font-weight: 500;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_inquiries as $row): ?>
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 12px;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td style="padding: 12px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td style="padding: 12px;">
                        <?php if ($row['type'] == 'booking'): ?>
                            <span class="badge badge-booking">Booking</span>
                        <?php
        else: ?>
                            <span class="badge badge-contact">Contact</span>
                        <?php
        endif; ?>
                    </td>
                    <td style="padding: 12px; color: #666; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($row['message']); ?>
                    </td>
                    <td style="padding: 12px; text-align: right;">
                        <a href="inquiries.php" style="color: #00bcd4; font-size: 14px;"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php
    endforeach; ?>
            </tbody>
        </table>
        <?php
else: ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No new inquiries found.</p>
            </div>
        <?php
endif; ?>
    </div>
</div>

<div class="action-row">
    <a href="tour-edit.php" class="quick-action-btn">
        <i class="fas fa-plus-circle" style="color: #007bff;"></i> Add New Tour
    </a>
    <a href="post-edit.php" class="quick-action-btn">
        <i class="fas fa-pen-nib" style="color: #6f42c1;"></i> Write Blog Post
    </a>
    <a href="gallery.php" class="quick-action-btn">
        <i class="fas fa-camera" style="color: #e83e8c;"></i> Upload Photos
    </a>
    <a href="short-videos.php" class="quick-action-btn">
        <i class="fas fa-film" style="color: #20c997;"></i> Upload Short Videos
    </a>
    <a href="settings.php" class="quick-action-btn">
        <i class="fas fa-cog" style="color: #6c757d;"></i> Settings
    </a>
</div>

<?php include 'includes/footer.php'; ?>
