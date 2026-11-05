<?php
$host = getenv('AIS_DB_HOST') ?: '127.0.0.1';
$dbname = getenv('AIS_DB_NAME') ?: 'ais_db';
$username = getenv('AIS_DB_USER') ?: 'ais_user';
$password = getenv('AIS_DB_PASS') ?: 'AIS_Local_Dev_ChangeThis123!';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw new RuntimeException('Database connection failed. Check the server configuration and local database setup.', 0, $e);
}
