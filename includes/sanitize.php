<?php
function sanitizeText(string $value, int $maxLength = 255): string
{
    $value = trim($value);
    // Remove null bytes and other non-printable control characters
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    return mb_substr($value, 0, $maxLength);
}

/*  Sanitize a username: */
function sanitizeUsername(string $value): string
{
    $value = sanitizeText($value, 50);
    if (!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/', $value)) {
        return '';
    }
    return $value;
}

/*  Sanitize an email address: */
function sanitizeEmail(string $value): string
{
    $value = sanitizeText($value, 254);
    $clean = filter_var($value, FILTER_SANITIZE_EMAIL);
    if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    return $clean;
}

/*  Sanitize a post content: */
function sanitizePostContent(string $value): string
{
    return sanitizeText($value, 500);
}

/*  Sanitize a search query or a scry query: */
function sanitizeSearch(string $value): string
{
    $value = sanitizeText($value, 100);
    // Strip characters that have no place in a search box
    $value = preg_replace('/[<>{};\'"`\\\\]/', '', $value);
    return $value;
}

/*  Validate an image upload: */
function validateImageUpload(array $file): bool
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMimes      = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize           = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) return false;

    // Check actual MIME type from file contents (not spoofable via $_FILES)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) return false;

    return true;
}