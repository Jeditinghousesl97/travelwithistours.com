<?php

require_once 'config/db.php';

include 'includes/header.php';

?>



<!-- Page Header -->
<!-- Page Header -->
<section class="page-header gallery-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/gallery.webp'); background-color: #f4f4f4; padding: 100px 0; text-align: center; background-size: cover; background-position: center; position: relative;">
    <div class="container" style="position: relative; z-index: 2;">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #fff; margin-bottom: 10px;">Gallery & Testimonials</h1>
        <p style="color: #eee; font-size: 18px; max-width: 600px; margin: 0 auto;">See what our guests say and browse through our travel memories.</p>
    </div>
</section>

<style>
    @media (max-width: 992px) {
        /* Mobile Header Optimization */
        .gallery-page-header {
            height: 60vh !important;
            padding: 0 !important;
            display: flex !important;
            align-items: flex-end !important; /* Bottom */
            justify-content: center !important; /* Center */
        }
        .gallery-page-header .container {
            padding-bottom: 60px; /* Spacing from bottom */
            width: 100%;
        }
    }
</style>



<!-- Testimonials Section -->
<section class="section" style="padding-top: 80px; padding-bottom: 80px; background-color: #fff;">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 20px; font-size: 33px; font-family: 'Playfair Display', serif;">Guest Love</h2>
        <p style="text-align: center; max-width: 800px; margin: 0 auto 40px auto; color: #555; line-height: 1.6;">
            Discover why our guests fall in love with Sri Lanka. Read authentic reviews and heartwarming stories from travelers who have experienced our bespoke tours and signature hospitality.
        </p>
        <div class="swiper testimonials-swiper" style="padding-bottom: 50px;">
            <div class="swiper-wrapper">
                <?php
// Fetch Testimonials
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC")->fetchAll();

if (count($testimonials) > 0):
    foreach ($testimonials as $testimonial):
?>
                <div class="swiper-slide" style="height: auto;">
                    <?php if (!empty($testimonial['link'])): ?>
                    <a href="<?php echo htmlspecialchars($testimonial['link']); ?>" target="_blank" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                    <?php
        endif; ?>
                        <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); height: 100%; display: flex; flex-direction: column; transition: transform 0.3s;">
                            <div style="color: #ffd700; margin-bottom: 10px;">
                                <?php for ($i = 0; $i < $testimonial['rating']; $i++)
            echo '<i class="fas fa-star"></i>'; ?>
                            </div>
                            <p style="font-style: italic;">"<?php echo htmlspecialchars($testimonial['text']); ?>"</p>
                            <div style="margin-top: 20px; display: flex; align-items: center;">
                                <img src="<?php echo $testimonial['image'] ? htmlspecialchars($testimonial['image']) : 'https://placehold.co/50x50?text=Guest'; ?>" style="border-radius: 50%; margin-right: 15px; width:50px; height:50px; object-fit:cover;">
                                <div>
                                    <h4 style="margin: 0; font-size: 16px;"><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                                    <span style="font-size: 12px; color: #777;"><?php echo htmlspecialchars($testimonial['location']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php if (!empty($testimonial['link'])): ?>
                    </a>
                    <?php
        endif; ?>
                </div>
                <?php
    endforeach;
else: ?>
                    <div class="swiper-slide"><div style="text-align: center; color: #777;">No testimonials yet. Add them in the Admin Panel.</div></div>
                <?php
endif; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>


<!-- Reviews Platform Slider -->
<section style="padding: 60px 0; background: #fff; border-bottom: 1px solid #f9f9f9;">
    <div class="container">
        <div class="swiper reviews-swiper">
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

if (count($partners) > 0):
    foreach ($partners as $partner):
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
        </div>
    </div>
</section>


<!-- Gallery Section -->
<?php
// Fetch Gallery Images
$gallery_images = $pdo->query("SELECT * FROM gallery ORDER BY is_featured DESC, created_at DESC")->fetchAll();
?>
<section class="section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 40px; font-family: 'Playfair Display', serif; font-size: 36px;">Our Gallery</h2>
        
        <?php if (count($gallery_images) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                <?php foreach ($gallery_images as $img): ?>
                <div style="position: relative; overflow: hidden; border-radius: 8px; height: 250px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); group;">
                    <a href="<?php echo htmlspecialchars($img['image_path']); ?>" class="gallery-lightbox" data-caption="<?php echo htmlspecialchars($img['caption'] ?: $img['alt_text']); ?>">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($img['alt_text'] ?: 'GPS Lanka Travels Gallery Image'); ?>" 
                             title="<?php echo htmlspecialchars($img['caption'] ?: $img['alt_text']); ?>"
                             loading="lazy"
                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; cursor: pointer;">
                    </a>
                    
                    <?php if ($img['is_featured']): ?>
                        <div style="position: absolute; top: 10px; right: 10px; background: var(--accent-color); color: #fff; padding: 5px 10px; font-size: 10px; font-weight: bold; border-radius: 4px; text-transform: uppercase;">Featured</div>
                    <?php
        endif; ?>
                </div>
                <?php
    endforeach; ?>
            </div>
        <?php
else: ?>
            <p style="text-align: center; color: #777;">No images in the gallery yet.</p>
        <?php
endif; ?>
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




<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<!-- SimpleLightbox CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.2/simple-lightbox.min.css" rel="stylesheet" />


<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if(document.querySelector('.testimonials-swiper')) {
            new Swiper('.testimonials-swiper', {
                slidesPerView: 1,
                spaceBetween: 30,
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                breakpoints: {
                    768: { slidesPerView: 2 },
                    1024: { slidesPerView: 3 },
                }
            });
        }

        if(document.querySelector('.reviews-swiper')) {
            new Swiper('.reviews-swiper', {
                slidesPerView: 2,
                spaceBetween: 30,
                centeredSlides: true,
                loop: true,
                autoplay: {
                    delay: 2500,
                    disableOnInteraction: false,
                },
                breakpoints: {
                    640: { slidesPerView: 3, centeredSlides: true },
                    768: { slidesPerView: 4, centeredSlides: false },
                    1024: { slidesPerView: 5, centeredSlides: false },
                }
            });
        }
    });
</script>

<!-- SimpleLightbox JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.2/simple-lightbox.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var lightbox = new SimpleLightbox('.gallery-lightbox', { 
            captionsData: 'alt',
            captionDelay: 250,
            animationSpeed: 200,
            fadeSpeed: 200,
        });
    });
</script>
