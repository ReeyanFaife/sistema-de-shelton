<?php
require_once 'config.php';

// Iniciar sessão se não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (isset($_SESSION['user'])) {
    // Auditoria do evento de logout
    // audit($pdo, $_SESSION['user']['id'], "logout");

    // Remover dados específicos da sessão de forma segura
    unset($_SESSION['user']); // Remove apenas o usuário da sessão
}

// Destruir a sessão
session_unset();  // Limpa todos os dados da sessão
session_destroy(); // Destroi a sessão

// Limpar cookies relacionados à sessão (para evitar que reste algum dado)
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirecionar para a página inicial
header('Location: index.php');
exit;
?>
