<?php
// update_password.php

session_start();

// 1) Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Acesso não autorizado. É necessário estar logado."]);
    exit;
}

// Se quiser permitir apenas 'user' e não 'admin', descomente:
/*
if ($_SESSION['user_type'] !== 'user') {
    http_response_code(403);
    echo json_encode(["error" => "Acesso restrito. Apenas usuários podem alterar senhas aqui."]);
    exit;
}
*/

// 2) Conexão com o banco de dados
require_once 'conexao.php';  // Garante que a var $conn esteja disponível
header('Content-Type: application/json; charset=utf-8');

// 3) Pega o ID do usuário logado
$userId = $_SESSION['user_id'];

// 4) Recebe os dados do formulário
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword     = $_POST['newPassword']     ?? '';

// (Opcional) Exemplo de validação do tamanho mínimo da nova senha:
if (strlen($newPassword) < 6) {
    echo json_encode(["error" => "A nova senha deve ter ao menos 6 caracteres."]);
    exit;
}

// 5) Busca a senha atual no banco
$sql = "SELECT password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Usuário não encontrado."]);
    exit;
}

$user = $result->fetch_assoc();
$hashSenhaAtual = $user['password'];

// 6) Verifica se a senha atual confere
if (!password_verify($currentPassword, $hashSenhaAtual)) {
    echo json_encode(["error" => "Senha atual incorreta."]);
    exit;
}

// 7) Gera hash da nova senha
$hashNova = password_hash($newPassword, PASSWORD_DEFAULT);

// 8) Atualiza no banco de dados
$sqlUpdate = "UPDATE users SET password = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("si", $hashNova, $userId);

if ($stmtUpdate->execute()) {
    echo json_encode(["message" => "Senha alterada com sucesso!"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao atualizar a senha no banco de dados."]);
}
?>