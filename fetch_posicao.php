<?php
require_once 'conexao.php';

header('Content-Type: application/json');

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está autenticado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

// Consulta user_ids com total recebido, ordenado pela soma de pagamentos
$stmt = $conn->prepare("
    SELECT user_id, SUM(payment_value) AS total 
    FROM payments 
    GROUP BY user_id 
    ORDER BY total DESC
");

$stmt->execute();
$result = $stmt->get_result();

$rank = 1;
$userRank = null;

while ($row = $result->fetch_assoc()) {
    if ((int)$row['user_id'] === (int)$user_id) {
        $userRank = $rank;
        break;
    }
    $rank++;
}
$stmt->close();

// Retorna a posição formatada
echo json_encode([
    "position" => $userRank ? "Top {$userRank}" : "N/A"
]);