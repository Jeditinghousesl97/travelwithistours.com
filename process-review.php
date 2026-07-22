<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'includes/tripadvisor_reviews.php';

ensure_tripadvisor_reviews_schema($pdo);

function redirect_review_form(array $errors, array $old = []): void
{
    $_SESSION['review_form_errors'] = $errors;
    $_SESSION['review_form_old'] = $old;
    header('Location: review.php#review-form');
    exit;
}

function verify_review_turnstile(string $secret, string $response): bool
{
    if ($secret === '' || $response === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => $secret,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    if (function_exists('curl_init')) {
        $curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $result = curl_exec($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => $payload,
                'timeout' => 12,
            ],
        ]);
        $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    }

    if (!is_string($result) || $result === '') {
        return false;
    }

    $decoded = json_decode($result, true);
    return is_array($decoded) && !empty($decoded['success']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

$old = [
    'reviewer_name' => trim((string) ($_POST['reviewer_name'] ?? '')),
    'reviewer_location' => trim((string) ($_POST['reviewer_location'] ?? '')),
    'review_title' => trim((string) ($_POST['review_title'] ?? '')),
    'trip_date' => trim((string) ($_POST['trip_date'] ?? '')),
    'rating' => (int) ($_POST['rating'] ?? 0),
    'review_text' => trim((string) ($_POST['review_text'] ?? '')),
];

if (!empty($_POST['website'])) {
    redirect_review_form(['We could not accept this submission. Please try again.'], $old);
}

$csrf_token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['review_csrf_token']) || !hash_equals($_SESSION['review_csrf_token'], $csrf_token)) {
    redirect_review_form(['Your form session expired. Please reload the page and try again.'], $old);
}

$errors = [];
$text_length = static function (string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
};

if ($old['reviewer_name'] === '' || $text_length($old['reviewer_name']) > 150) {
    $errors[] = 'Enter your name (up to 150 characters).';
}

if ($old['reviewer_location'] === '' || $text_length($old['reviewer_location']) > 150) {
    $errors[] = 'Enter your location, such as London, United Kingdom.';
}

if ($old['review_title'] === '' || $text_length($old['review_title']) > 255) {
    $errors[] = 'Enter a review title (up to 255 characters).';
}

if ($old['rating'] < 1 || $old['rating'] > 5) {
    $errors[] = 'Choose a rating from 1 to 5.';
}

$trip_date = DateTimeImmutable::createFromFormat('!Y-m-d', $old['trip_date']);
$trip_date_errors = DateTimeImmutable::getLastErrors();
if (!$trip_date || ($trip_date_errors !== false && ($trip_date_errors['warning_count'] > 0 || $trip_date_errors['error_count'] > 0))) {
    $errors[] = 'Choose a valid trip date.';
} elseif ($trip_date > new DateTimeImmutable('today')) {
    $errors[] = 'The trip date cannot be in the future.';
}

$review_text_length = $text_length($old['review_text']);
if ($review_text_length < 20 || $review_text_length > 5000) {
    $errors[] = 'Write a review between 20 and 5,000 characters.';
}

$turnstile_secret = '';
try {
    $secret_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $secret_stmt->execute(['cf_secret_key']);
    $turnstile_secret = trim((string) ($secret_stmt->fetchColumn() ?: ''));
} catch (Throwable $e) {
    $turnstile_secret = '';
}

if ($turnstile_secret !== '') {
    $turnstile_response = trim((string) ($_POST['cf-turnstile-response'] ?? ''));
    if (!verify_review_turnstile($turnstile_secret, $turnstile_response)) {
        $errors[] = 'Complete the security check and submit the form again.';
    }
}

$uploaded_files = [];
if (isset($_FILES['review_photos']) && is_array($_FILES['review_photos']['name'] ?? null)) {
    $file_count = count(array_filter($_FILES['review_photos']['name'], static fn($name): bool => (string) $name !== ''));
    if ($file_count > 5) {
        $errors[] = 'You can upload a maximum of 5 photos.';
    }
}

if (!empty($errors)) {
    redirect_review_form($errors, $old);
}

try {
    if (isset($_FILES['review_photos']) && is_array($_FILES['review_photos']['name'] ?? null)) {
        $allowed_mime_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $upload_directory = __DIR__ . '/uploads/tripadvisor-reviews/guest/';

        foreach ($_FILES['review_photos']['name'] as $index => $original_name) {
            if ((string) $original_name === '') {
                continue;
            }

            $upload_error = (int) ($_FILES['review_photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            $file_size = (int) ($_FILES['review_photos']['size'][$index] ?? 0);
            $temporary_path = (string) ($_FILES['review_photos']['tmp_name'][$index] ?? '');

            if ($upload_error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('One of the photos could not be uploaded. Please try again.');
            }

            if ($file_size < 1 || $file_size > 5 * 1024 * 1024) {
                throw new RuntimeException('Each photo must be smaller than 5 MB.');
            }

            $image_info = @getimagesize($temporary_path);
            $file_info = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $file_info->file($temporary_path);

            if ($image_info === false || !is_string($mime_type) || !isset($allowed_mime_types[$mime_type])) {
                throw new RuntimeException('Photos must be valid JPG, PNG, or WebP images.');
            }

            if (!is_dir($upload_directory) && !mkdir($upload_directory, 0755, true) && !is_dir($upload_directory)) {
                throw new RuntimeException('The photo upload folder is unavailable.');
            }

            $filename = date('Ymd') . '-' . bin2hex(random_bytes(10)) . '.' . $allowed_mime_types[$mime_type];
            $destination = $upload_directory . $filename;

            if (!move_uploaded_file($temporary_path, $destination)) {
                throw new RuntimeException('A photo could not be saved. Please try again.');
            }

            $uploaded_files[] = 'uploads/tripadvisor-reviews/guest/' . $filename;
        }
    }

    $display_order = (int) $pdo->query("SELECT COALESCE(MIN(display_order), 0) - 1 FROM tripadvisor_reviews")->fetchColumn();
    $insert = $pdo->prepare(
        "INSERT INTO tripadvisor_reviews
        (reviewer_name, reviewer_location, review_title, rating, review_text, trip_date, review_link, reviewer_image, review_photos, submission_source, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, 'guest', ?, 1)"
    );
    $insert->execute([
        $old['reviewer_name'],
        $old['reviewer_location'],
        $old['review_title'],
        $old['rating'],
        $old['review_text'],
        $old['trip_date'],
        $uploaded_files ? json_encode($uploaded_files, JSON_UNESCAPED_SLASHES) : null,
        $display_order,
    ]);

    $_SESSION['review_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['review_submitted'] = true;
    unset($_SESSION['review_form_errors'], $_SESSION['review_form_old']);
    header('Location: review.php?submitted=1');
    exit;
} catch (RuntimeException $e) {
    foreach ($uploaded_files as $uploaded_file) {
        $full_path = __DIR__ . '/' . $uploaded_file;
        if (is_file($full_path)) {
            unlink($full_path);
        }
    }
    redirect_review_form([$e->getMessage()], $old);
} catch (Throwable $e) {
    foreach ($uploaded_files as $uploaded_file) {
        $full_path = __DIR__ . '/' . $uploaded_file;
        if (is_file($full_path)) {
            unlink($full_path);
        }
    }
    error_log('Guest review submission error: ' . $e->getMessage());
    redirect_review_form(['We could not publish your review right now. Please try again shortly.'], $old);
}
