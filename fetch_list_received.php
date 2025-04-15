<?php
session_start();
require_once 'conexao.php';

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Cálculo do período de pagamento atual baseado na data de hoje
$today = new DateTime();
$day = (int) $today->format('d');

if ($day >= 15) {
    // Se hoje é dia 15 ou mais, pega o ciclo atual: 15 deste mês até 14 do próximo
    $start_date = new DateTime($today->format('Y-m') . '-15');
    $end_date = clone $start_date;
    $end_date->modify('+1 month')->modify('-1 day');
} else {
    // Se hoje é antes do dia 15, pega o ciclo anterior: 15 do mês passado até 14 deste mês
    $end_date = new DateTime($today->format('Y-m') . '-14');
    $start_date = clone $end_date;
    $start_date->modify('-1 month')->modify('+1 day');
}

// Formato MySQL
$start_date_str = $start_date->format('Y-m-d');
$end_date_str = $end_date->format('Y-m-d');

// ✅ Consulta os pagamentos do ciclo vigente
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