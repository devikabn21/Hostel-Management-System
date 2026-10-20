<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$username = trim((string) readline('Admin username: '));
$email = trim((string) readline('Admin email: '));
$password = (string) readline('Admin password (minimum 12 characters): ');

if ($username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Enter a username, valid email, and password of at least 12 characters.\n");
    exit(1);
}

try {
    $statement = database_connection()->prepare(
        'INSERT INTO admin_users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
    );
    $statement->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Admin account created successfully.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create admin account. Check that the database is imported and the username/email is unused.\n");
    error_log($exception->getMessage());
    exit(1);
}