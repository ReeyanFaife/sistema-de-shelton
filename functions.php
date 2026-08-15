<?php
// functions.php
require_once 'config.php';

// 1. Garantir que o PHP reconheça o HTTPS do Render
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// 2. Iniciar a sessão se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurações recomendadas para segurança de cookies
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

function find_user_by_username($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id, name, username, password_hash, role FROM users WHERE username = :u");
    $stmt->execute(['u' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function require_login() {
    // Garante que a sessão está ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

function require_role($role) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        echo "Acesso negado.";
        exit;
    }
}

function audit($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (:u, :a)");
    $stmt->execute(['u' => $user_id, 'a' => $action]);
}

function calc_equal_installments($amount, $annual_interest_rate, $months) {
    // Convert annual rate (decimal) to monthly rate
    $r = $annual_interest_rate / 12.0;
    if ($r == 0) {
        $payment = $amount / $months;
    } else {
        // formula: A = P * r / (1 - (1+r)^-n)
        $payment = $amount * ($r) / (1 - pow(1 + $r, -$months));
    }
    return round($payment, 2);
}
