<?php
session_start();

// Se a sessão existir, destrói e redireciona
if (isset($_SESSION['user_id'])) {
    session_unset(); // Remove todas as variáveis de sessão
    session_destroy(); // Destroi a sessão
    setcookie(session_name(), '', time() - 3600, '/'); // Invalida o cookie de sessão
}

header("Location: login.php"); // Redireciona para login
exit;
?>