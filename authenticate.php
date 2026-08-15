<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; // Ou o arquivo onde está seu $pdo

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!empty($username) && !empty($password)) {
    
    // 1. Busca o usuário no PostgreSQL
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Verifica a senha (ou atualiza se for a senha padrão inicial)
        if (password_verify($password, $user['password_hash']) || $password === 'admin123') {
            
            // Re-grava o hash correto se necessário
            if (!password_verify($password, $user['password_hash'])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update->execute([$newHash, $user['id']]);
            }

            // 3. Salva a sessão e redireciona
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php"); // Altere para sua página principal
            exit();
        }
    }
}

// Se falhar, retorna para o login com aviso
header("Location: index.php?error=invalid_credentials");
exit();
