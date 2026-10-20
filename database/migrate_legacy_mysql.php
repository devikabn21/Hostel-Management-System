<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "Legacy import requires pdo_mysql. The Hostel Management System itself does not.\n");
    exit(1);
}

$host = getenv('LEGACY_MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('LEGACY_MYSQL_PORT') ?: '3306';
$name = getenv('LEGACY_MYSQL_DATABASE') ?: '';
$username = getenv('LEGACY_MYSQL_USER') ?: '';
$password = getenv('LEGACY_MYSQL_PASSWORD') ?: '';
if ($name === '' || $username === '') {
    fwrite(STDERR, "Set LEGACY_MYSQL_DATABASE and LEGACY_MYSQL_USER, plus any required host, port, and password values.\n");
    exit(1);
}

$legacy = new PDO(
    "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$sqlite = database_connection();
$tables = [
    'admin_users' => ['id', 'username', 'email', 'password_hash', 'created_at', 'updated_at'],
    'students' => ['id', 'full_name', 'usn', 'department', 'semester', 'phone', 'email', 'gender', 'address', 'guardian_name', 'guardian_phone', 'registration_date', 'created_at', 'updated_at'],
    'rooms' => ['id', 'room_number', 'room_type', 'floor', 'capacity', 'status', 'created_at', 'updated_at'],
    'room_allocations' => ['id', 'student_id', 'room_id', 'bed_number', 'status', 'allocated_at', 'vacated_at', 'created_at'],
];

try {
    begin_immediate($sqlite);
    foreach ($tables as $table => $columns) {
        $quotedColumns = implode(', ', array_map(static fn (string $column): string => '"' . $column . '"', $columns));
        $legacyColumns = implode(', ', array_map(static fn (string $column): string => '`' . $column . '`', $columns));
        $placeholders = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
        $insert = $sqlite->prepare("INSERT INTO \"$table\" ($quotedColumns) VALUES ($placeholders)");
        foreach ($legacy->query("SELECT $legacyColumns FROM `$table`") as $row) {
            $insert->execute($row);
        }
        fwrite(STDOUT, "$table: copied rows\n");
    }
    commit_immediate($sqlite);
    fwrite(STDOUT, "Import complete. The legacy MySQL database was not changed.\n");
} catch (Throwable $exception) {
    rollback_immediate($sqlite);
    fwrite(STDERR, "Import stopped and SQLite changes were rolled back. Check the legacy schema and that the target SQLite database has no conflicting IDs or unique values.\n");
    error_log((string) $exception);
    exit(1);
}