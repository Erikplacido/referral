<?php
session_start();

// 1) Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2) Conecta ao banco
require_once 'conexao.php';

// 3) Pega o ID do usuário a excluir via GET
$userId = $_GET['id'] ?? 0;
$userId = (int) $userId; // Garante que seja inteiro

// Verifica se é um ID válido
if ($userId <= 0) {
    header("Location: admin_dashboard.php?msg=Id_invalido");
    exit;
}

// 4) Monta a query de exclusão
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);

// 5) Executa e checa se deu certo
if ($stmt->execute()) {
    // Se quiser, você pode excluir dados relacionados em outras tabelas
    // (ex: user_stats, payments, etc.) se houver chaves estrangeiras.
    // Se estiver usando ON DELETE CASCADE, já é automático.

    header("Location: admin_dashboard.php?msg=Usuario_excluido");
} else {
    header("Location: admin_dashboard.php?msg=Erro_ao_excluir");
}
exit;