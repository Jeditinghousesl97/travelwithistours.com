<?php

require_once 'config/db.php';
require_once 'includes/short_videos.php';
require_once 'includes/tripadvisor_reviews.php';
ensure_short_videos_schema($pdo);
ensure_tripadvisor_reviews_schema($pdo);

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
            <div class="swiper-pagination testimonials-swiper-pagination"></div>
        </div>
    </div>


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
                             alt="<?php echo htmlspecialchars($img['alt_text'] ?: 'Travel with IS Tours Gallery Image'); ?>" 
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

        if(document.querySelector('.testimonials-swiper')) {
            new Swiper('.testimonials-swiper', {
                slidesPerView: 1,
                spaceBetween: 30,
                pagination: {
                    el: '.testimonials-swiper-pagination',
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

        new SimpleLightbox('.tripadvisor-review-photo-link', {
            captions: false,
            animationSpeed: 200,
            fadeSpeed: 200,
        });
    });
</script>

