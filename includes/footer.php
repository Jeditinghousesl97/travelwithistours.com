<?php
// Ensure DB connection is available for footer data
if (!isset($pdo)) {
    $db_path = __DIR__ . '/../config/db.php';
    if (file_exists($db_path))
        require_once $db_path;
}

// Fetch Footer Data
$f_services = [];
$f_tours = [];
$f_posts = [];

if (isset($pdo)) {
    try {
        // Col 2: Services
        $stmt_serv = $pdo->query("SELECT id, name FROM services ORDER BY display_order ASC");
        $f_services = $stmt_serv->fetchAll();

        // Col 3: Tours (Featured or Newest, Limit 6)
        $stmt_tours = $pdo->query("SELECT id, name FROM tours ORDER BY is_featured DESC, created_at DESC LIMIT 6");
        $f_tours = $stmt_tours->fetchAll();

        // Col 4: Journal (Limit 5)
        $stmt_posts = $pdo->query("SELECT id, title FROM posts WHERE status='published' ORDER BY created_at DESC LIMIT 5");
        $f_posts = $stmt_posts->fetchAll();
    }
    catch (Exception $e) {
    // Silent fail if tables don't exist yet
    }
}
?>















<footer style="background-color: #ffffff; color: #333; padding: 70px 0 0 0; border-top: 1px solid #f0f0f0; font-family: 'Archivo', sans-serif;">
    <div class="container">
        <div class="footer-grid-5">
            
            <!-- Column 1: Contact Details -->
            <div class="footer-col">
                <a href="index.php" style="display: block; margin-bottom: 20px;">
                    <?php
$footer_logo_path = !empty($h_settings['footer_logo']) ? $h_settings['footer_logo'] : (!empty($h_settings['site_logo']) ? $h_settings['site_logo'] : 'assets/logo/logo.png');
?>
                    <img src="<?php echo htmlspecialchars($footer_logo_path); ?>" alt="<?php echo htmlspecialchars($h_settings['site_title'] ?? 'GPS Lanka Travels'); ?>" style="height: 70px; width: auto;">
                </a>
                
                <?php if (!empty($h_settings['footer_about_text'])): ?>
                    <p style="color: #555; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                        <?php echo nl2br(htmlspecialchars($h_settings['footer_about_text'])); ?>
                    </p>
                <?php
endif; ?>
                
                <div class="contact-info">
                   <?php if (!empty($h_settings['contact_address'])): ?>
                    <div class="c-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <p><?php echo nl2br(htmlspecialchars($h_settings['contact_address'])); ?></p>
                    </div>
                   <?php
endif; ?>
                   
                   <?php if (!empty($h_settings['contact_phone'])): ?>
                    <div class="c-item">
                        <i class="fas fa-phone"></i>
                        <p><?php echo htmlspecialchars($h_settings['contact_phone']); ?></p>
                    </div>
                   <?php
endif; ?>
                   
                   <?php if (!empty($h_settings['contact_email'])): ?>
                    <div class="c-item">
                        <i class="fas fa-envelope"></i>
                        <p><?php echo htmlspecialchars($h_settings['contact_email']); ?></p>
                    </div>
                   <?php
endif; ?>

                   <?php if (!empty($h_settings['contact_whatsapp'])): ?>
                    <div class="c-item">
                        <i class="fab fa-whatsapp"></i>
                        <p><?php echo htmlspecialchars($h_settings['contact_whatsapp']); ?></p>
                    </div>
                   <?php
endif; ?>
                </div>

                <div class="social-icons" style="margin-top: 25px;">
                    <?php if (!empty($h_settings['social_facebook'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_facebook']); ?>"><i class="fa-brands fa-facebook-f"></i></a><?php
endif; ?>
                    <?php if (!empty($h_settings['social_instagram'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_instagram']); ?>"><i class="fa-brands fa-instagram"></i></a><?php
endif; ?>

                    <?php if (!empty($h_settings['social_tiktok'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_tiktok']); ?>"><i class="fa-brands fa-tiktok"></i></a><?php
endif; ?>
                    <?php if (!empty($h_settings['social_google'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_google']); ?>"><i class="fa-brands fa-google"></i></a><?php
endif; ?>
                    <?php if (!empty($h_settings['social_linkedin'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_linkedin']); ?>"><i class="fa-brands fa-linkedin-in"></i></a><?php
endif; ?>
                    <?php if (!empty($h_settings['social_trustpilot'])): ?><a href="<?php echo htmlspecialchars($h_settings['social_trustpilot']); ?>"><i class="fas fa-star"></i></a><?php
endif; ?>
                </div>
            </div>

            <!-- Column 2: Our Services -->
            <div class="footer-col">
                <h3>Our Services</h3>
                <ul class="footer-links">
                    <?php if (count($f_services) > 0): ?>
                        <?php foreach ($f_services as $service): ?>
                            <li><a href="service-details.php?id=<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></a></li>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <li><a href="#">Airport Transfers</a></li>
                        <li><a href="#">Hotel Booking</a></li>
                        <li><a href="#">Tour Guiding</a></li>
                    <?php
endif; ?>
                </ul>
            </div>

            <!-- Column 3: Tour Packages -->
            <div class="footer-col">
                <h3>Popular Packages</h3>
                <ul class="footer-links">
                     <?php if (count($f_tours) > 0): ?>
                        <?php foreach ($f_tours as $tour): ?>
                            <li><a href="tour-details.php?id=<?php echo $tour['id']; ?>"><?php echo htmlspecialchars($tour['name']); ?></a></li>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                         <li><a href="#">Classic Sri Lanka</a></li>
                         <li><a href="#">Honeymoon Special</a></li>
                         <li><a href="#">Wildlife Safari</a></li>
                    <?php
endif; ?>
                </ul>
            </div>

            <!-- Column 4: Journal Posts -->
            <div class="footer-col">
                <h3>Destinations</h3>
                <ul class="footer-links">
                    <?php if (count($f_posts) > 0): ?>
                        <?php foreach ($f_posts as $post): ?>
                            <li><a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                         <li><a href="#">Exploring Sigiriya</a></li>
                         <li><a href="#">Best Time to Visit</a></li>
                         <li><a href="#">Cultural Triangle Guide</a></li>
                    <?php
endif; ?>
                </ul>
            </div>

            <!-- Column 5: Navigation Menu -->
            <div class="footer-col">
                <h3>Quick Navigation</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="all-services.php">Our Services</a></li>
                    <li><a href="all-tours.php">All Tour Packages</a></li>
                    <li><a href="gallery.php">Our Memories</a></li>
                    <li><a href="destinations.php">Destinations</a></li>
                    <li><a href="contact.php">Contact with us</a></li>
                    <li><a href="privacy-policy.php" style="margin-top: 15px; display:block; font-size: 13px;">Privacy Policy</a></li>
                    <li><a href="terms-conditions.php" style="font-size: 13px;">Terms & Conditions</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
             <p><?php echo $h_settings['footer_copyright_text'] ?? '&copy; ' . date('Y') . ' GPS Lanka Travels. All Rights Reserved.'; ?> | Designed by <a href="https://www.asseminate.com" target="_blank">Asseminate</a></p>
        </div>
    </div>
</footer>

<style>
    /* Footer Grid System */
    .footer-grid-5 {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 30px;
        margin-bottom: 50px;
    }

    /* Column Styles */
    .footer-col h3 {
        color: #000;
        font-size: 15px;
        text-transform: uppercase;
        margin-bottom: 25px;
        font-weight: 700;
        letter-spacing: 0.5px;
        font-family: 'Playfair Display', serif;
        position: relative;
    }
    
    .footer-col h3::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 30px;
        height: 2px;
        background-color: var(--accent-color);
    }

    /* Links */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 12px;
    }
    
    .footer-links a {
        color: #555;
        text-decoration: none;
        font-size: 14px;
        transition: 0.3s;
        display: block;
    }
    
    .footer-links a:hover {
        color: var(--accent-color);
        transform: translateX(5px);
    }

    /* Contact Info */
    .c-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 15px;
        color: #555;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .c-item i {
        color: var(--accent-color);
        margin-top: 4px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }
    
    .c-item p {
        margin: 0;
    }

    /* Social Icons */
    .social-icons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .social-icons a {
        width: 36px;
        height: 36px;
        background: #f5f5f5;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s;
        border: 1px solid #eee;
    }
    
    .social-icons a:hover {
        background: var(--accent-color);
        color: #fff;
        border-color: var(--accent-color);
        transform: translateY(-3px);
    }

    /* Bottom Bar */
    .footer-bottom {
        border-top: 1px solid #eee;
        padding: 25px 0;
        text-align: center;
        color: #777;
        font-size: 13px;
    }
    
    .footer-bottom a {
        color: #333;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }
    
    .footer-bottom a:hover {
        color: var(--accent-color);
    }

    /* Responsiveness */
    @media (max-width: 1200px) {
        .footer-grid-5 {
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }
    }
    
    @media (max-width: 768px) {
        .footer-grid-5 {
            grid-template-columns: 1fr;
            gap: 40px;
            
        }
        
        .footer-col h3 {
            margin-bottom: 20px;
        }
    }

    /* Floating WhatsApp Button */
    .floating-whatsapp {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #25d366 !important;
        color: #FFF !important;
        border-radius: 50%;
        text-align: center;
        font-size: 35px;
        box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        text-decoration: none;
        animation: pulse-whatsapp 2s infinite;
    }
    
    .floating-whatsapp:hover {
        background-color: #1ebe57 !important;
        transform: scale(1.1);
        box-shadow: 2px 2px 15px rgba(0,0,0,0.3);
        animation: none;
    }
    
    @keyframes pulse-whatsapp {
        0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
    }
    
    @media (max-width: 768px) {
        .floating-whatsapp {
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 30px;
        }
    }
</style>

<?php if (!empty($h_settings['contact_whatsapp'])): ?>
<!-- Floating WhatsApp Button -->
<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $h_settings['contact_whatsapp']); ?>" class="floating-whatsapp" target="_blank" aria-label="Chat with us on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<?php
endif; ?>

</body>
</html>
