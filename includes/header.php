<?php
// Ensure DB connection is available
if (!isset($pdo)) {
    // Fallback if not included by parent
    $db_path = __DIR__ . '/../config/db.php';
    if (file_exists($db_path))
        require_once $db_path;
}

// Fetch Settings
$h_settings = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $h_settings[$row['setting_key']] = $row['setting_value'];
        }

        // Maintenance Mode Logic
        if (($h_settings['maintenance_mode'] ?? '0') == '1') {
            // Start session to check for admin
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $current_page = basename($_SERVER['PHP_SELF']);
            $is_admin = isset($_SESSION['user_id']); // Simple check for logged in admin

            if ($current_page != 'maintenance.php' && !$is_admin) {
                header("Location: maintenance.php");
                exit;
            }
        }

    }
    catch (PDOException $e) {
    // Silent fail or log
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic SEO Meta Tags -->
    <?php
$base_url = $h_settings['site_base_url'] ?? '';
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Page specific meta defaults
$meta_title = isset($page_title) ? $page_title . " - " . ($h_settings['site_title'] ?? 'GPS Lanka Travels') : ($h_settings['site_title'] ?? 'GPS Lanka Travels');
$meta_desc = $page_description ?? ($h_settings['seo_meta_description'] ?? 'GPS Lanka Travels – Curating timeless Sri Lankan escapes with culture, comfort, and class.');
$meta_key = $page_keywords ?? ($h_settings['seo_meta_keywords'] ?? '');
$og_img = $page_og_image ?? ($h_settings['seo_og_image'] ?? 'assets/images/og-image.jpg');

// Ensure OG image has full path
if ($og_img && !filter_var($og_img, FILTER_VALIDATE_URL)) {
    $og_img = rtrim($base_url, '/') . '/' . ltrim($og_img, '/');
}
?>
    <title><?php echo htmlspecialchars($meta_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <?php if ($meta_key): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_key); ?>">
    <?php
endif; ?>

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_img); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($og_img); ?>">

    <!-- Favicon -->
    <?php $favicon_path = !empty($h_settings['site_favicon']) ? $h_settings['site_favicon'] : 'assets/images/logo/favicon.png'; ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Header Specific Styles */
        .top-bar {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 0px 0 0 0;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }
        
        .top-bar .left {
            display: flex;
            align-items: center;
            gap: 20px;
            justify-content: flex-start;
        }
        
        .top-bar .center {
            text-align: center;
        }
        
         .top-bar .center img {
            height: 100px; /* Increased Size */
            width: auto;
            display: block;
            margin: 0 auto;
            transition: transform 0.3s;
        }
        
         .top-bar .center img:hover {
            transform: scale(1.05);
        }

        .top-bar .right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            text-align: right;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
            font-weight: 500;
        }

        .btn-enquire {
            background-color: #ff1a4a; /* Red/Pink Accent */
            color: #fff;
            padding: 10px 25px;
            border-radius: 50px;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: 0.3s;
            box-shadow: 0 4px 10px rgba(255, 26, 74, 0.2);
        }

        .btn-enquire:hover {
            background-color: #d9163e;
            transform: translateY(-2px);
        }

        /* Navigation */
        .nav-bar {
            display: flex;
            justify-content: center;
            padding: 0;
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links > li > a {
            display: block;
            padding: 20px 0;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
            color: #333;
            letter-spacing: 0.5px;
            border-bottom: 2px solid transparent;
        }

        .nav-links > li > a:hover {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
        }

        /* Dropdown */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            min-width: 250px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px; /* Rounded corners for modern look */
            padding: 10px;
            z-index: 1000;
            border-top: 3px solid var(--accent-color);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
            opacity: 1;
        }

        .dropdown-menu li a {
            padding: 12px 20px;
            font-size: 14px;
            color: #555;
            text-transform: none;
            font-weight: 500;
            border-radius: 5px;
            transition: background 0.2s;
        }

        .dropdown-menu li a:hover {
            background: #f4f8fb;
            color: var(--accent-color);
            padding-left: 25px; /* Slight shift effect */
        }
        
        /* Mobile Responsive */
        .menu-toggle {
            display: none;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            background: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .menu-toggle:hover {
            color: var(--accent-color);
            background: #f0f0f0;
        }

        @media (max-width: 992px) {
            .top-bar {
                grid-template-columns: 1fr;
                gap: 15px;
                text-align: center;
                padding-bottom: 15px;
            }
            .top-bar .left {
                justify-content: center;
                flex-wrap: wrap;
                width: 100%;
            }
            /* 01. Hide Email & Inquiry Button */
            .top-bar .right {
                display: none;
            }
            
            .menu-toggle {
                display: flex; /* Show hamburger on mobile */
                align-items: center;
                justify-content: center;
                position: fixed; /* Sticky on page scrolling */
                top: 55px; /* Increased top padding (20px + 35px) */
                left: 20px;
                background: #fff; /* White background */
                padding: 10px;
                border-radius: 50%; /* Fully rounded/Circle */
                width: 45px;
                height: 45px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.15);
                z-index: 9999; /* Ensure on top of everything */
                border: none;
                color: #333;
            }

            .nav-bar {
                background: transparent; /* Allow header gradient to show */
                box-shadow: none;
            }

            .nav-links {
                display: none; /* Hide links by default */
                position: fixed; /* Stop scrolling with page */
                top: 110px;
                left: 20px;
                width: calc(100% - 40px);
                background: #fff;
                padding: 30px; /* 30px padding */
                border-radius: 15px; /* Corner radius */
                flex-direction: column;
                align-items: flex-start; /* Left align items */
                gap: 0;
                box-shadow: 0 5px 30px rgba(0,0,0,0.15);
                z-index: 9998;
                max-height: 70vh;
                overflow-y: auto;
            }
            
            .nav-links.active {
                display: flex; /* Show when active */
                animation: slideDown 0.3s ease-out forwards;
            }
            
            .nav-links > li {
                width: 100%;
                text-align: left; /* Left align text */
                border-bottom: 1px solid #f5f5f5;
            }

            .nav-links > li > a {
                 padding: 15px 0;
                 display: block;
                 width: 100%;
            }

            .dropdown-menu {
                position: static;
                transform: none;
                box-shadow: none;
                border: 1px solid #eee;
                display: none; 
                width: 100%;
                text-align: left; /* Left align text */
            }
            
            /* Animation */
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
        
        #google_translate_element select {
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            font-size: 12px;
        }
        /* Language Switcher */
        .lang-switcher {
            position: relative;
            display: inline-block;
        }

        #lang-btn {
            background: #fff;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        #lang-btn:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        #lang-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #fff;
            min-width: 160px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 5px;
            padding: 5px 0;
            z-index: 1001;
            margin-top: 5px;
            border: 1px solid #eee;
        }

        #lang-menu li {
            padding: 8px 15px;
            font-size: 13px;
            color: #555;
            cursor: pointer;
            transition: background 0.2s;
            list-style: none;
        }

        #lang-menu li:hover {
            background: #f9f9f9;
            color: var(--accent-color);
        }

        .lang-switcher.active #lang-menu {
            display: block;
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="container">
        <!-- Top Bar -->
        <div class="top-bar">
            <!-- Left: Language & Whatsapp -->
            <div class="left">
                <!-- Custom Language Switcher -->
                <div class="lang-switcher" id="custom-lang-switcher">
                    <button id="lang-btn">
                        <i class="fas fa-globe"></i> <span id="current-lang">Language</span> <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                    </button>
                    <ul id="lang-menu">
                        <li data-lang="en">English</li>
                        <li data-lang="de">Deutsch</li>
                        <li data-lang="fr">Français</li>
                        <li data-lang="nl">Nederlands</li>
                        <li data-lang="it">Italiano</li>
                        <li data-lang="es">Español</li>
                        <li data-lang="ru">Русский</li>
                        <li data-lang="zh-CN">中文</li>
                    </ul>
                </div>
                <!-- Hidden Google Element -->
                <div id="google_translate_element" style="display:none;"></div>
                <?php if (!empty($h_settings['contact_whatsapp'])): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $h_settings['contact_whatsapp']); ?>" class="contact-item" target="_blank">
                        <i class="fab fa-whatsapp" style="color: #25D366; font-size: 18px;"></i> <?php echo htmlspecialchars($h_settings['contact_whatsapp']); ?>
                    </a>
                <?php
endif; ?>
            </div>

            <!-- Center: Logo -->
            <div class="center">
                <a href="index.php">
                    <?php $logo_path = !empty($h_settings['site_logo']) ? $h_settings['site_logo'] : 'assets/logo/logo.png'; ?>
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($h_settings['site_title'] ?? 'GPS Lanka Travels'); ?>">
                </a>
            </div>

            <!-- Right: Email & Enquiry -->
            <div class="right">
                <?php if (!empty($h_settings['contact_email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($h_settings['contact_email']); ?>" class="contact-item">
                        <i class="far fa-envelope" style="color: #666; font-size: 16px;"></i> <?php echo htmlspecialchars($h_settings['contact_email']); ?>
                    </a>
                <?php
endif; ?>
                <a href="booking-inquiry.php" class="btn-enquire">Enquire Now</a>
            </div>
        </div>

        <!-- Navigation Bar -->
        <div class="nav-bar">
            <!-- Mobile Menu Toggle -->
            <div class="menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <nav>
                <ul class="nav-links" id="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="all-services.php">What we Offer</a></li>
                    
                    <li><a href="all-tours.php">All Tour Packages</a></li>

                    <li><a href="gallery.php">Gallery & Testimonials</a></li>
                    <li><a href="destinations.php">Destinations</a></li>
                    <li><a href="contact.php">Let's Connect</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
      pageLanguage: 'en', 
      includedLanguages: 'en,de,fr,nl,it,es,ru,zh-CN', 
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE, 
      autoDisplay: false
  }, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const langSwitcher = document.getElementById('custom-lang-switcher');
        const langBtn = document.getElementById('lang-btn');
        const langMenu = document.getElementById('lang-menu');
        const currentLangSpan = document.getElementById('current-lang');
        
        // Map codes to names for persistence
        const langMap = {
            'en': 'English',
            'de': 'Deutsch',
            'fr': 'Français',
            'nl': 'Nederlands',
            'it': 'Italiano',
            'es': 'Español',
            'ru': 'Русский',
            'zh-CN': '中文'
        };

        // 1. Toggle Menu
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            langSwitcher.classList.toggle('active');
        });

        // 2. Close when clicking outside
        document.addEventListener('click', function() {
            langSwitcher.classList.remove('active');
        });

        // 3. Handle Language Selection
        const langItems = langMenu.querySelectorAll('li');
        langItems.forEach(item => {
            item.addEventListener('click', function() {
                const langCode = this.getAttribute('data-lang');
                const langName = this.textContent;

                // Update UI Immediately
                currentLangSpan.textContent = langName;
                
                // Set Cookie for Persistence (Crucial for Google Translate)
                setCookie('googtrans', '/en/' + langCode, 1); // 1 day
                setCookie('googtrans', '/en/' + langCode, 1, '/', '.gpslankatravels.com'); // Domain wide
                
                // Trigger Google Translate Widget
                const googleSelect = document.querySelector('.goog-te-combo');
                if (googleSelect) {
                    googleSelect.value = langCode;
                    googleSelect.dispatchEvent(new Event('change'));
                } else {
                    // Fallback: Reload page to apply cookie
                    location.reload(); 
                }
            });
        });
        
        // 4. Check Cookie on Load & Update Text
        const currentCookie = getCookie('googtrans');
        if (currentCookie) {
            const code = currentCookie.split('/').pop();
            if (langMap[code]) {
                currentLangSpan.textContent = langMap[code];
            }
        }

        // --- Helper Functions ---
        function setCookie(name, value, days, path = '/', domain) {
            let expires = "";
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            let cookie = name + "=" + (value || "") + expires + "; path=" + path;
            if (domain) cookie += "; domain=" + domain;
            document.cookie = cookie;
        }

        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for(let i=0;i < ca.length;i++) {
                let c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        
        // --- Mobile Menu Toggle ---
        const menuToggle = document.getElementById('mobile-menu-toggle');
        const navLinks = document.getElementById('nav-links');
        
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                
                // Toggle Icon (Optional)
                const icon = menuToggle.querySelector('i');
                if (navLinks.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
    });
</script>
