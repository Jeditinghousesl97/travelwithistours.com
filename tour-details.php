<?php
require_once 'config/db.php';

// Get Tour ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: tours.php");
    exit;
}

// Fetch Tour Details
$stmt = $pdo->prepare("SELECT * FROM tours WHERE id = ?");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    http_response_code(404);
    include '404.php';
    exit;
}

// Fetch Itinerary
$stmt = $pdo->prepare("SELECT * FROM tour_itineraries WHERE tour_id = ? ORDER BY display_order ASC");
$stmt->execute([$id]);
$itinerary = $stmt->fetchAll();

// SEO Metadata
$page_title = !empty($tour['seo_title']) ? $tour['seo_title'] : $tour['name'];
$page_description = !empty($tour['seo_description']) ? $tour['seo_description'] : mb_strimwidth(strip_tags($tour['long_description']), 0, 160, "...");
$page_keywords = !empty($tour['seo_keywords']) ? $tour['seo_keywords'] : $tour['tour_type'];
$page_og_image = $tour['thumbnail'];

// Include Header
include 'includes/header.php';
?>

<!-- Custom CSS for this page -->
<link rel="stylesheet" href="assets/css/tour-details.css">

<!-- 1. Header Section -->
<section class="tour-header-section">
    <div class="tour-header-container">
        <!-- Image Column with Curve -->
        <div class="tour-header-img-col" style>
            <?php if ($tour['thumbnail']): ?>
                <img src="<?php echo $tour['thumbnail']; ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>">
            <?php
else: ?>
                <img src="https://placehold.co/800x600?text=No+Image" alt="Placeholder">
            <?php
endif; ?>
        </div>
        
        <!-- Text Column -->
        <div class="tour-header-text-col">
            <h1 class="tour-title"><?php echo htmlspecialchars($tour['name']); ?></h1>
            <?php if ($tour['sub_heading']): ?>
                <p class="tour-subtitle"><?php echo nl2br(htmlspecialchars($tour['sub_heading'])); ?></p>
            <?php
endif; ?>
            
            <div class="tour-meta-buttons">
                <!-- Enquire Button -->
                <a href="contact.php?subject=Inquiry: <?php echo urlencode($tour['name']); ?>" class="btn-pill btn-blue">
                    Enquire Now <i class="fas fa-envelope"></i>
                </a>
                <!-- Book Now Button -->
                <a href="booking-inquiry.php" class="btn-pill btn-pink">
                    Book Now <i class="fas fa-circle-right"></i> 
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Icons Bar -->
<div class="container">
    <div class="tour-icons-bar">
        <?php if ($tour['duration']): ?>
        <div class="tour-icon-item">
            <i class="far fa-clock"></i>
            <span><?php echo htmlspecialchars($tour['duration']); ?></span>
        </div>
        <?php
endif; ?>
        
        <?php if ($tour['location_count']): ?>
        <div class="tour-icon-item">
            <i class="fas fa-map-marker-alt"></i>
            <span><?php echo htmlspecialchars($tour['location_count']); ?> Locations</span>
        </div>
        <?php
endif; ?>
        
        <?php if ($tour['tour_type']): ?>
        <div class="tour-icon-item">
            <i class="fas fa-spa"></i> <!-- Using spa icon as generic for type, can be dynamic -->
            <span><?php echo htmlspecialchars($tour['tour_type']); ?></span>
        </div>
        <?php
endif; ?>
        
        <?php if ($tour['min_people']): ?>
        <div class="tour-icon-item">
            <i class="fas fa-user-friends"></i>
            <span><?php echo htmlspecialchars($tour['min_people']); ?></span>
        </div>
        <?php
endif; ?>
    </div>
</div>

<!-- 2.5 Description Section -->
<?php if ($tour['long_description']): ?>
<section class="section" style="padding-bottom: 0;">
    <div class="container">
        <div class="tour-description-box" style="background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #eee;">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 20px;">Tour Overview</h3>
            <div class="tour-description-content" style="line-height: 1.8; color: #555;">
                <?php echo str_replace('../assets', 'assets', $tour['long_description']); ?>
            </div>
        </div>
    </div>
</section>
<?php
endif; ?>

<!-- 3. Itinerary Section -->
<section class="itinerary-section">
    <div class="container">
        <?php if (count($itinerary) > 0): ?>
            <div class="itinerary-list">
                <?php foreach ($itinerary as $item): ?>
                <div class="itinerary-item">
                    <!-- Day Bubble -->
                    <div class="day-bubble-col">
                        <div class="day-bubble">
                            <?php
        // Improve display of day number
        $dayNum = $item['day_number'];
        // If plain number, add 'Day ' text visually if needed, or keep minimal as per design
        echo htmlspecialchars($dayNum);
?>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="itinerary-content">
                        <h3 class="itinerary-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <div class="itinerary-desc">
                            <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                        </div>
                        
                        <!-- Images -->
                        <?php if ($item['image_1'] || $item['image_2']): ?>
                        <div class="itinerary-images">
                            <?php if ($item['image_1']): ?>
                                <img src="<?php echo $item['image_1']; ?>" alt="Itinerary Image 1">
                            <?php
            endif; ?>
                            
                            <?php if ($item['image_2']): ?>
                                <img src="<?php echo $item['image_2']; ?>" alt="Itinerary Image 2">
                            <?php
            endif; ?>
                        </div>
                        <?php
        endif; ?>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
        <?php
else: ?>
            <p style="text-align: center; color: #777;">Detailed itinerary coming soon.</p>
        <?php
endif; ?>
    </div>
</section>

<!-- 4. Highlights & Map -->
<section class="section">
    <div class="container">
        <div class="highlights-map-grid">
            <!-- Map Column (Left usually in design, can swap) -->
            <div class="map-box">
                <?php if ($tour['map_embed_code']): ?>
                    <?php echo $tour['map_embed_code']; ?>
                <?php
else: ?>
                    <div style="width: 100%; height: 400px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 12px;">Map Placeholder</div>
                <?php
endif; ?>
            </div>
            
            <!-- Highlights Column -->
            <div class="highlights-box">
                <h3>Journey Highlights</h3>
                <?php if ($tour['highlights']): ?>
                    <?php echo str_replace('../assets', 'assets', $tour['highlights']); ?>
                <?php
else: ?>
                    <p>No highlights added.</p>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 5. Tips & FAQ -->
<section class="section" style="padding-bottom: 80px;">
    <div class="container">
        <div class="tips-faq-grid">
            <!-- Tips (Pink Box) -->
            <div class="tips-box">
                <h3>Insightful Tips</h3>
                <div class="tips-content">
                    <?php if ($tour['insightful_tips']): ?>
                        <?php echo str_replace('../assets', 'assets', $tour['insightful_tips']); ?>
                    <?php
else: ?>
                        <p>No tips available.</p>
                    <?php
endif; ?>
                </div>
            </div>
            
            <!-- FAQ (White Box) -->
            <div class="faq-box">
                <h3>FAQ</h3>
                <div class="tips-content"> <!-- Reusing typography style -->
                     <?php if ($tour['faq_content']): ?>
                        <?php echo str_replace('../assets', 'assets', $tour['faq_content']); ?>
                    <?php
else: ?>
                        <p>No FAQs available.</p>
                    <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Book Now Button -->
<section style="margin-bottom: 50px; background: #fff; position: relative; overflow: hidden; text-align: center;">
  
        <a href="booking-inquiry.php" class="btn" style="background-color: #ff1a4a; color: #fff; padding: 14px 40px; border-radius: 50px; font-size: 13px; letter-spacing: 1px; font-weight: 700; text-transform: uppercase; box-shadow: 0 5px 15px rgba(255, 26, 74, 0.3); border: none; display: inline-flex; align-items: center; gap: 8px;">
            Book Now <i class="fas fa-arrow-right"></i>
        </a>
</section>


<!-- Signature Tours -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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
<section style="padding: 100px 0; background: #fff; position: relative; overflow: hidden; text-align: center;">
    <div class="container" style="position: relative; z-index: 2;">
        <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 5px; color: #000; font-weight: 400;">Looking for an</h3>
        <h2 style="font-family: 'Playfair Display', serif; font-size: 52px; margin: 0 0 10px 0; color: #333; line-height: 1.2;">Exclusive Customized Tour?</h2>
        <h3 style="font-family: 'Playfair Display', serif; font-size: 28px; margin-bottom: 35px; color: #000; font-weight: 700;">No Problem</h3>
        
        <a href="booking-inquiry.php" class="btn" style="background-color: #ff1a4a; color: #fff; padding: 14px 40px; border-radius: 50px; font-size: 13px; letter-spacing: 1px; font-weight: 700; text-transform: uppercase; box-shadow: 0 5px 15px rgba(255, 26, 74, 0.3); border: none; display: inline-flex; align-items: center; gap: 8px;">
            Connect with us <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <!-- Decorative Image (Right Side) -->
    <div style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 450px; max-width: 40%; pointer-events: none; z-index: 1;">
        <!-- User to add image here -->
        <img src="assets/images/custom-tour-art.png" alt="Customized Tour Art" style="width: 100%; height: auto; object-fit: contain;">
    </div>
</section>


<!-- Why Choose Us Section -->
<section class="section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <h2 class="section-title" style="text-align: center; border-left: none; padding-left: 0; font-size: 42px; margin-bottom: 60px; font-family: 'Playfair Display', serif;">Why choose us?</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 50px; text-align: center;">
            
            <!-- Item 1 -->
            <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #f3e5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #8e24aa;">
                    <i class="fas fa-tags" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">Price match guarantee</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">Amazing holiday package deals in Sri Lanka at un-matchable rates.</p>
            </div>

            <!-- Item 2 -->
             <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #e0f7fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #006064;">
                    <i class="far fa-compass" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">Proven experience</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">The Travel with IS Tours team comprises locals who are well experienced in the field.</p>
            </div>

            <!-- Item 3 -->
             <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #e8eaf6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1a237e;">
                    <i class="far fa-lightbulb" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">Personal consultant</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">Our friendly team of consultants offer personalized services to clients.</p>
            </div>

            <!-- Item 4 -->
             <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #e0f2f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #004d40;">
                    <i class="fas fa-mobile-alt" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">24 Hour Ground Support</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">We are at your service 24 hours a day to help with any concerns.</p>
            </div>

            <!-- Item 5 -->
             <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #fce4ec; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #880e4f;">
                    <i class="fas fa-passport" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">Fair Booking Conditions</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">The bookings policy is prepared with utmost concern for our guests.</p>
            </div>

            <!-- Item 6 -->
             <div class="choose-us-item">
                <div style="width: 70px; height: 70px; margin: 0 auto 20px; background: #f1f8e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #33691e;">
                    <i class="fas fa-shield-alt" style="font-size: 28px;"></i>
                </div>
                <h3 style="font-size: 20px; font-family: 'Playfair Display', serif; margin-bottom: 15px; color: #333;">Secure Payment Centre</h3>
                <p style="color: #666; font-size: 14px; line-height: 1.6;">We use a safe and secure financial platform to confirm all your bookings.</p>
            </div>

        </div>
    </div>
</section>






<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // Initialize Signature Swiper
    const sigSwiper = new Swiper('.signature-swiper', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
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
</script>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
