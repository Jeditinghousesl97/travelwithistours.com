<?php
require_once 'config/db.php';

// Fetch Settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Check if maintenance mode is actually on. If not, redirect home.
if (($settings['maintenance_mode'] ?? '0') == '0') {
    header("Location: index.php");
    exit;
}

$title = $settings['maintenance_title'] ?? 'Something Exciting is Coming!';
$message = $settings['maintenance_message'] ?? 'We are currently working hard to bring you the best experience of Sri Lankan travel. Stay tuned!';
$launch_date = $settings['maintenance_launch_date'] ?? '';
$bg_image = $settings['maintenance_bg_image'] ?? 'assets/hero.webp';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon - <?php echo htmlspecialchars($settings['site_title'] ?? 'Travel with IS Tours'); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <?php $favicon_path = !empty($settings['site_favicon']) ? $settings['site_favicon'] : 'assets/images/logo/favicon.png'; ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <style>
        :root {
            --primary: #00bcd4;
            --accent: #ff1a4a;
            --dark: #1a1a1a;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark);
            color: var(--white);
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%), url('<?php echo $bg_image; ?>');
            background-size: cover;
            background-position: center;
            z-index: -1;
            filter: contrast(1.1);
            animation: zoomBg 20s infinite alternate;
        }

        @keyframes zoomBg {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }

        .container {
            max-width: 900px;
            padding: 40px;
            text-align: center;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo img {
            height: 100px;
            width: auto;
        }

        h1 {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        p {
            font-size: clamp(16px, 2vw, 20px);
            color: rgba(255,255,255,0.8);
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        /* Countdown */
        .countdown {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .countdown-item {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 20px;
            min-width: 100px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }

        .countdown-item:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .countdown-number {
            font-size: 32px;
            font-weight: 800;
            display: block;
            color: var(--primary);
        }

        .countdown-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.5);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .social-links a {
            color: white;
            font-size: 24px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transition: 0.3s;
            text-decoration: none;
        }

        .social-links a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-5px) rotate(10deg);
        }

        .contact-info {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: rgba(255,255,255,0.6);
        }

        .contact-item i {
            color: var(--primary);
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
                border-radius: 0;
                background: transparent;
                backdrop-filter: none;
                border: none;
                box-shadow: none;
            }
            .countdown {
                gap: 10px;
            }
            .countdown-item {
                min-width: 70px;
                padding: 15px 10px;
            }
            .countdown-number {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="bg-overlay"></div>

<div class="container">
    <div class="logo">
        <img src="assets/images/logo/logo.png" alt="Travel with IS Tours">
    </div>

    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo htmlspecialchars($message); ?></p>

    <?php if ($launch_date && strtotime($launch_date) > time()): ?>
    <div class="countdown" id="countdown">
        <div class="countdown-item">
            <span class="countdown-number" id="days">00</span>
            <span class="countdown-label">Days</span>
        </div>
        <div class="countdown-item">
            <span class="countdown-number" id="hours">00</span>
            <span class="countdown-label">Hours</span>
        </div>
        <div class="countdown-item">
            <span class="countdown-number" id="minutes">00</span>
            <span class="countdown-label">Mins</span>
        </div>
        <div class="countdown-item">
            <span class="countdown-number" id="seconds">00</span>
            <span class="countdown-label">Secs</span>
        </div>
    </div>
    
    <script>
        const launchDate = new Date("<?php echo $launch_date; ?>").getTime();
        
        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = launchDate - now;
            
            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdown").style.display = "none";
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("days").innerText = days.toString().padStart(2, '0');
            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');
        }, 1000);
    </script>
    <?php
endif; ?>

    <div class="social-links">
        <?php if (!empty($settings['social_facebook'])): ?>
            <a href="<?php echo $settings['social_facebook']; ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
        <?php
endif; ?>
        <?php if (!empty($settings['social_instagram'])): ?>
            <a href="<?php echo $settings['social_instagram']; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
        <?php
endif; ?>
        <?php if (!empty($settings['social_tiktok'])): ?>
            <a href="<?php echo $settings['social_tiktok']; ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
        <?php
endif; ?>
        <?php if (!empty($settings['social_whatsapp'])): ?>
             <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['contact_whatsapp']); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
        <?php
endif; ?>
    </div>

    <div class="contact-info">
        <?php if (!empty($settings['contact_email'])): ?>
            <div class="contact-item">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['contact_email']); ?>
            </div>
        <?php
endif; ?>
        <?php if (!empty($settings['contact_phone'])): ?>
            <div class="contact-item">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['contact_phone']); ?>
            </div>
        <?php
endif; ?>
    </div>
</div>

</body>
</html>
