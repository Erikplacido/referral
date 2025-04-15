<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['resetEmail'];

    // Verifica se o e-mail existe no sistema
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(["error" => "E-mail não encontrado."]);
        exit;
    }

    // Gera token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Remove tokens antigos
    $conn->query("DELETE FROM password_resets WHERE email = '$email'");

    // Salva token novo
    $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expires);
    $stmt->execute();

    // Corrigido: Link de redefinição
    $link = "https://bluefacilityservices.com.au/referralclub/reset_password.php?token=$token";

    // Corrigido: Remetente do e-mail
    $subject = "Redefinição de senha";
    $message = "Olá,\n\nClique no link abaixo para redefinir sua senha:\n\n$link\n\nEste link expira em 1 hora.";
    $headers = "From: no-reply@bluefacilityservices.com.au";

    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(["success" => "E-mail enviado com sucesso."]);
    } else {
        echo json_encode(["error" => "Erro ao enviar e-mail."]);
    }
}
?>