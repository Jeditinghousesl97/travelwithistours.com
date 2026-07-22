<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'includes/tripadvisor_reviews.php';

ensure_tripadvisor_reviews_schema($pdo);

$page_title = 'Write a Review';
$page_description = 'Share your Travel with IS Tours experience and help future guests plan their Sri Lankan journey.';
$body_class = 'review-submission-page';

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

<br>
<br>
<br>

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

                    <div class="review-form-errors" id="reviewFormErrors" role="alert" <?php echo empty($review_errors) ? 'hidden' : ''; ?>>
                        <strong>Please check the following:</strong>
                        <ul id="reviewFormErrorList">
                            <?php foreach ($review_errors as $error): ?>
                                <li><?php echo htmlspecialchars((string) $error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

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

                        <div class="review-upload-progress" id="reviewUploadProgress" hidden aria-live="polite">
                            <div class="review-upload-progress-meta">
                                <span id="reviewUploadStatus">Uploading photos...</span>
                                <strong id="reviewUploadPercent">0%</strong>
                            </div>
                            <div class="review-upload-progress-track" id="reviewUploadProgressTrack" role="progressbar" aria-label="Photo upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <span id="reviewUploadProgressBar"></span>
                            </div>
                        </div>

                        <button class="review-submit-button" id="reviewSubmitButton" type="submit">
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
    const reviewForm = document.getElementById('guestReviewForm');
    const submitButton = document.getElementById('reviewSubmitButton');
    const formErrors = document.getElementById('reviewFormErrors');
    const formErrorList = document.getElementById('reviewFormErrorList');
    const uploadProgress = document.getElementById('reviewUploadProgress');
    const uploadStatus = document.getElementById('reviewUploadStatus');
    const uploadPercent = document.getElementById('reviewUploadPercent');
    const uploadProgressTrack = document.getElementById('reviewUploadProgressTrack');
    const uploadProgressBar = document.getElementById('reviewUploadProgressBar');
    let selectedPhotos = [];
    let photoSequence = 0;
    let previewObjectUrls = [];

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

    function photoSignature(file) {
        return [file.name, file.size, file.lastModified].join(':');
    }

    function syncPhotoInput() {
        const transfer = new DataTransfer();
        selectedPhotos.forEach(function (photo) {
            transfer.items.add(photo.file);
        });
        photoInput.files = transfer.files;
    }

    function renderSelectedPhotos(message) {
        previewObjectUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        previewObjectUrls = [];
        photoPreviews.innerHTML = '';

        selectedPhotos.forEach(function (photo, index) {
            const previewItem = document.createElement('div');
            previewItem.className = 'review-photo-preview-item';

            const image = document.createElement('img');
            image.className = 'review-photo-preview';
            image.alt = 'Selected review photo ' + (index + 1);
            const objectUrl = URL.createObjectURL(photo.file);
            previewObjectUrls.push(objectUrl);
            image.src = objectUrl;

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'review-photo-remove';
            removeButton.setAttribute('aria-label', 'Remove ' + photo.file.name);
            removeButton.title = 'Remove photo';
            removeButton.innerHTML = '&times;';
            removeButton.addEventListener('click', function () {
                selectedPhotos = selectedPhotos.filter(function (selectedPhoto) {
                    return selectedPhoto.id !== photo.id;
                });
                syncPhotoInput();
                renderSelectedPhotos();
            });

            previewItem.appendChild(image);
            previewItem.appendChild(removeButton);
            photoPreviews.appendChild(previewItem);
        });

        if (message) {
            photoMessage.textContent = message;
        } else if (selectedPhotos.length === 5) {
            photoMessage.textContent = '5 photos selected. Maximum reached.';
        } else if (selectedPhotos.length > 0) {
            photoMessage.textContent = selectedPhotos.length + (selectedPhotos.length === 1 ? ' photo selected. Choose again to add more.' : ' photos selected. Choose again to add more.');
        } else {
            photoMessage.textContent = '';
        }
    }

    photoInput.addEventListener('change', function () {
        const newFiles = Array.from(photoInput.files);
        const existingSignatures = new Set(selectedPhotos.map(function (photo) {
            return photoSignature(photo.file);
        }));
        let skippedForLimit = false;

        newFiles.forEach(function (file) {
            const signature = photoSignature(file);
            if (existingSignatures.has(signature)) {
                return;
            }

            if (selectedPhotos.length >= 5) {
                skippedForLimit = true;
                return;
            }

            photoSequence += 1;
            selectedPhotos.push({ id: photoSequence, file: file });
            existingSignatures.add(signature);
        });

        syncPhotoInput();
        renderSelectedPhotos(skippedForLimit ? 'Maximum 5 photos. Extra selected photos were not added.' : '');
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

    function setSubmitting(isSubmitting) {
        submitButton.disabled = isSubmitting;
        submitButton.classList.toggle('is-loading', isSubmitting);
        submitButton.innerHTML = isSubmitting
            ? '<span class="review-button-spinner" aria-hidden="true"></span><span>Submitting review...</span>'
            : 'Submit your review <i class="fas fa-arrow-right"></i>';
    }

    function setUploadProgress(percent, statusText) {
        const safePercent = Math.max(0, Math.min(100, percent));
        uploadProgressBar.style.width = safePercent + '%';
        uploadPercent.textContent = safePercent + '%';
        uploadStatus.textContent = statusText;
        uploadProgressTrack.setAttribute('aria-valuenow', String(safePercent));
    }

    function showSubmissionErrors(errors) {
        const messages = Array.isArray(errors) && errors.length
            ? errors
            : ['We could not submit your review. Please try again.'];

        formErrorList.innerHTML = '';
        messages.forEach(function (message) {
            const item = document.createElement('li');
            item.textContent = message;
            formErrorList.appendChild(item);
        });
        formErrors.hidden = false;
        formErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (window.turnstile && document.querySelector('.cf-turnstile')) {
            window.turnstile.reset();
        }
    }

    function restoreSubmissionForm() {
        setSubmitting(false);
        uploadProgress.hidden = true;
        setUploadProgress(0, 'Uploading photos...');
    }

    reviewForm.addEventListener('submit', function (event) {
        event.preventDefault();

        if (submitButton.disabled) {
            return;
        }

        if (!reviewForm.checkValidity()) {
            reviewForm.reportValidity();
            return;
        }

        formErrors.hidden = true;
        formErrorList.innerHTML = '';
        setSubmitting(true);

        const hasPhotos = photoInput.files.length > 0;
        uploadProgress.hidden = !hasPhotos;
        if (hasPhotos) {
            setUploadProgress(0, 'Preparing photos...');
        }

        const request = new XMLHttpRequest();
        request.open('POST', reviewForm.action, true);
        request.timeout = 120000;
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('Accept', 'application/json');

        if (hasPhotos) {
            request.upload.addEventListener('progress', function (progressEvent) {
                if (!progressEvent.lengthComputable) {
                    uploadStatus.textContent = 'Uploading photos...';
                    return;
                }

                const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                setUploadProgress(percent, percent < 100 ? 'Uploading photos...' : 'Upload complete. Publishing review...');
            });
        }

        request.addEventListener('load', function () {
            let response = null;
            try {
                response = JSON.parse(request.responseText);
            } catch (error) {
                response = null;
            }

            if (request.status >= 200 && request.status < 300 && response && response.success) {
                if (hasPhotos) {
                    setUploadProgress(100, 'Review published. Redirecting...');
                }
                window.location.assign(response.redirect || 'review.php?submitted=1');
                return;
            }

            restoreSubmissionForm();
            showSubmissionErrors(response && response.errors ? response.errors : null);
        });

        request.addEventListener('error', function () {
            restoreSubmissionForm();
            showSubmissionErrors(['A network error interrupted the submission. Please check your connection and try again.']);
        });

        request.addEventListener('timeout', function () {
            restoreSubmissionForm();
            showSubmissionErrors(['The upload took too long. Please use smaller photos or try again on a stronger connection.']);
        });

        request.send(new FormData(reviewForm));
    });
});
</script>
<?php endif; ?>

<?php include 'includes/prefooter.php'; ?>
<?php include 'includes/footer.php'; ?>
