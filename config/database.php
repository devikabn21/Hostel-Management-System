<?php
declare(strict_types=1);

function database_connection(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('SQLite support is unavailable. Enable pdo_sqlite in the active PHP installation.');
    }

    $databasePath = dirname(__DIR__) . '/database/hostel_management.db';
    $schemaPath = dirname(__DIR__) . '/database/hostel_management.sql';
    $connection = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $connection->exec('PRAGMA foreign_keys = ON');
    $connection->exec('PRAGMA busy_timeout = 5000');

    $schema = file_get_contents($schemaPath);
    if ($schema === false) {
        throw new RuntimeException('The SQLite schema file could not be loaded.');
    }
    $connection->exec($schema);

    return $connection;
}

function begin_immediate(PDO $connection): void
{
    $connection->exec('BEGIN IMMEDIATE');
}

function commit_immediate(PDO $connection): void
{
    $connection->exec('COMMIT');
}

function rollback_immediate(PDO $connection): void
{
    try {
        $connection->exec('ROLLBACK');
    } catch (PDOException) {
    }
}