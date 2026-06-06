<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="text-align: center; width: 100%;">
            <img src="<?php echo htmlspecialchars($admin_site_logo ?? '../assets/logo/logo.png'); ?>" alt="Admin Logo" style="height: 50px; width: auto; margin-bottom: 5px; display: block; margin: 0 auto;">
            <h3 style="margin: 5px 0 0 0; font-size: 16px; color: #333;">Admin Dashboard</h3>
        </div>
        <!-- Close button for mobile -->
        <button onclick="toggleSidebar()" class="mobile-close-btn" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; display: none;">&times;</button>
    </div>
    
    <div class="sidebar-menu">
        <ul>
            <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="tours.php" class="<?php echo($current_page == 'tours.php' || $current_page == 'tour-edit.php' || $current_page == 'tour-itinerary.php') ? 'active' : ''; ?>"><i class="fas fa-plane"></i> Tours</a></li>
            <li><a href="tour-categories.php" class="<?php echo $current_page == 'tour-categories.php' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Tour Categories</a></li>
            <li><a href="inquiries.php" class="<?php echo $current_page == 'inquiries.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="posts.php" class="<?php echo($current_page == 'posts.php' || $current_page == 'post-edit.php') ? 'active' : ''; ?>"><i class="fas fa-newspaper"></i> Blog Posts</a></li>
            <li><a href="blog-categories.php" class="<?php echo $current_page == 'blog-categories.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Blog Categories</a></li>
            <li><a href="services.php" class="<?php echo $current_page == 'services.php' ? 'active' : ''; ?>"><i class="fas fa-concierge-bell"></i> Services</a></li>
            <li><a href="gallery.php" class="<?php echo $current_page == 'gallery.php' ? 'active' : ''; ?>"><i class="fas fa-images"></i> Gallery</a></li>
            <li><a href="testimonials.php" class="<?php echo $current_page == 'testimonials.php' ? 'active' : ''; ?>"><i class="fas fa-comment-alt"></i> Testimonials</a></li>
            <li><a href="tripadvisor-reviews.php" class="<?php echo $current_page == 'tripadvisor-reviews.php' ? 'active' : ''; ?>"><i class="fab fa-tripadvisor"></i> TripAdvisor Reviews</a></li>
            <li><a href="partners.php" class="<?php echo $current_page == 'partners.php' ? 'active' : ''; ?>"><i class="fas fa-handshake"></i> Partners</a></li>
            <li><a href="slider.php" class="<?php echo $current_page == 'slider.php' ? 'active' : ''; ?>"><i class="fas fa-images"></i> Homepage Slider</a></li>
            <li><a href="short-videos.php" class="<?php echo $current_page == 'short-videos.php' ? 'active' : ''; ?>"><i class="fas fa-film"></i> Short Videos</a></li>
            <li><a href="why-choose-us.php" class="<?php echo $current_page == 'why-choose-us.php' ? 'active' : ''; ?>"><i class="fas fa-award"></i> Why Choose Us</a></li>
            <li><a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="clear_cache.php" class="<?php echo $current_page == 'clear_cache.php' ? 'active' : ''; ?>" style="color: #ff9800;"><i class="fas fa-broom"></i> Purge Cache</a></li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" style="color: #00bcd4; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 25px; border-radius: 5px; transition: background 0.2s; margin-bottom: 5px;">
            <i class="fas fa-external-link-alt"></i> Visit Website
        </a>
        <a href="logout.php" style="color: #d9534f; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 25px; border-radius: 5px; transition: background 0.2s;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .mobile-close-btn {
            display: block !important;
            position: absolute;
            top: 15px;
            right: 15px;
        }
    }
    
    .sidebar-menu::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 3px;
    }
    
    .sidebar-footer a:hover {
        background-color: #ffebee;
    }
</style>
