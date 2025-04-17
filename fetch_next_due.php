<?php
session_start();
require_once 'conexao.php';

// Início do log de debug
$log = fopen("log_next_due.txt", "a");
fwrite($log, "\n=== NOVA REQUISIÇÃO EM " . date("Y-m-d H:i:s") . " ===\n");

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    fwrite($log, "Erro: Usuário não autenticado.\n");
    echo json_encode(["error" => "Usuário não autenticado"]);
    fclose($log);
    exit;
}

$user_id = $_SESSION['user_id'];
fwrite($log, "Usuário ID: $user_id\n");

// Definindo a janela de datas com base no mês atual
$start_date_str = date("Y-m-01");  // Primeiro dia do mês atual
$end_date_str   = date("Y-m-t");    // Último dia do mês atual
fwrite($log, "Mês em questão: $start_date_str até $end_date_str\n");

// 1) Consulta pagamentos realizados no mês atual
$query = "
    SELECT SUM(payment_value) AS next_due 
    FROM payments 
    WHERE user_id = ? 
    AND payment_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $start_date_str, $end_date_str);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

$next_due = $data['next_due'] ?? 0;
fwrite($log, "Total encontrado no mês atual (payments): " . var_export($next_due, true) . "\n");

// 2) Fallback: Consulta na user_payments_info se o valor for nulo ou zero
if (!$next_due || $next_due == 0) {
    fwrite($log, "Usando fallback (user_payments_info)...\n");

    $stmt2 = $conn->prepare("SELECT next_due FROM user_payments_info WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $info = $res2->fetch_assoc();
    $fallback_value = $info['next_due'] ?? 0;

    $next_due = $fallback_value;
    fwrite($log, "Valor recuperado do fallback: " . var_export($fallback_value, true) . "\n");
    $stmt2->close();
}

$conn->close();

// 3) Retorna o valor formatado com duas casas decimais
$final = number_format($next_due, 2, '.', '');
fwrite($log, "Valor final retornado: $final\n");
fclose($log);

echo json_encode([
    "next_due" => $final
]);
?>
