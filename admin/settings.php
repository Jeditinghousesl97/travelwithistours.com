<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/db.php';

$success = '';
$error = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Handle Password Change
    if (!empty($_POST['new_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($confirm)) {
            $error = "To change password, please fill all password fields.";
        }
        elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        }
        else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($current, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $_SESSION['user_id']]);

                header("Location: settings.php?msg=pass_success");
                exit;
            }
            else {
                $error = "Current password is incorrect.";
            }
        }
    }

    // Remove password fields so they aren't saved to settings table
    unset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password']);

    // Process general settings only if there's no error from password change
    if (empty($error)) {
        // Handle File Uploads (Main Logo)
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
            $target_dir = "../assets/logo/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['png', 'ico', 'jpg', 'jpeg', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = "logo_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_file)) {
                    $logo_path = "assets/logo/" . $new_filename;
                    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute(['site_logo', $logo_path]);
                }
            }
        }

        // Handle File Uploads (Footer Logo)
        if (isset($_FILES['footer_logo']) && $_FILES['footer_logo']['error'] == 0) {
            $target_dir = "../assets/logo/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['footer_logo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['png', 'ico', 'jpg', 'jpeg', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = "footer_logo_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['footer_logo']['tmp_name'], $target_file)) {
                    $logo_path = "assets/logo/" . $new_filename;
                    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute(['footer_logo', $logo_path]);
                }
            }
        }

        // Handle File Uploads (Favicon)
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
            $target_dir = "../assets/images/logo/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['png', 'ico', 'jpg', 'jpeg', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = "favicon_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $target_file)) {
                    $favicon_path = "assets/images/logo/" . $new_filename;
                    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute(['site_favicon', $favicon_path]);
                }
            }
        }

        // Handle File Uploads (About Image)
        if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
            $target_dir = "../assets/images/about/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['about_image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['png', 'jpg', 'jpeg', 'webp'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = "about_home_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES['about_image']['tmp_name'], $target_file)) {
                    $about_image_path = "assets/images/about/" . $new_filename;
                    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute(['about_image', $about_image_path]);
                }
            }
        }

        foreach ($_POST as $key => $value) {
            // Update or Insert setting
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        // Redirect to prevent form resubmission
        header("Location: settings.php?msg=success");
        exit;
    }
}

// Handle Success Message from Redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'success') {
        $success = "Settings saved successfully!";
    }
    elseif ($_GET['msg'] == 'pass_success') {
        $success = "Password changed successfully!";
    }
}

// Fetch Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/header.php';
?>

<div class="header">
    <h2>General Settings</h2>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php
endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div><?php
endif; ?>

<div class="tabs" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; flex-wrap: wrap;">
    <button type="button" class="tab-btn active" onclick="openTab(event, 'general')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">General</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'account')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Account / Password</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'seo')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">SEO</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'contact')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Contact</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'smtp')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Email (SMTP)</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'social')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Social Media</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'security')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Security</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'maintenance')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Maintenance / Coming Soon</button>
    <button type="button" class="tab-btn" onclick="openTab(event, 'about-home')" style="background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #666; transition: 0.3s; border-radius: 5px;">Homepage About</button>
</div>

<form method="POST" enctype="multipart/form-data">
    <!-- General Settings -->
    <div id="general" class="tab-content">
        <div class="form-section">
            <h3>Site Identity</h3>
            <div class="form-group">
                <label class="form-label">Site Title</label>
                <input type="text" name="site_title" class="form-control" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Site Main Logo (.png, .jpg, .webp)</label>
                <div style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                    <?php if (!empty($settings['site_logo'])): ?>
                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                            <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Logo" style="height: 40px; display: block; margin-bottom: 5px; object-fit: contain;">
                            <small style="color: #888;">Current</small>
                        </div>
                    <?php
else: ?>
                        <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                            <img src="../assets/logo/logo.png" alt="Logo" style="height: 40px; display: block; margin-bottom: 5px; object-fit: contain;">
                            <small style="color: #888;">Default</small>
                        </div>
                    <?php
endif; ?>
                    <div style="flex-grow: 1;">
                        <input type="file" name="site_logo" class="form-control" accept=".png,.ico,.jpg,.jpeg,.webp">
                        <small style="color: #666; display: block; margin-top: 5px;">Recommended format: Transparent PNG. Used in Header.</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Site Favicon (.png, .ico, .jpg)</label>
                <div style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                    <?php if (!empty($settings['site_favicon'])): ?>
                        <div style="text-align: center;">
                            <img src="../<?php echo htmlspecialchars($settings['site_favicon']); ?>" alt="Favicon" style="width: 32px; height: 32px; display: block; margin-bottom: 5px; object-fit: contain;">
                            <small style="color: #888;">Current</small>
                        </div>
                    <?php
endif; ?>
                    <div style="flex-grow: 1;">
                        <input type="file" name="site_favicon" class="form-control" accept=".png,.ico,.jpg,.jpeg,.webp">
                        <small style="color: #666; display: block; margin-top: 5px;">Recommended size: 32x32px or 16x16px.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Settings (Password Change) -->
    <div id="account" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Change Password</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Required to change password" autocomplete="new-password">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" autocomplete="new-password">
                </div>
            </div>
            <small style="color: #666; display: block; margin-top: 10px;">Leave these fields blank if you do not wish to change your password.</small>
        </div>
    </div>

    <!-- SEO Settings -->
    <div id="seo" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Global SEO Settings</h3>
            <div class="form-group">
                <label class="form-label">Global Meta Description</label>
                <textarea name="seo_meta_description" class="form-control" rows="3" placeholder="A brief summary of your website for search engines (approx. 150-160 characters)."><?php echo htmlspecialchars($settings['seo_meta_description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Global Meta Keywords</label>
                <input type="text" name="seo_meta_keywords" class="form-control" value="<?php echo htmlspecialchars($settings['seo_meta_keywords'] ?? ''); ?>" placeholder="keyword1, keyword2, keyword3">
            </div>
            <div class="form-group">
                <label class="form-label">Canonical URL / Base URL</label>
                <input type="url" name="site_base_url" class="form-control" value="<?php echo htmlspecialchars($settings['site_base_url'] ?? ''); ?>" placeholder="https://www.gpslankatravels.com">
                <small style="color: #666;">Important for SEO to prevent duplicate content issues. Include http:// or https://</small>
            </div>
            <div class="form-group">
                <label class="form-label">Social Share Image URL (OG Image)</label>
                <input type="text" name="seo_og_image" class="form-control" value="<?php echo htmlspecialchars($settings['seo_og_image'] ?? ''); ?>" placeholder="assets/images/og-image.jpg">
                <small style="color: #666;">Default image displayed when your website link is shared on social media (size: 1200x630px recommended).</small>
            </div>
        </div>
    </div>
    <!-- Contact Settings -->
    <div id="contact" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Contact Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
               <label class="form-label">WhatsApp Number</label>
               <input type="text" name="contact_whatsapp" class="form-control" value="<?php echo htmlspecialchars($settings['contact_whatsapp'] ?? ''); ?>" placeholder="+94 XX XXX XXXX">
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="contact_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Google Map Embed URL (src)</label>
                <input type="text" name="contact_map_url" class="form-control" value="<?php echo htmlspecialchars($settings['contact_map_url'] ?? ''); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                <small style="display:block; color:#888; margin-top:5px; font-size: 12px;">Paste the URL from the 'src' attribute of the Google Maps Embed code.</small>
            </div>
        </div>
    </div>

    <!-- SMTP Settings -->
    <div id="smtp" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>SMTP Email Settings (For Forms)</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">SMTP Port</label>
                    <input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Sender Name (From Name)</label>
                    <input type="text" name="email_from_name" class="form-control" value="<?php echo htmlspecialchars($settings['email_from_name'] ?? 'GPS Lanka Travels'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Send Form Notifications To (Email)</label>
                    <input type="email" name="notification_email" class="form-control" value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>" placeholder="admin@example.com">
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media -->
    <div id="social" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Social Media Links (Leave empty to hide)</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Facebook URL</label>
                    <input type="text" name="social_facebook" class="form-control" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram URL</label>
                    <input type="text" name="social_instagram" class="form-control" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
                </div>

                <div class="form-group">
                     <label class="form-label">TikTok URL</label>
                    <input type="text" name="social_tiktok" class="form-control" value="<?php echo htmlspecialchars($settings['social_tiktok'] ?? ''); ?>" placeholder="https://tiktok.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label">Google Reviews URL</label>
                    <input type="text" name="social_google" class="form-control" value="<?php echo htmlspecialchars($settings['social_google'] ?? ''); ?>" placeholder="https://g.page/...">
                </div>
                 <div class="form-group">
                    <label class="form-label">LinkedIn URL</label>
                    <input type="text" name="social_linkedin" class="form-control" value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/...">
                </div>
                 <div class="form-group">
                    <label class="form-label">Trustpilot URL</label>
                    <input type="text" name="social_trustpilot" class="form-control" value="<?php echo htmlspecialchars($settings['social_trustpilot'] ?? ''); ?>" placeholder="https://trustpilot.com/...">
                </div>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div id="security" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Security & Bot Protection</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Cloudflare Turnstile Site Key</label>
                    <input type="text" name="cf_site_key" class="form-control" value="<?php echo htmlspecialchars($settings['cf_site_key'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Cloudflare Turnstile Secret Key</label>
                    <input type="text" name="cf_secret_key" class="form-control" value="<?php echo htmlspecialchars($settings['cf_secret_key'] ?? ''); ?>">
                </div>
            </div>
            <small style="color: #666; display: block; margin-top: 10px;">Cloudflare Turnstile is a smart, privacy-friendly alternative to CAPTCHA. Get your keys from the Cloudflare Dashboard.</small>
        </div>
    </div>

    <!-- Maintenance Mode Settings -->
    <div id="maintenance" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Maintenance & Coming Soon Mode</h3>
            <div class="form-group">
                <label class="form-label">Enable Maintenance Mode</label>
                <select name="maintenance_mode" class="form-control" style="padding: 10px;">
                    <option value="0" <?php echo($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>OFF (Live)</option>
                    <option value="1" <?php echo($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>ON (Maintenance/Coming Soon)</option>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;">When ON, visitors will be redirected to the "Coming Soon" page. Logged-in admins can still see the live site.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Page Title</label>
                <input type="text" name="maintenance_title" class="form-control" value="<?php echo htmlspecialchars($settings['maintenance_title'] ?? 'Something Exciting is Coming!'); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Coming Soon Message</label>
                <textarea name="maintenance_message" class="form-control" rows="4"><?php echo htmlspecialchars($settings['maintenance_message'] ?? 'We are currently working hard to bring you the best experience of Sri Lankan travel. Stay tuned!'); ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Expected Launch Date</label>
                    <input type="date" name="maintenance_launch_date" class="form-control" value="<?php echo htmlspecialchars($settings['maintenance_launch_date'] ?? ''); ?>">
                    <small style="color: #666;">This will show a countdown on the coming soon page (if date is in future).</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Background Image URL</label>
                    <input type="text" name="maintenance_bg_image" class="form-control" value="<?php echo htmlspecialchars($settings['maintenance_bg_image'] ?? 'assets/hero.webp'); ?>">
                    <small style="color: #666;">URL to background image (e.g. assets/hero.webp).</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Move Footer Settings to General Tab -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const generalTab = document.getElementById('general');
            const footerSection = document.getElementById('footer-settings-section');
            if(footerSection) generalTab.appendChild(footerSection);
        });
    </script>

    <!-- Homepage About Settings -->
    <div id="about-home" class="tab-content" style="display: none;">
        <div class="form-section">
            <h3>Homepage "About Us" Section</h3>
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="about_title" class="form-control" value="<?php echo htmlspecialchars($settings['about_title'] ?? 'Magical Memories,<br>Bespoke experiences'); ?>">
                <small style="color: #666;">Use <code>&lt;br&gt;</code> to create a line break.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description (Paragraphs)</label>
                <textarea name="about_description" id="about_description" class="form-control" rows="8"><?php echo htmlspecialchars($settings['about_description'] ?? "Welcome to GPS Lanka Travels, your key gateway to great Sri Lankan travel adventures. We are a trusted travel firm devoted to showing the very best of this island. From golden shores and misty hill country to ancient cities and wild nature, we craft great journeys capturing the true spirit, culture, and beauty of Sri Lanka. Built on a passion for travel excellence and personal care, we specialize in shaping seamless, highly memorable trips.\n\nWith deep local insight and strong industry ties, we offer bespoke vacations, heritage tours, and scenic routes. Whether seeking wildlife trips or quiet beach holidays, our reliable service standards and select partners ensure perfection. We believe travel must be fully meaningful, very comfortable, and highly enriching. Each custom itinerary we design reflects our solid commitment to quality, gentle care, and total authenticity. Discover Sri Lanka, experience absolute paradise, and travel with GPS Lanka Travels as your top travel partner today."); ?></textarea>
                <small style="color: #666;">You can use the editor or plain text. In the frontend, paragraphs will be styled safely.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Side Image (.png, .jpg, .webp)</label>
                <div style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                    <?php if (!empty($settings['about_image'])): ?>
                        <div style="text-align: center;">
                            <img src="../<?php echo htmlspecialchars($settings['about_image']); ?>" alt="About image" style="width: 150px; height: auto; display: block; margin-bottom: 5px; object-fit: cover; border-radius: 8px;">
                            <small style="color: #888;">Current</small>
                        </div>
                    <?php
else: ?>
                        <div style="text-align: center;">
                            <img src="../assets/images/about/about-home.png" alt="About image" style="width: 150px; height: auto; display: block; margin-bottom: 5px; object-fit: cover; border-radius: 8px;">
                            <small style="color: #888;">Default</small>
                        </div>
                    <?php
endif; ?>
                    <div style="flex-grow: 1;">
                        <input type="file" name="about_image" class="form-control" accept=".png,.jpg,.jpeg,.webp">
                        <small style="color: #666; display: block; margin-top: 5px;">Recommended orientation: Vertical/Portrait.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="footer-settings-section" class="form-section">
        <h3>Footer Settings</h3>
        <div class="form-group">
            <label class="form-label">Footer Logo (.png, .jpg, .webp)</label>
            <div style="display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
                <?php if (!empty($settings['footer_logo'])): ?>
                    <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                        <img src="../<?php echo htmlspecialchars($settings['footer_logo']); ?>" alt="Footer Logo" style="height: 40px; display: block; margin-bottom: 5px; object-fit: contain;">
                        <small style="color: #888;">Current</small>
                    </div>
                <?php
elseif (!empty($settings['site_logo'])): ?>
                    <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                        <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Site Logo" style="height: 40px; display: block; margin-bottom: 5px; object-fit: contain;">
                        <small style="color: #888;">Using Main Site Logo</small>
                    </div>
                <?php
else: ?>
                    <div style="text-align: center; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                        <img src="../assets/logo/logo.png" alt="Logo" style="height: 40px; display: block; margin-bottom: 5px; object-fit: contain;">
                        <small style="color: #888;">Default</small>
                    </div>
                <?php
endif; ?>
                <div style="flex-grow: 1;">
                    <input type="file" name="footer_logo" class="form-control" accept=".png,.ico,.jpg,.jpeg,.webp">
                    <small style="color: #666; display: block; margin-top: 5px;">Optional. If not set, the Main Site Logo will be used in the footer.</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Footer About Text</label>
            <textarea name="footer_about_text" class="form-control" rows="3"><?php echo htmlspecialchars($settings['footer_about_text'] ?? 'Curating timeless Sri Lankan escapes with culture, comfort, and class.'); ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Footer Copyright Text</label>
            <input type="text" name="footer_copyright_text" class="form-control" value="<?php echo htmlspecialchars($settings['footer_copyright_text'] ?? '&copy; ' . date('Y') . ' GPS Lanka Travels. All Rights Reserved.'); ?>">
            <small style="color: #666;">You can use HTML entities like &amp;copy; for standard symbols.</small>
        </div>
    </div>

    
    <div style="position: sticky; bottom: 0; background: #fff; padding: 20px; border-top: 1px solid #ddd; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); border-radius: 0 0 8px 8px; margin: 0 -20px -20px -20px; z-index: 10;">
        <button type="submit" class="btn" style="width: 100%; font-size: 16px; padding: 12px; font-weight: 600;">
           <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</form>

<style>
    .tab-btn {
        border-bottom: 3px solid transparent !important;
    }
    .tab-btn.active {
        color: #00bcd4 !important;
        border-bottom: 3px solid #00bcd4 !important;
        background: #f0f8ff !important;
    }
    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
    
    // Save active tab in localStorage
    localStorage.setItem('activeSettingsTab', tabName);
}

// Restore active tab
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        const tabBtn = Array.from(document.getElementsByClassName('tab-btn')).find(btn => btn.getAttribute('onclick').includes(activeTab));
        if (tabBtn) {
            tabBtn.click();
        } else {
            document.querySelector('.tab-btn').click();
        }
    } else {
        document.querySelector('.tab-btn').click();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
