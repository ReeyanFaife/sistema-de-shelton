<?php
// config.php
$host     = getenv('PGHOST')     ?: 'ep-snowy-credit-axv7n9xs-pooler.c-4.us-east-2.aws.neon.tech';
$db       = getenv('PGDATABASE') ?: 'neondb';
$user     = getenv('PGUSER')     ?: 'neondb_owner';
$pass     = getenv('PGPASSWORD') ?: 'npg_CRBlKYaZw5O9';
$port     = getenv('PGPORT')     ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// helper: current user
function current_user() {
    return $_SESSION['user'] ?? null;
}
