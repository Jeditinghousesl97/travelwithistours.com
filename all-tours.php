<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch Categories for Filter
$catStmt = $pdo->query("SELECT * FROM categories WHERE type = 'tour' ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Pagination Logic
$limit = 18;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter Logic
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

$whereClause = "";
$params = [];

if ($category_filter != 'all') {
    // Modify query to filter by category slug
    // We need a complex join for category filter
    $whereClause = "WHERE c.slug = ?";
    $params[] = $category_filter;
}

// Main Query with JOINs
// Note: We need GROUP BY because of left joins, but simpler if we filter first.
// Let's use a subquery or distinct approach.
// Best approach: Use GROUP BY t.id
$sql = "SELECT t.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_names 
        FROM tours t 
        LEFT JOIN tour_categories tc ON t.id = tc.tour_id 
        LEFT JOIN categories c ON tc.category_id = c.id 
        $whereClause 
        GROUP BY t.id 
        ORDER BY t.is_featured DESC, t.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// Check if more
// A simple way to check if more exist is to count total
$countSql = "SELECT COUNT(DISTINCT t.id) 
             FROM tours t 
             LEFT JOIN tour_categories tc ON t.id = tc.tour_id 
             LEFT JOIN categories c ON tc.category_id = c.id 
             $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_tours = $countStmt->fetchColumn();
$total_pages = ceil($total_tours / $limit);

?>

<!-- Hero Section for Page -->
<!-- Hero Section for Page -->
<section class="page-header all-tours-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/all-tour-packages-page.webp'); background-color: #f4f4f4; padding: 100px 0; text-align: center; background-size: cover; background-position: center; position: relative;">
    <div class="container" style="position: relative; z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #fff; margin-bottom: 10px;">All Tour Packages</h1>
        <p style="color: #eee; font-size: 18px; max-width: 600px; margin: 0 auto;">Discover the best of Sri Lanka with our curated tours</p>
    </div>
</section>

<section class="section" style="padding: 60px 0; background-color: #f9f9f9;">
    <div class="container">
        
        <!-- Category Filter -->
        <div class="tour-filter" style="text-align: center; margin-bottom: 40px;">
            <a href="?category=all" class="filter-btn <?php echo $category_filter == 'all' ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat['slug']; ?>" class="filter-btn <?php echo $category_filter == $cat['slug'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php
endforeach; ?>
        </div>

        <!-- Tours Grid -->
        <?php if (count($tours) > 0): ?>
            <div class="tours-grid">
                <?php foreach ($tours as $tour):
        $img = $tour['thumbnail'] ? $tour['thumbnail'] : 'https://placehold.co/400x300?text=No+Image';
?>
                <div class="tour-card">
                    <div class="tour-img-wrap">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>">
                        <?php if ($tour['is_featured']): ?>
                            <span class="featured-tag"><i class="fas fa-star"></i> Featured</span>
                        <?php
        endif; ?>
                        <?php if ($tour['duration']): ?>
                            <span class="duration-tag"><i class="far fa-clock"></i> <?php echo htmlspecialchars($tour['duration']); ?></span>
                        <?php
        endif; ?>
                    </div>
                    <div class="tour-content">
                        <div class="tour-meta">
                            <?php if ($tour['tour_type']): ?>
                                <span class="tour-type"><i class="fas fa-suitcase"></i> <?php echo htmlspecialchars($tour['tour_type']); ?></span>
                            <?php
        endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($tour['name']); ?></h3>
                        <?php if ($tour['sub_heading']): ?>
                            <p class="tour-desc"><?php echo htmlspecialchars(mb_strimwidth($tour['sub_heading'], 0, 100, "...")); ?></p>
                        <?php
        endif; ?>
                        
                        <div class="tour-footer">
                            <div class="tour-price"><?php echo htmlspecialchars($tour['price']); ?></div>
                            <a href="tour-details.php?id=<?php echo $tour['id']; ?>" class="btn-view">View Details</a>
                        </div>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>

            <!-- "Load More" / Pagination -->
            <!-- Note: The user requested a "Load More" button. 
                 Implementing true AJAX Load More requires a separate endpoint or JS logic. 
                 For now, let's do a simple Next Page link styled as a button, or if specific ajax is needed we can add it.
                 User said "load more button for pagination". A "Next Page" button is the simplest form of this.
            -->
            <?php if ($page < $total_pages): ?>
            <div style="text-align: center; margin-top: 50px;">
                <a href="?category=<?php echo $category_filter; ?>&page=<?php echo $page + 1; ?>" class="btn" style="padding: 12px 40px;">Load More Tours</a>
            </div>
            <?php
    endif; ?>

        <?php
else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>No tours found in this category.</h3>
                <a href="all-tours.php" class="btn" style="margin-top: 20px;">View All Tours</a>
            </div>
        <?php
endif; ?>

    </div>
</section>

<style>
    /* Page Specific Styles */
    .filter-btn {
        display: inline-block;
        padding: 8px 20px;
        margin: 5px;
        border: 1px solid #ddd;
        border-radius: 30px;
        color: #555;
        text-decoration: none;
        transition: 0.3s;
        background: #fff;
    }
    .filter-btn:hover, .filter-btn.active {
        background: var(--accent-color);
        color: #fff;
        border-color: var(--accent-color);
    }

    .tours-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 Columns */
        gap: 30px;
    }
    
    @media (max-width: 992px) {
        .tours-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .tours-grid { grid-template-columns: 1fr; }
    }

    .tour-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); /* Lighter shadow */
        transition: transform 0.3s;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
    }
    .tour-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .tour-img-wrap {
        position: relative;
        height: 220px;
        overflow: hidden;
    }
    .tour-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s;
    }
    .tour-card:hover .tour-img-wrap img {
        transform: scale(1.05);
    }

    .featured-tag {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #fff;
        color: var(--text-primary);
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .duration-tag {
        position: absolute;
        bottom: 15px;
        right: 15px;
        background: rgba(0,0,0,0.7);
        color: #fff;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
    }

    .tour-content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .tour-meta {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .tour-type, .tour-cat {
        background: #f0f8ff;
        color: #00bcd4;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    .tour-cat {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .tour-card h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
        color: var(--text-primary);
    }
    
    .tour-desc {
        color: #777;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .tour-footer {
        margin-top: auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
    }
    
    .tour-price {
        color: #ed2196;
        font-weight: 700;
        font-size: 16px;
    }
    
    .btn-view {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        border: 1px solid var(--accent-color);
        padding: 6px 15px;
        border-radius: 20px;
        transition: 0.2s;
    }
    .btn-view:hover {
        background: var(--accent-color);
        color: #fff;
    }
</style>


<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
