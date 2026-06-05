<?php
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    header("Location: destinations.php");
    exit;
}

$post_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.username as author_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.author_id = u.id WHERE p.id = ? AND p.status = 'published'");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    include '404.php';
    exit;
}

// SEO Metadata
$page_title = !empty($post['seo_title']) ? $post['seo_title'] : $post['title'];
$page_description = !empty($post['seo_description']) ? $post['seo_description'] : mb_strimwidth(strip_tags($post['content']), 0, 160, "...");
$page_keywords = !empty($post['seo_keywords']) ? $post['seo_keywords'] : $post['category_name'];
$page_og_image = $post['thumbnail'];

include 'includes/header.php';
?>

<!-- Hero Section -->

<section class="post-page-header" style="position: relative; height: 40vh; min-height: 400px; display: flex; align-items: center; justify-content: center; color: #fff; background-size: cover; background-position: center; background-repeat: no-repeat; background-image: url('<?php echo htmlspecialchars($post['thumbnail'] ? $post['thumbnail'] : 'assets/images/custom-tour-art2.png'); ?>');">
    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>
    <div class="container" style="position: relative; z-index: 2; text-align: center;">
        <?php if ($post['category_name']): ?>
            <span style="background: var(--accent-color); color: #fff; padding: 5px 15px; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; border-radius: 30px; display: inline-block; margin-bottom: 20px;">
                <?php echo htmlspecialchars($post['category_name']); ?>
            </span>
        <?php
endif; ?>
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; line-height: 1.2; margin-bottom: 15px;"><?php echo htmlspecialchars($post['title']); ?></h1>
        <p style="font-size: 18px; opacity: 0.9;">
            By <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?> &bull; <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
        </p>
    </div>
</section>

<style>
    @media (max-width: 992px) {
        .post-page-header {
            height: 100vh !important;
            padding: 0 !important;
            display: flex !important;
            align-items: flex-end !important; /* Bottom */
            justify-content: center !important; /* Center */
        }
        .post-page-header .container {
            padding-bottom: 100px; /* Spacing from bottom */
            width: 100%;
        }
    }
</style>

<!-- Content Section -->
<section class="section" style="padding: 80px 0;">
    <div class="container" style="max-width: 900px;">
        <div class="blog-content" style="font-size: 18px; line-height: 1.8; color: #444;">
            <?php echo str_replace('../assets', 'assets', $post['content']); ?>
        </div>
        
        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid #eee;">
            <a href="destinations.php" class="btn" style="background: #333; color: #fff;">&larr; Back to Destinations</a>
        </div>
    </div>
</section>

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
</script>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
