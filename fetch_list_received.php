<?php
session_start();
require_once 'conexao.php';

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Cálculo do período de pagamento atual baseado no mês atual
$today = new DateTime();
$start_date = new DateTime($today->format('Y-m-01')); // Primeiro dia do mês atual
$end_date   = new DateTime($today->format('Y-m-t'));   // Último dia do mês atual

// Formato MySQL
$start_date_str = $start_date->format('Y-m-d');
$end_date_str   = $end_date->format('Y-m-d');

// ✅ Consulta os pagamentos do mês atual
$query = "
    SELECT payment_value, referral_name, payment_date
    FROM payments
    WHERE user_id = ? 
    AND payment_date BETWEEN ? AND ?
    ORDER BY payment_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $start_date_str, $end_date_str);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// ✅ Retorno JSON
echo json_encode(["payments" => $payments]);
?>
