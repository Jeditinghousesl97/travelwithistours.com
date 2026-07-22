<?php include 'includes/header.php'; ?>
<?php require_once 'includes/tripadvisor_reviews.php'; ?>
<?php require_once 'includes/short_videos.php'; ?>

<!-- Hero Slider Section -->
<?php
require_once 'config/db.php';
ensure_tripadvisor_reviews_schema($pdo);
ensure_short_videos_schema($pdo);
$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY display_order ASC")->fetchAll();

function get_youtube_embed_url(?string $url): ?string
{
    if (!$url) {
        return null;
    }

    $trimmed = trim($url);
    $parts = parse_url($trimmed);
    $host = strtolower($parts['host'] ?? '');

    if (str_contains($host, 'youtu.be')) {
        $video_id = trim($parts['path'] ?? '', '/');
    } else {
        parse_str($parts['query'] ?? '', $query);
        $video_id = $query['v'] ?? '';

        if (!$video_id && !empty($parts['path'])) {
            $path_parts = array_values(array_filter(explode('/', trim($parts['path'], '/'))));
            $embed_index = array_search('embed', $path_parts, true);
            if ($embed_index !== false && isset($path_parts[$embed_index + 1])) {
                $video_id = $path_parts[$embed_index + 1];
            }
        }
    }

    if (!$video_id) {
        return null;
    }

    $params = http_build_query([
        'autoplay' => 1,
        'mute' => 1,
        'controls' => 0,
        'loop' => 1,
        'playlist' => $video_id,
        'playsinline' => 1,
        'rel' => 0,
        'modestbranding' => 1,
        'enablejsapi' => 1,
    ]);

    return "https://www.youtube.com/embed/" . rawurlencode($video_id) . "?" . $params;
}

function get_embed_media_url(?string $url): ?string
{
    if (!$url) {
        return null;
    }

    $trimmed = trim($url);
    $parts = parse_url($trimmed);

    if ($parts === false) {
        return $trimmed;
    }

    $host = strtolower($parts['host'] ?? '');
    $existing_query = [];
    parse_str($parts['query'] ?? '', $existing_query);

    $provider_params = [
        'autoplay' => '1',
        'loop' => '1',
        'muted' => '1',
        'playsinline' => '1',
        'preload' => 'auto',
    ];

    if (str_contains($host, 'cloudflarestream.com')) {
        $provider_params = [
            'autoplay' => 'true',
            'loop' => 'true',
            'muted' => 'true',
            'preload' => 'auto',
            'controls' => 'false',
        ];
    } elseif (str_contains($host, 'vimeo.com')) {
        $provider_params = [
            'autoplay' => '1',
            'loop' => '1',
            'muted' => '1',
            'background' => '1',
            'autopause' => '0',
            'preload' => 'auto',
        ];
    }

    $query = http_build_query(array_merge($existing_query, $provider_params));
    $rebuilt_url = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

    if (!empty($parts['port'])) {
        $rebuilt_url .= ':' . $parts['port'];
    }

    $rebuilt_url .= $parts['path'] ?? '';

    if ($query !== '') {
        $rebuilt_url .= '?' . $query;
    }

    if (!empty($parts['fragment'])) {
        $rebuilt_url .= '#' . $parts['fragment'];
    }

    return $rebuilt_url;
}
?>

<section class="hero-slider-container">
    <?php if (count($slides) > 0): ?>
        <?php foreach ($slides as $index => $slide): ?>
        <?php
        $slide_type = $slide['slide_type'] ?? 'image';
        $is_youtube = $slide_type === 'youtube';
        $is_video = $slide_type === 'video';
        $is_embed = $slide_type === 'embed';
        $is_initial_slide = $index === 0;
        $background_style = !empty($slide['image_path']) ? "background-image: url('" . htmlspecialchars($slide['image_path']) . "');" : '';
        $youtube_embed_url = $is_youtube ? get_youtube_embed_url($slide['video_url'] ?? '') : null;
        $generic_embed_url = $is_embed ? get_embed_media_url($slide['video_url'] ?? '') : null;
        ?>
        <div class="hero-slide <?php echo($index === 0) ? 'active' : ''; ?>" style="<?php echo $background_style; ?>">
            <?php if ($is_youtube && $youtube_embed_url): ?>
                <div class="hero-slide-media">
                    <iframe
                        class="hero-slide-iframe"
                        <?php echo $is_initial_slide ? 'src="' . htmlspecialchars($youtube_embed_url) . '"' : ''; ?>
                        data-src="<?php echo htmlspecialchars($youtube_embed_url); ?>"
                        title="<?php echo htmlspecialchars($slide['title'] ?: 'Hero video slide'); ?>"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen"
                        loading="<?php echo $is_initial_slide ? 'eager' : 'lazy'; ?>"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            <?php elseif ($is_video && !empty($slide['video_url'])): ?>
                <div class="hero-slide-media">
                    <video
                        class="hero-slide-video"
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        <?php echo !empty($slide['image_path']) ? 'poster="' . htmlspecialchars($slide['image_path']) . '"' : ''; ?>>
                        <source src="<?php echo htmlspecialchars($slide['video_url']); ?>">
                    </video>
                </div>
            <?php elseif ($is_embed && $generic_embed_url): ?>
                <div class="hero-slide-media hero-slide-media-embed">
                    <iframe
                        class="hero-slide-iframe"
                        <?php echo $is_initial_slide ? 'src="' . htmlspecialchars($generic_embed_url) . '"' : ''; ?>
                        data-src="<?php echo htmlspecialchars($generic_embed_url); ?>"
                        title="<?php echo htmlspecialchars($slide['title'] ?: 'Hero embed slide'); ?>"
                        allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen"
                        loading="<?php echo $is_initial_slide ? 'eager' : 'lazy'; ?>"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                </div>
            <?php endif; ?>

            <div class="container hero-content">
                <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                <?php if ($slide['subtitle']): ?>
                <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                <?php
        endif; ?>
                <?php if ($slide['button_text']): ?>
                <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="hero-btn"><?php echo htmlspecialchars($slide['button_text']); ?></a>
                <?php
        endif; ?>
            </div>
        </div>
        <?php
    endforeach; ?>

        <!-- Arrows -->
        <div class="slider-arrow prev-arrow" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></div>
        <div class="slider-arrow next-arrow" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></div>
        
        <!-- Dots -->
        <div class="slider-nav">
             <?php foreach ($slides as $index => $slide): ?>
                <div class="slider-dot <?php echo($index === 0) ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
             <?php
    endforeach; ?>
        </div>

    <?php
else: ?>
        <!-- Fallback Static Slide if DB is empty -->
        <div class="hero-slide active" style="background-image: url('https://placehold.co/1920x800?text=Please+Add+Slides+in+Admin');">
            <div class="container hero-content">
                <h1>Welcome to Travel with IS Tours</h1>
                <p>Please login to admin panel and add hero slides.</p>
                <a href="admin/" class="hero-btn">Go to Admin</a>
            </div>
        </div>
    <?php
endif; ?>
</section>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');
    const totalSlides = slides.length;
    let slideInterval;

    function showSlide(index) {
        if (index >= totalSlides) currentSlide = 0;
        else if (index < 0) currentSlide = totalSlides - 1;
        else currentSlide = index;

        // Remove active class
        slides.forEach(slide => {
            slide.classList.remove('active');
            syncSlideMedia(slide, false);
        });
        dots.forEach(dot => dot.classList.remove('active'));

        // Add active class
        slides[currentSlide].classList.add('active');
        syncSlideMedia(slides[currentSlide], true);
        if(dots.length > 0) dots[currentSlide].classList.add('active');
    }

    function changeSlide(direction) {
        showSlide(currentSlide + direction);
        resetTimer();
    }

    function goToSlide(index) {
        showSlide(index);
        resetTimer();
    }

    function startTimer() {
        slideInterval = setInterval(() => {
            showSlide(currentSlide + 1);
        }, 5000); // Change slide every 5 seconds
    }

    function resetTimer() {
        clearInterval(slideInterval);
        startTimer();
    }

    function syncSlideMedia(slide, isActive) {
        if (!slide) return;

        const videos = slide.querySelectorAll('video');
        const iframes = slide.querySelectorAll('iframe[data-src]');

        videos.forEach(video => {
            if (isActive) {
                const playPromise = video.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(() => {});
                }
            } else {
                video.pause();
                video.currentTime = 0;
            }
        });

        iframes.forEach(iframe => {
            if (isActive) {
                if (!iframe.src) {
                    iframe.src = iframe.dataset.src;
                }
            } else {
                if (iframe.src) {
                    iframe.src = '';
                }
            }
        });
    }

    // Initialize
    if(totalSlides > 0) {
        syncSlideMedia(slides[currentSlide], true);
    }
    if(totalSlides > 1) {
        startTimer();
    }
</script>

<!-- About Us -->
<section id="about" class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <?php
$about_image_path = !empty($h_settings['about_image']) ? $h_settings['about_image'] : 'assets/images/about/about-home.png';
?>
                <img src="<?php echo htmlspecialchars($about_image_path); ?>" alt="<?php echo htmlspecialchars(strip_tags($h_settings['about_title'] ?? 'About Travel with IS Tours')); ?>">
            </div>
            <div class="about-content">
                <h2><?php echo !empty($h_settings['about_title']) ? nl2br(htmlspecialchars_decode($h_settings['about_title'])) : 'Magical Memories,<br>Bespoke experiences'; ?></h2>
                
                <?php
$about_desc = $h_settings['about_description'] ?? "Welcome to Travel with IS Tours, your gateway to unforgettable Sri Lankan travel experiences. We are a trusted local travel partner dedicated to showing you the very best of this island. From golden beaches and misty hill country to ancient cities and wildlife-rich landscapes, we craft journeys that reflect the spirit, culture, and beauty of Sri Lanka.\n\nWith deep local knowledge and personalized care, we create bespoke holidays, heritage journeys, scenic routes, and comfortable transport experiences tailored to each traveler. We believe travel should be meaningful, comfortable, and authentic. Every itinerary we design reflects our commitment to quality service, warm hospitality, and memorable adventures across Sri Lanka.";

// Split the description by newlines to wrap in paragraphs
$paragraphs = explode("\n", $about_desc);
foreach ($paragraphs as $p) {
    $p = trim($p);
    if (!empty($p)) {
        // Only allow basic formatting tags if they decide to use them (like <br>) but encode others, or just use nl2br depending on preference. 
        // Re-allow <br> in case they typed it. But simpler is just encoding everything.
        echo '<p>' . htmlspecialchars($p) . '</p>';
    }
}
?>
                
                <a href="booking-inquiry.php" class="btn-primary-red">PLAN YOUR TRIP TO SRI LANKA <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>




<!-- Our Services (Slider) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<section class="services-section" style="background-color: #ffffffff;">
    <div class="container" style="position: relative;">
        <!-- New Heading Style -->
        <!-- New Heading Style -->
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-family: var(--hero-font); font-size: 40px; margin-bottom: 10px;">What we Offer</h2>
            <p style="max-width: 700px; margin: 0 auto; color: #666; font-size: 16px;">We provide a wide range of services to ensure your travel experience in Sri Lanka is nothing short of perfection.</p>
        </div>
        
        <!-- Swiper Container with Padding for Arrows -->
        <div class="swiper-wrapper-container" style="padding: 0 50px; position: relative;">
            <div class="swiper service-swiper">
                <div class="swiper-wrapper">
                    <?php
require_once 'config/db.php';
$services = $pdo->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();

if (count($services) > 0):
    foreach ($services as $svc):
        // Limit description to 130 chars
        $desc = $svc['short_description'];
        if (strlen($desc) > 130) {
            $desc = substr($desc, 0, 130) . '...';
        }
        // Default image
        $img = $svc['icon'] ? $svc['icon'] : 'https://placehold.co/400x300?text=Service';
?>
                    <div class="swiper-slide service-card">
                        <div class="service-image" style="background-image: url('<?php echo htmlspecialchars($img); ?>');"></div>
                        <div class="service-content">
                            <h3><?php echo htmlspecialchars($svc['name']); ?></h3>
                            <p><?php echo htmlspecialchars($desc); ?></p>
                            <a href="service-details.php?id=<?php echo $svc['id']; ?>" class="read-more-btn">READ MORE <i class="fas fa-arrow-right" style="font-size: 10px;"></i></a>
                        </div>
                    </div>
                    <?php
    endforeach;
else:
?>
                        <div class="swiper-slide"><div style="text-align: center;">No services available.</div></div>
                    <?php
endif; ?>
                </div>
            </div>
            
            <!-- Swiper Navigation -->
            <div class="swiper-button-prev svc-swiper-arrow"></div>
            <div class="swiper-button-next svc-swiper-arrow"></div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding: 0 50px; flex-wrap: wrap; gap: 20px;">
            <p style="max-width: 600px; color: #555; font-size: 16px; margin: 0; text-align: left;">
                From airport transfers to personalized tour planning, our expert team is dedicated to handling every detail of your trip with care and professionalism.
            </p>
            <a href="all-services.php" class="btn" style="padding: 12px 30px; font-size: 16px; background-color: #000; color: #fff; border: 1px solid #000;">View All Services</a>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    const swiper = new Swiper('.service-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            },
        }
    });
</script>






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


<!-- Vision & Mission -->
<section class="section" style="padding: 60px 0; background-color: #f9f9f9;">
    <div class="container">
        <div style="display: flex; gap: 40px;">
            <div style="flex: 1; text-align: center;">
                <h3>Our Vision</h3>
                <p>To be the most trusted and preferred travel partner for luxury and experiential travel, setting the gold standard for high-end trips in Sri Lanka.</p>
            </div>
            <div style="flex: 1; text-align: center;">
                <h3>Our Mission</h3>
                <p>To provide exceptional, personalized travel services that create unforgettable memories for our guests while actively promoting sustainable growth.</p>
            </div>
        </div>
    </div>
</section>


<!-- Signature Tours -->
<section id="tours" class="section" style="padding: 80px 0; background-color: #f0f8ff;">
    <div class="container" style="position: relative;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-family: var(--hero-font); font-size: 40px; margin-bottom: 10px;">Our Tour Packages</h2>
            <p style="max-width: 700px; margin: 0 auto; color: #666; font-size: 16px;">Explore our meticulously crafted tour packages designed to give you the most authentic and memorable Sri Lankan experience.</p>
        </div>
        
        <!-- Swiper Container -->
        <div class="swiper-wrapper-container" style="padding: 0 50px; position: relative;">
            <div class="swiper signature-swiper">
                <div class="swiper-wrapper">
                    <?php
require_once 'config/db.php';
// Fetch All Tours, Ordered by Featured First
$sql = "SELECT t.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
        FROM tours t 
        LEFT JOIN tour_categories tc ON t.id = tc.tour_id 
        LEFT JOIN categories c ON tc.category_id = c.id 
        GROUP BY t.id 
        ORDER BY t.is_featured DESC, t.created_at DESC";
$stmt = $pdo->query($sql);
$sigTours = $stmt->fetchAll();

if (count($sigTours) > 0):
    foreach ($sigTours as $tour):
        $img = $tour['thumbnail'] ? $tour['thumbnail'] : 'https://placehold.co/400x300?text=No+Image';
?>
                    <div class="swiper-slide">
                        <div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); height: 100%; display: flex; flex-direction: column;">
                            <div style="position: relative; height: 200px; padding: 20px 20px 0 20px;">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                                
                                <?php if ($tour['is_featured']): ?>
                                    <div class="featured-badge" style="top: 35px; left: 35px;"><i class="fas fa-star"></i> Featured</div>
                                <?php
        endif; ?>
                                <?php if ($tour['duration']): ?>
                                <span style="position: absolute; bottom: 10px; right: 30px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($tour['duration']); ?>
                                </span>
                                <?php
        endif; ?>
                            </div>
                            <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                                <div style="display: flex; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <?php if ($tour['tour_type']): ?>
                                        <span style="background: #e0f7fa; color: #00838f; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                            <i class="fas fa-suitcase"></i> <?php echo htmlspecialchars($tour['tour_type']); ?>
                                        </span>
                                    <?php
        endif; ?>
                                    

                                </div>
                                <h3 style="margin-top: 0; font-size: 18px; margin-bottom: 10px;"><?php echo htmlspecialchars($tour['name']); ?></h3>
                                <?php if ($tour['sub_heading']): ?>
                                <p style="font-size: 13px; color: #777; margin-bottom: 15px; line-height: 1.4;"><?php echo htmlspecialchars(mb_strimwidth($tour['sub_heading'], 0, 260, "...")); ?></p>
                                <?php
        endif; ?>
                                <div style="margin-top: auto;">
                                    <p style="color: #ff1a4a; font-weight: 600; font-size: 16px; margin-bottom: 10px;"><?php echo htmlspecialchars($tour['price']); ?></p>
                                    <a href="tour-details.php?id=<?php echo $tour['id']; ?>" class="btn" style="width: 100%; text-align: center; box-sizing: border-box; display: block; background: var(--accent-color);">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach;
else:
?>
                        <div class="swiper-slide"><div style="text-align: center; padding: 20px;">No signature tours available yet.</div></div>
                    <?php
endif; ?>
                </div>
            </div>
            
            <!-- Navigation Arrows -->
            <div class="swiper-button-prev sig-swiper-arrow"></div>
            <div class="swiper-button-next sig-swiper-arrow"></div>
            
            <div class="swiper-pagination"></div>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding: 0 50px; flex-wrap: wrap; gap: 20px;">
            <p style="max-width: 600px; color: #555; font-size: 16px; margin: 0; text-align: left;">
                Explore our comprehensive collection of tour packages, each designed to provide you with an unforgettable journey through the wonders of Sri Lanka.
            </p>
            <a href="all-tours.php" class="btn" style="padding: 12px 30px; font-size: 16px; background-color: #fff; color: #000; border: 1px solid #000;">See All Packages</a>
        </div>
    </div>
</section>

<!-- Exclusive Customized Tour Section -->
<section style="padding: 100px 0; background: #fff; position: relative; overflow: visible; text-align: center;">
    <div class="container" style="position: relative; z-index: 2;">
        <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 5px; color: #000; font-weight: 400;">Looking for an</h3>
        <h2 style="font-family: 'Playfair Display', serif; font-size: 52px; margin: 0 0 10px 0; color: #333; line-height: 1.2;">Exclusive Customized Tour?</h2>
        <h3 style="font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 35px; color: #000; font-weight: 700;">No Problem</h3>
        
        <a href="booking-inquiry.php" class="btn" style="background-color: #ff1a4a; color: #fff; padding: 14px 40px; border-radius: 50px; font-size: 13px; letter-spacing: 1px; font-weight: 700; text-transform: uppercase; box-shadow: 0 5px 15px rgba(255, 26, 74, 0.3); border: none; display: inline-flex; align-items: center; gap: 8px;">
            Connect with us <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <!-- Decorative Image (Right Side) -->
    <div class="exclusive-tour-art-mobile" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); width: min(520px, 42vw); pointer-events: none; z-index: 1;">
        <!-- User to add image here -->
        <img src="assets/images/custom-tour-art.png" alt="Customized Tour Art" style="width: 100%; height: auto; object-fit: contain; display: block;">
    </div>
</section>

<style>
    @media (max-width: 768px) {
        .exclusive-tour-art-mobile {
            right: 12px !important;
            top: 18px !important;
            transform: none !important;
            width: min(180px, 42vw) !important;
        }
    }
</style>

<!-- Reviews Platform Slider -->
<section style="padding: 60px 0; background: #fff; border-bottom: 1px solid #f9f9f9;">
    <div class="container">
        <div class="swiper reviews-swiper" style="padding-bottom: 40px;">
            <div class="swiper-wrapper" style="align-items: center;">
                <?php
// Fetch Review Partners
$partners = [];
try {
    $partners_stmt = $pdo->query("SELECT * FROM partners ORDER BY created_at ASC");
    $partners = $partners_stmt->fetchAll();
}
catch (Exception $e) {
// Table might not exist yet
}

$reviews_slider_items = $partners;
if (count($reviews_slider_items) > 0 && count($reviews_slider_items) < 6) {
    $reviews_slider_items = array_merge($reviews_slider_items, $partners, $partners);
}

if (count($reviews_slider_items) > 0):
    foreach ($reviews_slider_items as $partner):
?>
                <div class="swiper-slide" style="text-align: center;">
                    <a href="<?php echo htmlspecialchars($partner['link']); ?>" target="_blank" style="opacity: 1; transition: 0.3s; display: inline-block;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        <img src="<?php echo htmlspecialchars($partner['logo']); ?>" alt="<?php echo htmlspecialchars($partner['name']); ?>" style="height: 80px; width: auto; max-width: 220px; object-fit: contain; filter: grayscale(0%); transition: filter 0.3s;" onmouseover="this.style.filter='grayscale(100%)'" onmouseout="this.style.filter='grayscale(0%)'">
                    </a>
                </div>
                <?php
    endforeach;
else:
    // Fallback to placeholder if no partners
?>
                <div class="swiper-slide" style="text-align: center; color: #999; font-size: 14px;">Add Review Partners in Admin Panel</div>
                <?php
endif; ?>
            </div>
            <div class="swiper-pagination reviews-swiper-pagination"></div>
        </div>
    </div>
</section>

<style>
    .reviews-swiper-pagination.swiper-pagination {
        position: static !important;
        margin-top: 16px;
        text-align: center;
    }
</style>

<!-- Destinations / Blog Section -->
<section style="padding: 100px 0; background-color: #ffffffff; position: relative;">
    <!-- Decorative Image (Top Left) -->
    <div style="position: absolute; left: 0; top: 0; width: 300px; opacity: 0.9; pointer-events: none; z-index: 1;">
        <img src="assets/images/custom-tour-art2.png" alt="Destinations Art" style="width: 100%; height: auto;">
    </div>

    <div class="container" style="position: relative; z-index: 2;">
        <!-- Header with Description -->
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; margin-bottom: 15px; color: #333;">Destinations</h2>
            <p style="max-width: 700px; margin: 0 auto; color: #666; font-size: 16px;">Immerse yourself in the stories of Sri Lanka. From hidden gems to cultural insights, our destinations pages are filled with inspiration for your next journey.</p>
        </div>
        
        <div class="swiper-wrapper-container" style="padding: 0 50px; position: relative;">
            <div class="swiper journal-swiper">
                <div class="swiper-wrapper">
                    <?php
// Fetch recent blog posts
$journal_stmt = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY is_featured DESC, created_at DESC LIMIT 5");
$posts = $journal_stmt->fetchAll();

if (count($posts) > 0):
    foreach ($posts as $post):
        $img = $post['thumbnail'] ? $post['thumbnail'] : 'https://placehold.co/400x300?text=Destinations';
        $date = date('F j, Y', strtotime($post['created_at']));
?>
                    <div class="swiper-slide">
                        <div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.03); height: 100%; display: flex; flex-direction: column;">
                            <div style="height: 250px; overflow: hidden; position: relative;">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: 0.5s;">
                                <?php if (isset($post['category_id'])):
            $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $cat_stmt->execute([$post['category_id']]);
            $cat_name = $cat_stmt->fetchColumn();
            if ($cat_name): ?>
                                    <span style="position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); color: #fff; padding: 5px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px;"><?php echo htmlspecialchars($cat_name); ?></span>
                                <?php
            endif;
        endif; ?>
                            </div>
                            <div style="padding: 25px; flex-grow: 1; text-align: center; display: flex; flex-direction: column; align-items: center; position: relative;">
                                <?php if (isset($post['is_featured']) && $post['is_featured']): ?>
                                    <span style="position: absolute; top: -15px; background: var(--accent-color); color: #fff; padding: 5px 15px; font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; border-radius: 20px;">Featured</span>
                                <?php
        endif; ?>
                                <span style="font-size: 12px; color: #888; margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 1px; margin-top: <?php echo(isset($post['is_featured']) && $post['is_featured']) ? '10px' : '0'; ?>;"><?php echo $date; ?></span>
                                <h3 style="font-family: 'Playfair Display', serif; font-size: 20px; color: #333; margin: 0 0 15px 0; line-height: 1.4;"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
                                    <?php echo htmlspecialchars(mb_strimwidth($post['excerpt'] ?? $post['content'], 0, 120, "...")); ?>
                                </p>
                                <a href="post.php?id=<?php echo $post['id']; ?>" style="color: #00bcd4; text-decoration: none; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">READ MORE &rarr;</a>
                            </div>
                        </div>
                    </div>
                    <?php
    endforeach;
else: ?>
                    <div class="swiper-slide"><div style="text-align: center;">No destinations yet.</div></div>
                    <?php
endif; ?>
                </div>
            </div>
            
            <!-- Navigation Arrows (Custom Style) -->
            <!-- Navigation Arrows -->
            <div class="swiper-button-prev journal-swiper-prev"></div>
            <div class="swiper-button-next journal-swiper-next"></div>
        </div>

        <!-- Bottom Section -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding: 0 50px; flex-wrap: wrap; gap: 20px;">
            <p style="max-width: 600px; color: #555; font-size: 16px; margin: 0; text-align: left;">
                Dive deeper into our travel experiences and get expert tips for your Sri Lankan adventure in our full destinations archive.
            </p>
            <a href="destinations.php" class="btn" style="padding: 12px 30px; font-size: 16px; background-color: #fff; color: #000; border: 1px solid #000;">All Destinations</a>
        </div>
    </div>
</section>

<?php
$tripadvisor_reviews = [];
try {
    $tripadvisor_reviews = $pdo->query("SELECT * FROM tripadvisor_reviews WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC")->fetchAll();
}
catch (Exception $e) {
    $tripadvisor_reviews = [];
}

$tripadvisor_widget_embed = trim((string) ($h_settings['tripadvisor_widget_embed'] ?? ''));
?>

<section class="tripadvisor-home-section" id="tripadvisor-reviews">
    <div class="container">
        <div class="tripadvisor-section-header tripadvisor-section-header-row">
            <div class="tripadvisor-section-copy">
                <h2>TripAdvisor Reviews</h2>
                <p>Read genuine guest experiences, explore our TripAdvisor presence, or share the story of your own Sri Lankan journey.</p>
            </div>
            <a class="tripadvisor-write-review-btn" href="review.php">
                <i class="far fa-pen-to-square"></i> Write a review
            </a>
        </div>

        <div class="tripadvisor-home-grid">
            <div class="tripadvisor-column tripadvisor-slider-shell">
                <?php if (count($tripadvisor_reviews) > 0): ?>
                    <div class="swiper tripadvisor-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($tripadvisor_reviews as $review): ?>
                                <?php
                                $excerpt = tripadvisor_review_excerpt((string) $review['review_text'], 80);
                                $review_link = trim((string) ($review['review_link'] ?? ''));
                                $review_photos = tripadvisor_review_photos((string) ($review['review_photos'] ?? ''));
                                $reviewer_avatar = trim((string) ($review['reviewer_image'] ?? '')) ?: ($review_photos[0] ?? '');
                                ?>
                                <div class="swiper-slide" style="height: auto;">
                                    <article class="tripadvisor-review-card">
                                        <div class="tripadvisor-review-top">
                                            <div class="tripadvisor-reviewer">
                                                <?php if ($reviewer_avatar !== ''): ?>
                                                    <img class="tripadvisor-avatar" src="<?php echo htmlspecialchars($reviewer_avatar); ?>" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>">
                                                <?php else: ?>
                                                    <div class="tripadvisor-avatar-placeholder"><?php echo htmlspecialchars(tripadvisor_reviewer_initials((string) $review['reviewer_name'])); ?></div>
                                                <?php endif; ?>

                                                <div class="tripadvisor-reviewer-meta">
                                                    <h3><?php echo htmlspecialchars($review['reviewer_name']); ?></h3>
                                                    <p><?php echo htmlspecialchars((string) ($review['reviewer_location'] ?? '')); ?></p>
                                                </div>
                                            </div>

                                            <div class="tripadvisor-rating" aria-label="<?php echo (int) $review['rating']; ?> out of 5">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="tripadvisor-rating-dot <?php echo $i <= (int) $review['rating'] ? 'is-filled' : ''; ?>"></span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <h3 class="tripadvisor-review-title"><?php echo htmlspecialchars((string) ($review['review_title'] ?? '')); ?></h3>

                                        <p class="tripadvisor-review-text">
                                            <span><?php echo nl2br(htmlspecialchars($excerpt['preview'])); ?></span><?php if ($excerpt['has_more']): ?> <span class="tripadvisor-ellipsis">...</span><span class="tripadvisor-more-text" hidden> <?php echo nl2br(htmlspecialchars($excerpt['remainder'])); ?></span><?php endif; ?>
                                        </p>

                                        <?php if ($excerpt['has_more']): ?>
                                            <button type="button" class="tripadvisor-read-toggle">Read more</button>
                                        <?php endif; ?>

                                        <?php if (!empty($review_photos)): ?>
                                            <div class="tripadvisor-review-photos" aria-label="Review photos">
                                                <?php foreach ($review_photos as $photo_index => $photo): ?>
                                                    <a class="tripadvisor-review-photo-link" href="<?php echo htmlspecialchars($photo); ?>" aria-label="Open review photo <?php echo $photo_index + 1; ?>">
                                                        <img class="tripadvisor-review-photo" src="<?php echo htmlspecialchars($photo); ?>" alt="Photo shared by <?php echo htmlspecialchars($review['reviewer_name']); ?>" loading="lazy">
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="tripadvisor-review-footer">
                                            <p class="tripadvisor-trip-date"><?php echo !empty($review['trip_date']) ? 'Trip date: ' . htmlspecialchars(tripadvisor_format_trip_date((string) $review['trip_date'])) : ''; ?></p>
                                            <?php if ($review_link !== ''): ?>
                                                <a class="tripadvisor-review-link" href="<?php echo htmlspecialchars($review_link); ?>" target="_blank" rel="noopener noreferrer">View review</a>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                <?php else: ?>
                    <div class="tripadvisor-review-empty">Be the first guest to share a Travel with IS Tours experience.</div>
                <?php endif; ?>
            </div>

            <div class="tripadvisor-column">
                <?php if ($tripadvisor_widget_embed !== ''): ?>
                    <aside class="tripadvisor-widget-card">
                        <div class="tripadvisor-widget-shell js-tripadvisor-widget-shell">
                            <?php echo $tripadvisor_widget_embed; ?>
                        </div>
                    </aside>
                <?php else: ?>
                    <div class="tripadvisor-widget-placeholder">Trip Advisor Live Stats Loading...</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$short_videos = [];
try {
    $short_videos = $pdo->query("SELECT * FROM short_videos WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC")->fetchAll();
}
catch (Exception $e) {
    $short_videos = [];
}
?>

<?php if (count($short_videos) > 0): ?>
<section class="short-videos-section">
    <div class="container">
        <div class="short-videos-header">
            <span class="short-videos-kicker">Short Moments</span>
            <h2 class="short-videos-title">A Quick Glimpse Of Sri Lanka</h2>
            <p class="short-videos-description">Swipe through short travel clips captured across the island, from scenic train rides to coastal moments and hill-country views.</p>
        </div>

        <div class="short-videos-slider-shell">
            <div class="swiper short-videos-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($short_videos as $video): ?>
                        <div class="swiper-slide">
                            <article class="short-video-card">
                                <div class="short-video-frame">
                                    <span class="short-video-badge"><i class="fas fa-play"></i> Short Clip</span>
                                    <video
                                        class="js-short-video"
                                        muted
                                        loop
                                        playsinline
                                        preload="metadata"
                                        <?php echo !empty($video['poster_path']) ? 'poster="' . htmlspecialchars($video['poster_path']) . '"' : ''; ?>>
                                        <source src="<?php echo htmlspecialchars($video['video_path']); ?>">
                                    </video>
                                </div>
                                <?php
                                $short_video_title = trim((string) ($video['title'] ?? ''));
                                $short_video_caption = trim((string) ($video['caption'] ?? ''));
                                ?>
                                <?php if ($short_video_title !== '' || $short_video_caption !== ''): ?>
                                    <div class="short-video-content">
                                        <?php if ($short_video_title !== ''): ?>
                                            <h3><?php echo htmlspecialchars($short_video_title); ?></h3>
                                        <?php endif; ?>
                                        <?php if ($short_video_caption !== ''): ?>
                                            <p><?php echo htmlspecialchars($short_video_caption); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="swiper-button-prev short-videos-arrow short-videos-prev"></div>
            <div class="swiper-button-next short-videos-arrow short-videos-next"></div>
            <div class="swiper-pagination short-videos-pagination"></div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Gallery Section (Masonry) -->
<section style="padding: 100px 0; background: #fff;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; color: #888; font-weight: 400; font-style: italic;">Captured Moments</h3>
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; color: #333; margin-top: 10px;">Through Our Lens</h2>
        </div>

        <div class="masonry-grid" style="column-count: 3; column-gap: 20px;">
            <?php
// Fetch Featured Gallery Images
$gallery_stmt = $pdo->query("SELECT * FROM gallery WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 8");
$gallery_images = $gallery_stmt->fetchAll();

if (count($gallery_images) > 0):
    foreach ($gallery_images as $img):
?>
                <div style="break-inside: avoid; margin-bottom: 20px; border-radius: 12px; overflow: hidden; position: relative; group;">
                    <a href="<?php echo htmlspecialchars($img['image_path']); ?>" class="home-gallery-lightbox">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($img['alt_text']); ?>" 
                             style="width: 100%; display: block; transition: transform 0.5s; cursor: pointer;"
                             onmouseover="this.style.transform='scale(1.05)'" 
                             onmouseout="this.style.transform='scale(1)'">
                    </a>
                </div>
            <?php
    endforeach;
else:
    // Fallback if no featured images
    echo '<p style="text-align:center; width:100%;">No featured images yet.</p>';
endif;
?>
        </div>
        
        <div style="text-align: center; margin-top: 50px;">
            <a href="gallery.php" class="btn" style="padding: 12px 40px; border: 1px solid #000; color: #000; background: transparent; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#000'; this.style.color='#fff'" onmouseout="this.style.background='transparent'; this.style.color='#000'">View Full Gallery</a>
        </div>
    </div>
</section>

<!-- SimpleLightbox CSS (Repeated here or moved to header if used globally) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.2/simple-lightbox.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.2/simple-lightbox.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize for Home Gallery too
        new SimpleLightbox('.home-gallery-lightbox', { 
            captionsData: 'alt',
            captionDelay: 250,
            animationSpeed: 200,
            fadeSpeed: 200,
        });

        new SimpleLightbox('.tripadvisor-review-photo-link', {
            captions: false,
            animationSpeed: 200,
            fadeSpeed: 200,
        });
    });
</script>

<script>
    // Initialize Signature Swiper
    const sigSwiper = new Swiper('.signature-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true, // Should be true if enough slides, but Swiper handles it gracefully usually
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        navigation: {
            nextEl: '.sig-swiper-arrow.swiper-button-next',
            prevEl: '.sig-swiper-arrow.swiper-button-prev',
        },
        breakpoints: {
            640: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            },
        }
    });

    // Initialize Service Swiper
    const serviceSwiper = new Swiper('.service-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        navigation: {
            nextEl: '.svc-swiper-arrow.swiper-button-next',
            prevEl: '.svc-swiper-arrow.swiper-button-prev',
        },
        breakpoints: {
            640: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 3 },
        }
    });

    // Initialize Reviews Swiper
    const reviewsSwiper = new Swiper('.reviews-swiper', {
        slidesPerView: 1,
        spaceBetween: 24,
        loop: true,
        loopAdditionalSlides: 6,
        watchOverflow: false,
        centeredSlides: true,
        pagination: {
            el: '.reviews-swiper-pagination',
            clickable: true,
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        breakpoints: {
            576: { slidesPerView: 2, spaceBetween: 32, centeredSlides: false },
            768: { slidesPerView: 4, spaceBetween: 50, centeredSlides: false },
            1200: { slidesPerView: 4, spaceBetween: 60 },
        }
    });

    // Initialize Journal Swiper
    const journalSwiper = new Swiper('.journal-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        navigation: {
            nextEl: '.journal-swiper-next',
            prevEl: '.journal-swiper-prev',
        },
        breakpoints: {
            640: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 3 },
        }
    });

    const shortVideosSwiperElement = document.querySelector('.short-videos-swiper');
    if (shortVideosSwiperElement) {
        const equalizeShortVideoCardHeights = () => {
            const cards = shortVideosSwiperElement.querySelectorAll('.short-video-card');
            let tallestCardHeight = 0;

            cards.forEach((card) => {
                card.style.minHeight = '';
            });

            cards.forEach((card) => {
                tallestCardHeight = Math.max(tallestCardHeight, card.offsetHeight);
            });

            cards.forEach((card) => {
                card.style.minHeight = `${tallestCardHeight}px`;
            });
        };

        const syncShortVideosPlayback = (swiperInstance) => {
            const allVideos = swiperInstance.el.querySelectorAll('.js-short-video');
            allVideos.forEach((video) => {
                video.pause();
                video.currentTime = 0;
            });

            const activeSlides = Array.from(swiperInstance.slides).filter((slide) => slide.classList.contains('swiper-slide-active'));
            activeSlides.forEach((slide) => {
                const video = slide.querySelector('.js-short-video');
                if (!video) {
                    return;
                }

                const playPromise = video.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(() => {});
                }
            });
        };

        const shortVideosSwiper = new Swiper('.short-videos-swiper', {
            slidesPerView: 1.15,
            spaceBetween: 20,
            loop: true,
            centeredSlides: false,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.short-videos-next',
                prevEl: '.short-videos-prev',
            },
            pagination: {
                el: '.short-videos-pagination',
                clickable: true,
            },
            breakpoints: {
                640: { slidesPerView: 1.6, spaceBetween: 20 },
                768: { slidesPerView: 2.2, spaceBetween: 24 },
                1024: { slidesPerView: 3, spaceBetween: 28 },
            },
            on: {
                init() {
                    equalizeShortVideoCardHeights();
                    syncShortVideosPlayback(this);
                },
                slideChangeTransitionEnd() {
                    syncShortVideosPlayback(this);
                },
                resize() {
                    equalizeShortVideoCardHeights();
                },
            }
        });

        window.addEventListener('load', equalizeShortVideoCardHeights);
        window.addEventListener('resize', equalizeShortVideoCardHeights);
        shortVideosSwiperElement.addEventListener('mouseenter', () => shortVideosSwiper.autoplay.stop());
        shortVideosSwiperElement.addEventListener('mouseleave', () => shortVideosSwiper.autoplay.start());
    }

    const tripadvisorSwiperElement = document.querySelector('.tripadvisor-swiper');
    if (tripadvisorSwiperElement) {
        new Swiper('.tripadvisor-swiper', {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: <?php echo count($tripadvisor_reviews) > 1 ? 'true' : 'false'; ?>,
            autoHeight: true,
            autoplay: <?php echo count($tripadvisor_reviews) > 1 ? '{ delay: 5000, disableOnInteraction: false }' : 'false'; ?>,
            pagination: {
                el: '.tripadvisor-swiper .swiper-pagination',
                clickable: true,
            }
        });
    }

    document.querySelectorAll('.tripadvisor-read-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const card = button.closest('.tripadvisor-review-card');
            if (!card) return;

            const moreText = card.querySelector('.tripadvisor-more-text');
            const ellipsis = card.querySelector('.tripadvisor-ellipsis');
            if (!moreText || !ellipsis) return;

            const isExpanded = !moreText.hasAttribute('hidden');
            if (isExpanded) {
                moreText.setAttribute('hidden', 'hidden');
                ellipsis.removeAttribute('hidden');
                button.textContent = 'Read more';
            } else {
                moreText.removeAttribute('hidden');
                ellipsis.setAttribute('hidden', 'hidden');
                button.textContent = 'Read less';
            }
        });
    });

    function resizeTripadvisorWidgets() {
        document.querySelectorAll('.js-tripadvisor-widget-shell').forEach(function(shell) {
            const embed = shell.firstElementChild;
            if (!embed) return;

            embed.style.transform = '';
            embed.style.transformOrigin = 'top left';
            embed.style.width = '';
            shell.style.minHeight = '';

            const shellWidth = shell.clientWidth;
            const embedWidth = embed.scrollWidth;

            if (shellWidth > 0 && embedWidth > shellWidth) {
                const scale = shellWidth / embedWidth;
                embed.style.width = embedWidth + 'px';
                embed.style.transform = 'scale(' + scale + ')';
                shell.style.minHeight = (embed.scrollHeight * scale) + 'px';
            }
        });
    }

    resizeTripadvisorWidgets();
    window.addEventListener('load', resizeTripadvisorWidgets);
    window.addEventListener('resize', resizeTripadvisorWidgets);
    setTimeout(resizeTripadvisorWidgets, 600);
    setTimeout(resizeTripadvisorWidgets, 1500);

    // Note: The Services Swiper is already initialized in previous script block. 
    // They won't conflict because we use distinct class selectors (.service-swiper vs .signature-swiper)
</script>




<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
