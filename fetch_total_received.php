<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Consulta para calcular a soma total dos pagamentos associados ao usuário
$stmt = $conn->prepare("SELECT COALESCE(SUM(payment_value), 0) AS total_received FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Retorna o valor total recebido em JSON
echo json_encode(["total_received" => number_format($data['total_received'], 2, '.', '')]);
exit;
?>