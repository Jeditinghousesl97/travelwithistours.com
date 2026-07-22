<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'includes/tripadvisor_reviews.php';

ensure_tripadvisor_reviews_schema($pdo);

$page_title = 'Write a Review';
$page_description = 'Share your Travel with IS Tours experience and help future guests plan their Sri Lankan journey.';

if (empty($_SESSION['review_csrf_token'])) {
    $_SESSION['review_csrf_token'] = bin2hex(random_bytes(32));
}

$review_errors = $_SESSION['review_form_errors'] ?? [];
$review_old = $_SESSION['review_form_old'] ?? [];
$review_submitted = !empty($_SESSION['review_submitted']);
unset($_SESSION['review_form_errors'], $_SESSION['review_form_old'], $_SESSION['review_submitted']);

$turnstile_site_key = '';
try {
    $turnstile_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $turnstile_stmt->execute(['cf_site_key']);
    $turnstile_site_key = trim((string) ($turnstile_stmt->fetchColumn() ?: ''));
} catch (Throwable $e) {
    $turnstile_site_key = '';
}

function review_old_value(array $values, string $key, string $default = ''): string
{
    return htmlspecialchars((string) ($values[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

include 'includes/header.php';
?>

<section class="page-header" style="background-image: linear-gradient(rgba(0,0,0,0.54), rgba(0,0,0,0.54)), url('assets/images/headers/gallery.webp'); background-color: #333; padding: 110px 0; text-align: center; background-size: cover; background-position: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: 'Playfair Display', serif; font-size: 48px; line-height: 1.15; margin-bottom: 12px;">Share Your Experience</h1>
        <p style="max-width: 650px; margin: 0 auto; font-size: 18px; color: #fff;">Your story can help another traveler discover Sri Lanka with confidence.</p>
    </div>
</section>

<main class="review-submit-page">
    <div class="container">
        <?php if ($review_submitted): ?>
            <div class="review-success-card">
                <div class="review-success-icon"><i class="fas fa-check"></i></div>
                <h2>Thank you for your review!</h2>
                <p>Your experience has been published in our homepage reviews section. We truly appreciate you taking the time to share it.</p>
                <div class="review-success-actions">
                    <a class="review-submit-button" href="index.php#tripadvisor-reviews">View your review</a>
                    <a class="tripadvisor-write-review-btn" href="review.php">Write another review</a>
                </div>
            </div>
        <?php else: ?>
            <div class="review-submit-layout">
                <div class="review-form-card" id="review-form">
                    <div class="review-form-intro">
                        <h2>Tell us about your trip</h2>
                        <p>Share the details future guests would find helpful. Fields marked with * are required.</p>
                    </div>

                    <?php if (!empty($review_errors)): ?>
                        <div class="review-form-errors" role="alert">
                            <strong>Please check the following:</strong>
                            <ul>
                                <?php foreach ($review_errors as $error): ?>
                                    <li><?php echo htmlspecialchars((string) $error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="process-review.php" method="POST" enctype="multipart/form-data" id="guestReviewForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['review_csrf_token']); ?>">

                        <div class="review-honeypot" aria-hidden="true">
                            <label for="reviewWebsite">Website</label>
                            <input type="text" id="reviewWebsite" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="review-form-grid">
                            <div class="review-field">
                                <label for="reviewerName">Reviewer name <span class="required">*</span></label>
                                <input class="review-input" id="reviewerName" type="text" name="reviewer_name" maxlength="150" autocomplete="name" required value="<?php echo review_old_value($review_old, 'reviewer_name'); ?>" placeholder="Your name">
                            </div>

                            <div class="review-field">
                                <label for="reviewerLocation">Location <span class="required">*</span></label>
                                <input class="review-input" id="reviewerLocation" type="text" name="reviewer_location" maxlength="150" autocomplete="address-level2" required value="<?php echo review_old_value($review_old, 'reviewer_location'); ?>" placeholder="London, United Kingdom">
                            </div>
                        </div>

                        <div class="review-form-grid">
                            <div class="review-field">
                                <label for="reviewTitle">Review title <span class="required">*</span></label>
                                <input class="review-input" id="reviewTitle" type="text" name="review_title" maxlength="255" required value="<?php echo review_old_value($review_old, 'review_title'); ?>" placeholder="Sum up your experience">
                            </div>

                            <div class="review-field">
                                <label for="tripDate">Trip date <span class="required">*</span></label>
                                <input class="review-input" id="tripDate" type="date" name="trip_date" max="<?php echo date('Y-m-d'); ?>" required value="<?php echo review_old_value($review_old, 'trip_date'); ?>">
                            </div>
                        </div>

                        <?php $selected_rating = max(1, min(5, (int) ($review_old['rating'] ?? 5))); ?>
                        <fieldset class="review-field" style="border: 0; padding: 0;">
                            <legend class="review-rating-label">How would you rate your experience? <span class="required">*</span></legend>
                            <div class="review-rating-options" id="reviewRatingOptions" aria-label="Rating from 1 to 5">
                                <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                    <label class="review-rating-option <?php echo $rating <= $selected_rating ? 'is-selected' : ''; ?>" title="<?php echo $rating; ?> out of 5">
                                        <input type="radio" name="rating" value="<?php echo $rating; ?>" <?php echo $rating === $selected_rating ? 'checked' : ''; ?> required>
                                        <span class="review-rating-dot-choice"></span>
                                    </label>
                                <?php endfor; ?>
                                <strong id="reviewRatingText"><?php echo $selected_rating; ?> out of 5</strong>
                            </div>
                        </fieldset>

                        <div class="review-field">
                            <label for="reviewText">Your review <span class="required">*</span></label>
                            <textarea class="review-textarea" id="reviewText" name="review_text" minlength="20" maxlength="5000" required placeholder="What made your experience memorable? Tell future travelers about the service, itinerary, driver, accommodation, or places you visited."><?php echo review_old_value($review_old, 'review_text'); ?></textarea>
                            <span class="review-field-help"><span id="reviewCharacterCount">0</span> / 5,000 characters</span>
                        </div>

                        <div class="review-field">
                            <label for="reviewPhotos">Add photos <span style="font-weight: 400; color: #666;">(optional)</span></label>
                            <div class="review-photo-dropzone" id="reviewPhotoDropzone">
                                <i class="far fa-images"></i>
                                <strong>Choose up to 5 photos</strong>
                                <span>JPG, PNG or WebP &middot; Maximum 5 MB each</span>
                                <input class="review-photo-input" id="reviewPhotos" type="file" name="review_photos[]" accept="image/jpeg,image/png,image/webp" multiple>
                            </div>
                            <div class="review-photo-previews" id="reviewPhotoPreviews" aria-live="polite"></div>
                            <span class="review-field-help" id="reviewPhotoMessage"></span>
                        </div>

                        <?php if ($turnstile_site_key !== ''): ?>
                            <div class="review-field">
                                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstile_site_key); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <p class="review-privacy-note" style="border-top: 0; padding-top: 0; margin-bottom: 22px;">By submitting, you confirm this is your genuine experience and agree that your review and uploaded photos may be published on this website.</p>

                        <button class="review-submit-button" type="submit">
                            Submit your review <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <aside class="review-guidance-card">
                    <h3>What makes a helpful review?</h3>
                    <ul>
                        <li>Describe your first-hand travel experience.</li>
                        <li>Mention the service, route, or destinations you enjoyed.</li>
                        <li>Share useful details for future travelers.</li>
                        <li>Keep personal or sensitive information private.</li>
                    </ul>
                    <p class="review-privacy-note">Your review appears automatically in the homepage review slider after a successful submission.</p>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php if ($turnstile_site_key !== '' && !$review_submitted): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<?php if (!$review_submitted): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ratingOptions = document.querySelectorAll('.review-rating-option');
    const ratingText = document.getElementById('reviewRatingText');
    const reviewText = document.getElementById('reviewText');
    const characterCount = document.getElementById('reviewCharacterCount');
    const photoInput = document.getElementById('reviewPhotos');
    const photoPreviews = document.getElementById('reviewPhotoPreviews');
    const photoMessage = document.getElementById('reviewPhotoMessage');
    const dropzone = document.getElementById('reviewPhotoDropzone');

    function updateRating(selectedValue) {
        ratingOptions.forEach(function (option, index) {
            option.classList.toggle('is-selected', index < selectedValue);
        });
        ratingText.textContent = selectedValue + ' out of 5';
    }

    ratingOptions.forEach(function (option) {
        option.querySelector('input').addEventListener('change', function () {
            updateRating(Number(this.value));
        });
    });

    function updateCharacterCount() {
        characterCount.textContent = reviewText.value.length.toLocaleString();
    }

    reviewText.addEventListener('input', updateCharacterCount);
    updateCharacterCount();

    photoInput.addEventListener('change', function () {
        photoPreviews.innerHTML = '';
        photoMessage.textContent = '';
        const files = Array.from(photoInput.files);

        if (files.length > 5) {
            photoMessage.textContent = 'Please choose no more than 5 photos.';
            photoInput.value = '';
            return;
        }

        files.forEach(function (file) {
            const image = document.createElement('img');
            image.className = 'review-photo-preview';
            image.alt = 'Selected review photo preview';
            image.src = URL.createObjectURL(file);
            image.addEventListener('load', function () {
                URL.revokeObjectURL(image.src);
            });
            photoPreviews.appendChild(image);
        });

        if (files.length > 0) {
            photoMessage.textContent = files.length + (files.length === 1 ? ' photo selected.' : ' photos selected.');
        }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function () {
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function () {
            dropzone.classList.remove('is-dragover');
        });
    });
});
</script>
<?php endif; ?>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
