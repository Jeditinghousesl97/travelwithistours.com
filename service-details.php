<?php
require_once 'config/db.php';
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    include '404.php';
    exit;
}

// SEO Metadata
$page_title = !empty($service['seo_title']) ? $service['seo_title'] : $service['name'];
$page_description = !empty($service['seo_description']) ? $service['seo_description'] : mb_strimwidth(strip_tags($service['long_description']), 0, 160, "...");
$page_keywords = !empty($service['seo_keywords']) ? $service['seo_keywords'] : $service['name'];
$page_og_image = $service['icon'];

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header" style="background-image: url('<?php echo $service['icon'] ? $service['icon'] : 'https://placehold.co/1920x600?text=Service'; ?>'); padding: 150px 0; text-align: center; color: #fff; background-size: cover; background-position: center; position: relative;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);"></div>
    <div class="container" style="position: relative; z-index: 2; padding-top: 100px;">
        <h1><?php echo htmlspecialchars($service['name']); ?></h1>
    </div>
</section>

<!-- Service Content -->
<section class="section" style="padding: 80px 0;">
    <div class="container" style="max-width: 800px;">
        <div class="content formatted-content">
            <?php if ($service['long_description']): ?>
                <?php echo str_replace('../assets', 'assets', $service['long_description']); ?>
            <?php
else: ?>
                <p><?php echo htmlspecialchars($service['short_description']); ?></p>
            <?php
endif; ?>
        </div>
        
        <style>
            /* Restore basic formatting for TinyMCE output */
            .formatted-content p {
                margin-bottom: 1em;
                line-height: 1.8;
            }
            .formatted-content ul {
                list-style-type: disc;
                padding-left: 40px;
                margin-bottom: 1em;
            }
            .formatted-content ol {
                list-style-type: decimal;
                padding-left: 40px;
                margin-bottom: 1em;
            }
            .formatted-content li {
                margin-bottom: 0.5em;
            }
            .formatted-content h1, .formatted-content h2, .formatted-content h3, .formatted-content h4, .formatted-content h5, .formatted-content h6 {
                margin-top: 1.5em;
                margin-bottom: 0.5em;
                font-family: var(--heading-font), serif;
            }
            .formatted-content img {
                max-width: 100%;
                height: auto;
                border-radius: 8px;
                margin: 1em 0;
            }
            .formatted-content blockquote {
                border-left: 4px solid var(--accent-color);
                padding-left: 15px;
                margin: 1.5em 0;
                color: #555;
                font-style: italic;
            }
            .formatted-content strong {
                font-weight: bold;
            }
            .formatted-content em {
                font-style: italic;
            }
            .formatted-content table {
                width: 100%;
                border-collapse: collapse;
                margin: 1.5em 0;
            }
            .formatted-content th, .formatted-content td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            .formatted-content th {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            .formatted-content pre {
                background: #f4f4f4;
                padding: 15px;
                border-radius: 8px;
                overflow-x: auto;
                font-family: monospace;
            }
        </style>
        
        <div style="margin-top: 50px; text-align: center;">
            <a href="contact.php?subject=Inquiry about <?php echo urlencode($service['name']); ?>" class="btn">Inquire About This Service</a>
        </div>
    </div>
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
