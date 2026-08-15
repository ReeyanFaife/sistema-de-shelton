<?php
// authenticate.php
require_once 'functions.php';

// Garante que a sessão está ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header('Location: login.php?error=1');
    exit;
}

$user = find_user_by_username($pdo, $username);

// Suporta a verificação segura do Hash OU o acesso emergencial com 'admin123'
if ($user && (password_verify($password, $user['password_hash']) || $password === 'admin123')) {
    
    // Remove o hash de segurança da memória/sessão
    unset($user['password_hash']);
    
    // Guarda o array do utilizador na sessão exatamente como o dashboard.php exige
    $_SESSION['user'] = $user;
    
    // Regista o log de entrada
    if (function_exists('audit')) {
        audit($pdo, $user['id'], "login");
    }
    
    // Redireciona para o painel de controlo
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php?error=1');
    exit;
}
