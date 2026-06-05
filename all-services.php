<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch All Services
$stmt = $pdo->query("SELECT * FROM services ORDER BY display_order ASC");
$services = $stmt->fetchAll();
?>

<!-- Hero Section for Page -->
<!-- Hero Section for Page -->
<section class="page-header services-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/all-tour-packages-page.webp'); background-color: #f4f4f4; padding: 100px 0; text-align: center; background-size: cover; background-position: center; position: relative;">
    <div class="container" style="position: relative; z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #fff; margin-bottom: 10px;">Our Services</h1>
        <p style="color: #eee; font-size: 18px; max-width: 600px; margin: 0 auto;">Comprehensive travel solutions for your Sri Lankan journey</p>
    </div>
</section>

<section class="section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        
        <?php if (count($services) > 0): ?>
            <div class="services-grid">
                <?php foreach ($services as $svc):
        $img = $svc['icon'] ? $svc['icon'] : 'https://placehold.co/400x300?text=Service';
?>
                <div class="service-card-page">
                    <div class="service-img-page">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($svc['name']); ?>">
                    </div>
                    <div class="service-content-page">
                        <h3><?php echo htmlspecialchars($svc['name']); ?></h3>
                        <p><?php echo htmlspecialchars(mb_strimwidth($svc['short_description'], 0, 200, "...")); ?></p>
                        <a href="service-details.php?id=<?php echo $svc['id']; ?>" class="read-more-btn">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php
    endforeach; ?>
            </div>
        <?php
else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <h3>No services available at the moment.</h3>
            </div>
        <?php
endif; ?>

    </div>
</section>

<style>
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }
    
    @media (max-width: 992px) {
        .services-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .services-grid { grid-template-columns: 1fr; }
    }

    .service-card-page {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        transition: transform 0.3s;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .service-card-page:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .service-img-page {
        height: 200px;
        overflow: hidden;
    }
    .service-img-page img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s;
    }
    .service-card-page:hover .service-img-page img {
        transform: scale(1.05);
    }

    .service-content-page {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .service-content-page h3 {
        margin: 0 0 15px 0;
        font-family: 'Playfair Display', serif;
        font-size: 22px;
        color: var(--text-primary);
    }

    .service-content-page p {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
    }

    .read-more-btn {
        color: var(--accent-color);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: 0.2s;
        margin-top: auto;
    }
    .read-more-btn:hover {
        color: #000; /* Darker shade on hover */
        gap: 8px;
    }
</style>


<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
