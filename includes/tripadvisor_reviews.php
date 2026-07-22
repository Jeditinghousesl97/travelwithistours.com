<?php

function ensure_tripadvisor_reviews_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tripadvisor_reviews (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            reviewer_name VARCHAR(150) NOT NULL,
            reviewer_location VARCHAR(150) DEFAULT NULL,
            review_title VARCHAR(255) DEFAULT NULL,
            rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
            review_text TEXT NOT NULL,
            trip_date VARCHAR(100) DEFAULT NULL,
            review_link VARCHAR(255) DEFAULT NULL,
            reviewer_image VARCHAR(255) DEFAULT NULL,
            review_photos LONGTEXT DEFAULT NULL,
            submission_source VARCHAR(20) NOT NULL DEFAULT 'admin',
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );

    $columns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM tripadvisor_reviews")->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['Field']] = true;
    }

    if (!isset($columns['review_photos'])) {
        $pdo->exec("ALTER TABLE tripadvisor_reviews ADD COLUMN review_photos LONGTEXT DEFAULT NULL AFTER reviewer_image");
    }

    if (!isset($columns['submission_source'])) {
        $pdo->exec("ALTER TABLE tripadvisor_reviews ADD COLUMN submission_source VARCHAR(20) NOT NULL DEFAULT 'admin' AFTER review_photos");
    }
}

function tripadvisor_review_photos(?string $photos_json): array
{
    if (!$photos_json) {
        return [];
    }

    $photos = json_decode($photos_json, true);
    if (!is_array($photos)) {
        return [];
    }

    return array_values(array_filter(array_map('strval', $photos), static function (string $photo): bool {
        return $photo !== '' && preg_match('#^uploads/tripadvisor-reviews/[A-Za-z0-9/_\.-]+$#', $photo) === 1;
    }));
}

function tripadvisor_format_trip_date(?string $trip_date): string
{
    $trip_date = trim((string) $trip_date);
    if ($trip_date === '') {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $trip_date);
    $errors = DateTimeImmutable::getLastErrors();
    if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
        return $date->format('F j, Y');
    }

    return $trip_date;
}

function tripadvisor_delete_review_photos(?string $photos_json, string $project_root): void
{
    $project_root = rtrim(str_replace('\\', '/', $project_root), '/');

    foreach (tripadvisor_review_photos($photos_json) as $photo) {
        $full_path = $project_root . '/' . $photo;
        $normalized_path = str_replace('\\', '/', $full_path);
        $allowed_prefix = $project_root . '/uploads/tripadvisor-reviews/guest/';

        if (str_starts_with($normalized_path, $allowed_prefix) && is_file($full_path)) {
            unlink($full_path);
        }
    }
}

function tripadvisor_review_excerpt(string $text, int $limit = 80): array
{
    $text = trim($text);

    if ($text === '') {
        return ['preview' => '', 'remainder' => '', 'has_more' => false];
    }

    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if ($words === false || count($words) <= $limit) {
        return ['preview' => $text, 'remainder' => '', 'has_more' => false];
    }

    return [
        'preview' => implode(' ', array_slice($words, 0, $limit)),
        'remainder' => implode(' ', array_slice($words, $limit)),
        'has_more' => true,
    ];
}

function tripadvisor_reviewer_initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'G';
    }

    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false || empty($parts)) {
        return 'G';
    }

    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }

    return strtoupper($initials ?: 'G');
}
