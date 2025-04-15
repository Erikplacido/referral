<?php
require_once 'conexao.php'; // Conexão com o banco

// ✅ Evita erro de sessão duplicada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// ✅ Verifica se o usuário está autenticado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

// ✅ Verifica se a conexão com o banco foi estabelecida corretamente
if (!$conn) {
    echo json_encode(["error" => "Falha na conexão com o banco de dados"]);
    exit;
}

// ✅ Buscar os dados do usuário (sem referral_ranking!)
$stmt = $conn->prepare("SELECT id, name, sobrenome, referral_code, club_category FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $name, $sobrenome, $referral_code, $club_category);

$user = [];
if ($stmt->fetch()) {
    $user = [
        "id" => $id,
        "name" => $name,
        "sobrenome" => $sobrenome,
        "referral_code" => $referral_code,
        "club_category" => $club_category,
    ];
}
$stmt->close();

// ✅ Buscar dados do Overview
$stmt = $conn->prepare("SELECT total_referrals, successful, unsuccessful, pending, in_negotiation FROM user_stats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_referrals, $successful, $unsuccessful, $pending, $in_negotiation);

$overview = [];
if ($stmt->fetch()) {
    $overview = [
        "total_referrals" => $total_referrals,
        "successful" => $successful,
        "unsuccessful" => $unsuccessful,
        "pending" => $pending,
        "in_negotiation" => $in_negotiation
    ];
} else {
    $overview = [
        "total_referrals" => 0,
        "successful" => 0,
        "unsuccessful" => 0,
        "pending" => 0,
        "in_negotiation" => 0
    ];
}
$stmt->close();

// ✅ Buscar informações financeiras do usuário
$stmt = $conn->prepare("SELECT total_received, next_due FROM user_payments_info WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payments_info = $result->fetch_assoc();
$stmt->close();

$total_received = $payments_info['total_received'] ?? '0.00';
$next_due = $payments_info['next_due'] ?? '0.00';

// ✅ Buscar lista de pagamentos do usuário
$stmt = $conn->prepare("SELECT payment_value, referral_name, payment_date FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();

// ✅ Buscar dados bancários do usuário
$stmt = $conn->prepare("SELECT bank_name, agency, bsb, account_number, abn_number FROM bank_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($bank_name, $agency, $bsb, $account_number, $abn_number);
$stmt->fetch();
$stmt->close();

// Criar array para detalhes bancários
$bankDetails = [
    "bankName"      => $bank_name ?? "",
    "agency"        => $agency ?? "",
    "bsb"           => $bsb ?? "",
    "accountNumber" => $account_number ?? "",
    "abnNumber"     => $abn_number ?? ""
];

// ✅ Retorna os dados em formato JSON
echo json_encode([
    "user" => $user,
    "overview" => $overview,
    "payments" => $payments,
    "total_received" => $total_received,
    "next_due" => $next_due,
    "bankDetails" => $bankDetails
]);

// ✅ Fecha a conexão APENAS NO FINAL DO SCRIPT!
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>