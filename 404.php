<?php
http_response_code(404);
require_once 'config/db.php';

// Set page-specific SEO
$page_title = "404 - Page Not Found";
$page_description = "The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.";

include 'includes/header.php';
?>

<div style="padding: 120px 0; text-align: center; background: #fff;">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="font-size: 150px; font-weight: 900; color: #f0f0f0; line-height: 1; margin-bottom: -40px; font-family: 'Playfair Display', serif;">404</div>
            <h1 style="font-family: 'Playfair Display', serif; font-size: 42px; color: #222; margin-bottom: 20px; position: relative; z-index: 1;">Oops! Page Not Found</h1>
            <p style="font-size: 18px; color: #666; line-height: 1.8; margin-bottom: 40px;">
                The page you are looking for might have been removed, had its name changed, or is temporarily unavailable. 
                Let's get you back on track to your Sri Lankan adventure.
            </p>
            
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="index.php" class="btn" style="padding: 15px 35px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px;">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="contact.php" class="btn" style="background: #333; color: #fff; padding: 15px 35px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px;">
                    <i class="fas fa-envelope"></i> Contact Support
                </a>
            </div>

            <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #eee;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 20px; color: #333; margin-bottom: 25px;">Quick Links</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
                    <a href="all-tours.php" style="background: #f8f9fa; color: #555; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-size: 14px; transition: 0.3s;" onmouseover="this.style.background='#eee'" onmouseout="this.style.background='#f8f9fa'">Tour Packages</a>
                    <a href="destinations.php" style="background: #f8f9fa; color: #555; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-size: 14px; transition: 0.3s;" onmouseover="this.style.background='#eee'" onmouseout="this.style.background='#f8f9fa'">Destinations</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
