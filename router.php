<?php
declare(strict_types=1);

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$databaseDirectory = realpath(__DIR__ . '/database');
$requestedFile = realpath(__DIR__ . urldecode((string) $requestedPath));

if ($databaseDirectory !== false && $requestedFile !== false
    && str_starts_with($requestedFile, $databaseDirectory . DIRECTORY_SEPARATOR)
    && preg_match('/\.(db|sqlite|sqlite3)(?:-|$)/i', basename($requestedFile))) {
    http_response_code(404);
    exit;
}

if ($requestedFile !== false && is_file($requestedFile)) {
    return false;
}

require __DIR__ . '/index.php';