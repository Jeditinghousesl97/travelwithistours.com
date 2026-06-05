<?php
require_once 'config/db.php';
include 'includes/header.php'; ?>


<!-- Page Header -->
<!-- Page Header -->
<section class="page-header contact-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/contact-us.webp'); background-color: #f4f4f4; padding: 100px 0; text-align: center; background-size: cover; background-position: center; position: relative;">
    <div class="container" style="position: relative; z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #fff; margin-bottom: 10px;">Let's Connect</h1>
        <p style="color: #eee; font-size: 18px; max-width: 600px; margin: 0 auto;">We are here to help you plan your perfect Sri Lankan getaway.</p>
    </div>
</section>





<!-- Contact Info & Form -->
<?php
// Fetch Settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
catch (Exception $e) {
}
?>
<section class="section" style="padding: 80px 0;">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-details">
                <h2 style="margin-bottom: 30px;">Get in Touch</h2>
                
                <div style="margin-bottom: 25px;">
                    <h3><i class="fas fa-map-marker-alt" style="color: var(--accent-color); width: 30px;"></i> Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($settings['contact_address'] ?? '#10/2, Mahatenagama, Elkaduwa, Matale, Sri Lanka.')); ?></p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h3><i class="fas fa-phone" style="color: var(--accent-color); width: 30px;"></i> Phone</h3>
                    <p>
                        <?php echo htmlspecialchars($settings['contact_phone'] ?? '+94 77 584 0718'); ?><br>
                        <?php if (!empty($settings['contact_whatsapp'])): ?>
                            <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($settings['contact_whatsapp']); ?>
                        <?php
endif; ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h3><i class="fas fa-envelope" style="color: var(--accent-color); width: 30px;"></i> Email</h3>
                    <p><?php echo htmlspecialchars($settings['contact_email'] ?? 'info@travelwithistours.com'); ?></p>
                </div>

                <div style="margin-top: 40px;">
                    <h3 style="margin-bottom: 15px;">Follow Us</h3>
                    <div style="display: flex; gap: 15px; font-size: 24px;">
                        <?php if (!empty($settings['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank" style="color: #3b5998;"><i class="fa-brands fa-facebook"></i></a>
                        <?php
endif; ?>
                        
                        <?php if (!empty($settings['social_instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_instagram']); ?>" target="_blank" style="color: #bc2a8d;"><i class="fa-brands fa-instagram"></i></a>
                        <?php
endif; ?>



                        <?php if (!empty($settings['social_tiktok'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_tiktok']); ?>" target="_blank" style="color: #000000;"><i class="fa-brands fa-tiktok"></i></a>
                        <?php
endif; ?>

                        <?php if (!empty($settings['social_google'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_google']); ?>" target="_blank" style="color: #4285F4;"><i class="fa-brands fa-google"></i></a>
                        <?php
endif; ?>

                        <?php if (!empty($settings['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_linkedin']); ?>" target="_blank" style="color: #0077b5;"><i class="fa-brands fa-linkedin"></i></a>
                        <?php
endif; ?>

                        <?php if (!empty($settings['social_trustpilot'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['social_trustpilot']); ?>" target="_blank" style="color: #00b67a;"><i class="fas fa-star"></i></a> 
                        <?php
endif; ?>
                    </div>
                </div>
            </div>

            <div class="contact-form-container">
                <h2 style="margin-bottom: 20px;">Send Us a Message</h2>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">Thank you! Your message has been sent.</div>
                <?php
endif; ?>
                
                <form action="process-contact.php" method="POST">
                    <input type="hidden" name="type" value="contact">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" class="form-control" required></textarea>
                    </div>

                    <!-- Cloudflare Turnstile -->
                    <?php if (!empty($settings['cf_site_key'])): ?>
                        <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($settings['cf_site_key']); ?>" style="margin-bottom: 20px;"></div>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <?php
endif; ?>

                    <button type="submit" class="btn" style="width: 100%; font-size: 16px;">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Google Map -->
<section style="height: 600px; background: #eee;">
    <iframe src="<?php echo htmlspecialchars($settings['contact_map_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.58585979667!2d79.80922114674345!3d6.9271139436440265!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae253d10f7a7003%3A0x320b2e4d32d3838d!2sColombo!5e0!3m2!1sen!2slk!4v1645000000000!5m2!1sen!2slk'); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</section>











<!-- Why Choose Us Section -->
<section class="section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <h2 class="section-title" style="text-align: center; border-left: none; padding-left: 0; font-size: 42px; margin-bottom: 60px; font-family: 'Playfair Display', serif;">Why choose us?</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 50px; text-align: center;">
            
            <?php
$choose_items = [];
try {
    $stmt = $pdo->query("SELECT * FROM why_choose_us WHERE status = 1 ORDER BY display_order ASC");
    $choose_items = $stmt->fetchAll();
}
catch (Exception $e) {
// Table might not exist yet
}

if (count($choose_items) > 0):
    foreach ($choose_items as $item):
?>
            <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: <?php echo htmlspecialchars($item['bg_color']); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: <?php echo htmlspecialchars($item['icon_color']); ?>;">
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;"><?php echo htmlspecialchars($item['title']); ?></h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;"><?php echo htmlspecialchars($item['description']); ?></p>
            </div>
            <?php
    endforeach;
else:
?>
                <p style="text-align:center; width:100%; color:#999;">Configure "Why choose us" section items in the Admin Dashboard.</p>
            <?php
endif;
?>

        </div>
    </div>
</section>












<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        // Simple client-side check if Turnstile is present
        var turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');
        if (turnstileResponse && !turnstileResponse.value) {
            e.preventDefault();
            alert('Please complete the security check (Turnstile) before sending.');
        }
    });
</script>


<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
