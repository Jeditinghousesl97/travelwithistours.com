<?php
require_once 'config/db.php';
include 'includes/header.php';

// Fetch Tour Packages for Dropdown
$tours = $pdo->query("SELECT id, name FROM tours ORDER BY name ASC")->fetchAll();

// Fetch Tour Packages for Slider
$slider_tours = $pdo->query("SELECT * FROM tours ORDER BY is_featured DESC, created_at DESC")->fetchAll();
?>

<!-- Page Header -->
<section class="page-header booking-page-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/headers/booking-inquiry.webp'); padding: 150px 0; text-align: center; color: #fff; background-size: cover; background-position: center;">
    <div class="container">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; margin-bottom: 10px;">Plan Your Journey</h1>
        <p style="font-size: 18px;">Let us craft your perfect Sri Lankan experience.</p>
    </div>
</section>

<!-- Booking Form Section -->
<section class="section" style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <div class="booking-container" style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;">
                    <h3>Thank You!</h3>
                    <p>Your booking inquiry has been received. Our team will contact you shortly.</p>
                </div>
            <?php
else: ?>

            <style>
                .form-group { margin-bottom: 25px; }
                .form-label { font-weight: 600; margin-bottom: 8px; display: block; color: #444; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
                .form-control { width: 100%; padding: 15px; border: 1px solid #e1e1e1; border-radius: 8px; background-color: #f9f9f9; color: #333; font-size: 15px; transition: all 0.3s; box-sizing: border-box; }
                .form-control:focus { border-color: var(--accent-color); background-color: #fff; outline: none; box-shadow: 0 0 0 4px rgba(0,0,0,0.03); }
                .section-title { font-family: 'Playfair Display', serif; font-size: 26px; margin-bottom: 30px; position: relative; padding-left: 20px; border-left: 4px solid var(--accent-color); color: #222; line-height: 1; }
                .btn-next, .btn-submit { padding: 12px 30px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; border: none; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
                .btn-next { background: var(--accent-color); color: #fff; }
                .btn-submit { background-color: #28a745; color: #fff; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
                .btn-prev { background: #eee; color: #555; padding: 12px 30px; border-radius: 50px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; border: none; cursor: pointer; transition: 0.3s; }
                .btn-prev:hover { background: #ddd; }
                .btn-next:hover { background: #333; }
                .btn-submit:hover { background-color: #218838; transform: translateY(-2px); }
                
                /* Mobile Fixes */
                .booking-form-grid {
                    display: grid;
                    grid-template-columns: 1fr 3fr;
                    gap: 20px;
                }
                .booking-form-grid-2 {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                }
                
                @media (max-width: 600px) {
                    .booking-form-grid, .booking-form-grid-2 {
                        grid-template-columns: 1fr !important;
                        gap: 10px !important;
                    }
                    .booking-container {
                        padding: 25px 20px !important;
                    }
                    .section-title {
                        font-size: 22px !important;
                    }
                    .btn-next, .btn-submit, .btn-prev {
                        width: 100%;
                        justify-content: center;
                    }
                    .booking-step-footer {
                        flex-direction: column;
                        gap: 10px;
                    }
                    .btn-prev {order: 2;}
                    .btn-next, .btn-submit {order: 1;}
                }
            </style>

            <form action="process-booking.php" method="POST" id="bookingForm">
                
                <!-- Step 1: Your Information -->
                <div class="form-step active" id="step1">
                    <h2 class="section-title">Your Information</h2>
                    
                    <div class="booking-form-grid">
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-control">
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Prof.">Prof.</option>
                                <option value="Rev.">Rev.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" required class="form-control" placeholder="Enter your full name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" required class="form-control" placeholder="Your country of residence">
                    </div>

                    <div class="booking-form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" required class="form-control" placeholder="example@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" required class="form-control" placeholder="+1 (123) 456-7890">
                        </div>
                    </div>

                    <?php
    // Fetch Turnstile Key
    $settings_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cf_site_key'");
    $site_key = $settings_stmt->fetchColumn();
    if ($site_key):
?>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($site_key); ?>" style="margin-bottom: 25px;"></div>
                    <?php
    endif; ?>

                    <div class="booking-step-footer" style="display: flex; justify-content: flex-end; margin-top: 10px;">
                        <button type="button" class="btn-next" onclick="nextStep(1)">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 2: Travel Expectations -->
                <div class="form-step" id="step2" style="display: none;">
                    <h2 class="section-title">Travel Expectations</h2>
                    
                    <div class="booking-form-grid-2">
                        <div class="form-group">
                            <label class="form-label">Travel Date</label>
                            <input type="date" name="travel_date" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Number of Nights</label>
                            <input type="number" name="nights" min="1" class="form-control" placeholder="e.g. 7">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Number of Adults</label>
                        <input type="number" name="adults" min="1" class="form-control" placeholder="e.g. 2">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Preferred Tour Package</label>
                        <select name="tour_package" class="form-control">
                            <option value="">Select a Package (Optional)</option>
                            <?php foreach ($tours as $tour): ?>
                                <option value="<?php echo htmlspecialchars($tour['name']); ?>"><?php echo htmlspecialchars($tour['name']); ?></option>
                            <?php
    endforeach; ?>
                            <option value="Custom">I want a Custom Tour</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Need Accommodation Arrangements?</label>
                        <div style="margin-top: 12px; display: flex; gap: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;"><input type="radio" name="accommodation" value="Yes" checked style="accent-color: var(--accent-color);"> Yes</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;"><input type="radio" name="accommodation" value="No" style="accent-color: var(--accent-color);"> No</label>
                        </div>
                    </div>

                    <div class="booking-step-footer" style="display: flex; justify-content: space-between; margin-top: 30px;">
                        <button type="button" class="btn-prev" onclick="prevStep(2)">Back</button>
                        <button type="button" class="btn-next" onclick="nextStep(2)">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 3: Last Details -->
                <div class="form-step" id="step3" style="display: none;">
                    <h2 class="section-title">Almost Done</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Special Notes / Requirements</label>
                        <textarea name="special_notes" rows="5" class="form-control" placeholder="Tell us about any specific interests, dietary requirements, or questions you have..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label">Preferred Contact Method</label>
                        <div style="margin-top: 12px; display: flex; gap: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;"><input type="radio" name="contact_method" value="Phone Call" style="accent-color: var(--accent-color);"> Phone Call</label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;"><input type="radio" name="contact_method" value="Email" checked style="accent-color: var(--accent-color);"> Email</label>
                        </div>
                    </div>

                    <div class="booking-step-footer" style="display: flex; justify-content: space-between; margin-top: 30px;">
                        <button type="button" class="btn-prev" onclick="prevStep(3)">Back</button>
                        <button type="submit" class="btn-submit">Submit Inquiry</button>
                    </div>
                </div>

            </form>
            <?php
endif; ?>
        </div>
    </div>
</section>

<!-- Tour Packages Slider Section -->
<section class="section" style="padding: 80px 0; background-color: #f9f9f9; border-top: 1px solid #eee;">
    <div class="container">
        <h2 class="section-title" style="text-align: center; border-left: none; padding-left: 0; font-size: 36px; margin-bottom: 50px;">Explore Our Packages</h2>
        
        <!-- Swiper CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

        <div class="swiper tour-swiper" style="padding-bottom: 50px;">
            <div class="swiper-wrapper">
                <?php foreach ($slider_tours as $tour):
    $img = $tour['thumbnail'] ? $tour['thumbnail'] : 'https://placehold.co/400x300?text=No+Image';
?>
                <div class="swiper-slide" style="height: auto;">
                    <div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); height: 100%; display: flex; flex-direction: column; transition: transform 0.3s;">
                        <div style="position: relative; height: 200px;">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php if ($tour['is_featured']): ?>
                                <div style="position: absolute; top: 15px; left: 15px; background: #fff; color: #333; padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">Featured</div>
                            <?php
    endif; ?>
                            <?php if ($tour['duration']): ?>
                                <span style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.6); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px;">
                                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($tour['duration']); ?>
                                </span>
                            <?php
    endif; ?>
                        </div>
                        
                        <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                            <?php if ($tour['tour_type']): ?>
                                <span style="font-size: 11px; text-transform: uppercase; color: #00838f; margin-bottom: 5px; font-weight: 600;"><?php echo htmlspecialchars($tour['tour_type']); ?></span>
                            <?php
    endif; ?>
                            
                            <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #333; line-height: 1.3;"><?php echo htmlspecialchars($tour['name']); ?></h3>
                            
                            <?php if ($tour['sub_heading']): ?>
                                <p style="font-size: 13px; color: #666; margin-bottom: 15px; line-height: 1.5; flex-grow: 1;"><?php echo htmlspecialchars(mb_strimwidth($tour['sub_heading'], 0, 80, "...")); ?></p>
                            <?php
    endif; ?>
                            
                            <div style="margin-top: auto; border-top: 1px solid #f4f4f4; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #ff1a4a; font-weight: 700; font-size: 15px;"><?php echo htmlspecialchars($tour['price']); ?></span>
                                <a href="tour-details.php?id=<?php echo $tour['id']; ?>" style="color: var(--accent-color); text-decoration: none; font-size: 13px; font-weight: 600;">View Details <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>






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





<!-- Google Map -->
<?php
// Fetch map URL from settings
$map_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126743.58585979667!2d79.80922114674345!3d6.9271139436440265!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae253d10f7a7003%3A0x320b2e4d32d3838d!2sColombo!5e0!3m2!1sen!2slk!4v1645000000000!5m2!1sen!2slk';
try {
    $stmt_map = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'contact_map_url'");
    $fetched_map_url = $stmt_map->fetchColumn();
    if ($fetched_map_url) {
        $map_url = $fetched_map_url;
    }
}
catch (Exception $e) {
}
?>
<section style="height: 600px; background: #eee;">
    <iframe src="<?php echo htmlspecialchars($map_url); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</section>















<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Swiper('.tour-swiper', {
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
    });
</script>

<script>
    function nextStep(currentStep) {
        // Simple validation only for required fields in current step
        // In a real app, use more robust validation
        if(currentStep === 1) {
            var name = document.getElementsByName('name')[0].value;
            var email = document.getElementsByName('email')[0].value;
            if(!name || !email) {
                alert('Please fill in required fields (Name, Email).');
                return;
            }
        }
        
        document.getElementById('step' + currentStep).style.display = 'none';
        document.getElementById('step' + (currentStep + 1)).style.display = 'block';
        window.scrollTo(0, 0); // Scroll to top
    }

    function prevStep(currentStep) {
        document.getElementById('step' + currentStep).style.display = 'none';
        document.getElementById('step' + (currentStep - 1)).style.display = 'block';
        window.scrollTo(0, 0);
    }
</script>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
