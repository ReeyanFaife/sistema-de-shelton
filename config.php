<?php
// config.php
session_start();

$DB_HOST = '127.0.0.1';
$DB_PORT = '5432';
$DB_NAME = 'BD_SHELTON';
$DB_USER = 'postgres';
$DB_PASS = 'Shelton2003';

$dsn = "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME";
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB Connection failed: " . htmlspecialchars($e->getMessage()));
}

// helper: current user
function current_user() {
    return $_SESSION['user'] ?? null;
}
