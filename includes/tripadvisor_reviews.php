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
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
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
