<?php

require_once 'config/db.php';
include 'includes/header.php';


// Get current category filter
$category_id = isset($_GET['category']) ? $_GET['category'] : 'all';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories WHERE type = 'blog' ORDER BY name ASC")->fetchAll();

// Build Query for Posts
$sql = "SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published'";
$params = [];

if ($category_id != 'all') {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

// Count total posts for pagination
$count_stmt = $pdo->prepare(str_replace("SELECT p.*, c.name as category_name", "SELECT COUNT(*)", $sql));
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Add sorting and pagination
$sql .= " ORDER BY p.is_featured DESC, p.created_at DESC LIMIT $limit OFFSET $offset";

// Fetch Posts
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<!-- Page Header -->
<!-- Page Header -->
<section class="page-header journal-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/journal.webp'); background-color: #f4f4f4; padding: 100px 0; text-align: center; background-size: cover; background-position: center; position: relative;">
    <div class="container" style="position: relative; z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #fff; margin-bottom: 10px;">Destinations</h1>
        <p style="color: #eee; font-size: 18px; max-width: 600px; margin: 0 auto;">Stories, tips, and guides for your Sri Lankan adventure.</p>
    </div>
</section>

<style>
    @media (max-width: 992px) {
        /* Mobile Header Optimization */
        .journal-page-header {
            height: 60vh !important;
            padding: 0 !important;
            display: flex !important;
            align-items: flex-end !important; /* Bottom */
            justify-content: center !important; /* Center */
        }
        .journal-page-header .container {
            padding-bottom: 60px; /* Spacing from bottom */
            width: 100%;
        }
    }
</style>

<!-- Blog Section -->
<section class="section" style="padding: 60px 0; background: #fff;">
    <div class="container">
        
        <!-- Category Filter -->
        <div class="filter-container" style="text-align: center; margin-bottom: 50px;">
            <a href="?category=all" class="btn filter-btn <?php echo $category_id == 'all' ? 'active' : ''; ?>" style="margin: 5px; padding: 10px 25px; border-radius: 30px; border: 1px solid #ddd; color: #555; background: #fff; transition: all 0.3s;">All Stories</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat['id']; ?>" class="btn filter-btn <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>" style="margin: 5px; padding: 10px 25px; border-radius: 30px; border: 1px solid #ddd; color: #555; background: #fff; transition: all 0.3s;">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php
endforeach; ?>
        </div>
        
        <style>
            .filter-btn:hover, .filter-btn.active {
                background-color: #000 !important;
                color: #fff !important;
                border-color: #000 !important;
            }
        </style>

        <!-- Posts Grid -->
        <?php if (count($posts) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
                <?php foreach ($posts as $post):
        $img = $post['thumbnail'] ? $post['thumbnail'] : 'https://placehold.co/600x400?text=No+Image';
        $date = date('F j, Y', strtotime($post['created_at']));
?>
                <div class="journal-card" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: transform 0.3s; display: flex; flex-direction: column;">
                    <div style="height: 240px; overflow: hidden; position: relative;">
                        <a href="post.php?id=<?php echo $post['id']; ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                        </a>
                        
                        <!-- Category Label -->
                        <?php if ($post['category_name']): ?>
                            <span style="position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.7); color: #fff; padding: 5px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; font-weight: 600;">
                                <?php echo htmlspecialchars($post['category_name']); ?>
                            </span>
                        <?php
        endif; ?>

                        <!-- Featured Ribbon -->
                        <?php if ($post['is_featured']): ?>
                            <span style="position: absolute; top: 15px; right: 15px; background: #ff1a4a; color: #fff; padding: 5px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; font-weight: 700;">
                                Featured
                            </span>
                        <?php
        endif; ?>
                    </div>
                    
                    <div style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
                        <span style="font-size: 12px; color: #888; margin-bottom: 10px; display: block; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="far fa-calendar-alt" style="margin-right: 5px;"></i> <?php echo $date; ?>
                        </span>
                        
                        <h3 style="font-family: 'Playfair Display', serif; font-size: 22px; color: #333; margin: 0 0 15px 0; line-height: 1.3;">
                            <a href="post.php?id=<?php echo $post['id']; ?>" style="color: #333; text-decoration: none;"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h3>
                        
                        <p style="color: #666; font-size: 15px; line-height: 1.6; margin-bottom: 20px; flex-grow: 1;">
                            <?php echo htmlspecialchars(mb_strimwidth($post['excerpt'] ?? strip_tags($post['content']), 0, 120, "...")); ?>
                        </p>
                        
                        <div style="border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                             <a href="post.php?id=<?php echo $post['id']; ?>" style="color: #333; text-decoration: none; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #ff1a4a; padding-bottom: 2px;">Read Story</a>
                        </div>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="margin-top: 60px; text-align: center;">
                <div class="pagination" style="display: inline-flex; gap: 10px;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>" 
                           style="width: 40px; height: 40px; line-height: 40px; border-radius: 50%; border: 1px solid #ddd; color: #333; text-decoration: none; <?php echo $i == $page ? 'background: #000; color: #fff; border-color: #000;' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php
        endfor; ?>
                </div>
            </div>
            <?php
    endif; ?>

        <?php
else: ?>
            <div style="text-align: center; padding: 50px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="color: #777;">No stories found in this category.</h3>
                <a href="destinations.php?category=all" class="btn" style="margin-top: 20px; background: #333; color: #fff; padding: 10px 25px; text-decoration: none; display: inline-block;">View All Stories</a>
            </div>
        <?php
endif; ?>

    </div>
</section>

<!-- Tourism Trends Section (New Section Added) -->
<section style="padding: 80px 0; background: #fafafa;">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: #ff1a4a; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; font-size: 14px; margin-bottom: 10px;">Travel Updates</p>
            <h2 style="font-family: 'Playfair Display', serif; font-size: 42px; color: #333; margin: 0;">Sri Lanka Tourism Trends</h2>
        </div>

        <div class="trends-grid">
            
            <!-- 1. Strong Growth -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #e3f2fd; color: #1565c0;">
                    <i class="fas fa-globe-asia"></i>
                </div>
                <h3 class="trend-title">Sri Lanka Tourism Continues Strong Growth</h3>
                <p class="trend-desc">
                    Sri Lanka’s tourism sector continues its positive momentum, recording a steady increase in international arrivals. The island remains a highly sought-after destination for culture, nature, wildlife, wellness, and beach holidays, reflecting renewed global confidence in Sri Lanka as a safe and rewarding travel destination.
                </p>
            </div>

            <!-- 2. Air Connectivity -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #fff0f3; color: #ff1a4a;">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <h3 class="trend-title">Improved Air Connectivity & New Markets</h3>
                <p class="trend-desc">
                    Enhanced international flight connectivity has strengthened Sri Lanka’s access to key markets including South Asia, the Middle East, Europe, and Central Asia. New routes and increased frequencies are making travel to Sri Lanka more convenient for global travelers.
                </p>
            </div>

            <!-- 3. Digital Convenience -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #e0f7fa; color: #00838f;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3 class="trend-title">Digital Convenience for Travelers</h3>
                <p class="trend-desc">
                    The tourism industry is embracing digital transformation, with hotels and service providers introducing advanced digital payment options. These improvements enhance ease of travel, particularly for visitors from regional markets, and support a smoother travel experience across the island.
                </p>
            </div>

            <!-- 4. Long-Stay / Nomad -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #fff8e1; color: #f57f17;">
                    <i class="fas fa-laptop-house"></i>
                </div>
                <h3 class="trend-title">Rise of Long-Stay & Digital Nomad Travel</h3>
                <p class="trend-desc">
                    Sri Lanka is gaining popularity among long-stay travelers and remote workers, thanks to flexible visa options, affordable living, scenic environments, and strong internet connectivity. This trend supports sustainable tourism and extended travel experiences beyond traditional holidays.
                </p>
            </div>

            <!-- 5. Sustainable / Experiential -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #e8f5e9; color: #2e7d32;">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3 class="trend-title">Focus on Sustainable & Experiential Tourism</h3>
                <p class="trend-desc">
                    There is growing demand for eco-friendly travel, community-based tourism, and meaningful cultural experiences. Travelers are increasingly seeking village tours, wildlife conservation experiences, wellness retreats, and authentic local interactions.
                </p>
            </div>

            <!-- 6. Cultural/Spiritual -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #f3e5f5; color: #7b1fa2;">
                    <i class="fas fa-landmark"></i>
                </div>
                <h3 class="trend-title">Cultural, Spiritual & Special Interest Tourism on the Rise</h3>
                <p class="trend-desc">
                    Interest in heritage routes, Ramayana trails, educational tours, culinary journeys, wellness travel, and special-interest tourism continues to grow, positioning Sri Lanka as a diverse destination catering to niche and bespoke travel experiences.
                </p>
            </div>

            <!-- 7. Adventure/Marine -->
            <div class="trend-card">
                <div class="trend-icon" style="background: #e0f2f1; color: #00695c;">
                    <i class="fas fa-water"></i>
                </div>
                <h3 class="trend-title">Adventure & Marine Tourism Expansion</h3>
                <p class="trend-desc">
                    Adventure tourism, including diving, shipwreck exploration, cycling, hiking, and wildlife safaris, is gaining strong attention. Sri Lanka’s rich marine life and historic shipwrecks are attracting certified divers and adventure enthusiasts from around the world.
                </p>
            </div>

        </div>

        <!-- Our Perspective -->
        <div class="perspective-box">
            <h3 style="font-family: 'Playfair Display', serif; font-size: 26px; color: #333; margin-top: 0; margin-bottom: 15px;">Our Perspective</h3>
            <p style="color: #555; font-size: 16px; line-height: 1.7; margin-bottom: 25px;">
                At Travel with IS Tours, we closely follow these developments to design travel experiences that align with current trends while maintaining high standards of comfort, safety, and authenticity. Our tours are crafted to reflect the evolving travel landscape and deliver exceptional value to our guests.
            </p>
            <p style="font-family: 'Playfair Display', serif; font-size: 22px; color: #ff1a4a; font-style: italic; margin: 0; font-weight: 600;">
                "Discover Sri Lanka. Experience Paradise. Travel with IS Tours."
            </p>
        </div>

        <style>
            .trends-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 30px;
            }
            .trend-card {
                background: #fff;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.04);
                border: 1px solid #eee;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                display: flex;
                flex-direction: column;
            }
            .trend-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            }
            .trend-icon {
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px; /* Slight rounded square for variety */
                font-size: 24px;
                margin-bottom: 20px;
            }
            .trend-title {
                font-family: 'Playfair Display', serif;
                font-size: 20px;
                color: #333;
                margin-bottom: 15px;
                margin-top: 0;
                line-height: 1.3;
            }
            .trend-desc {
                color: #666;
                font-size: 14px;
                line-height: 1.6;
                margin: 0;
            }
            .perspective-box {
                margin-top: 40px;
                background: #fff;
                padding: 40px;
                border-radius: 12px;
                border-left: 5px solid #ff1a4a;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            }

            @media (max-width: 992px) {
                .trends-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            @media (max-width: 768px) {
                .trends-grid {
                    grid-template-columns: 1fr;
                }
                .perspective-box {
                    padding: 25px;
                }
            }
        </style>

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
    <div style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 450px; max-width: 40%; pointer-events: none; z-index: 1;">
        <img src="assets/images/custom-tour-art.png" alt="Customized Tour Art" style="width: 100%; height: auto; object-fit: contain;">
    </div>
</section>




<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
