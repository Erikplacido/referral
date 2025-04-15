<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Busca os pagamentos dentro do período correto (15 do mês passado até 14 do mês atual)
$start_date = date('Y-m-15', strtotime('last month'));
$end_date = date('Y-m-14');

$query = "
    SELECT payment_value, referral_name, payment_date 
    FROM payments 
    WHERE user_id = ? 
    AND payment_date BETWEEN ? AND ?
    ORDER BY payment_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(["payments" => $payments]);
?>