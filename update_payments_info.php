<?php
require_once 'conexao.php'; // Conexão com o banco

// ✅ Evita erro de sessão duplicada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ✅ Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["error" => "Acesso não autorizado. É necessário estar logado."]);
    exit;
}

$userId = $_SESSION['user_id'];

// ✅ Captura os dados enviados via POST
$bank_name      = $_POST['bankName'] ?? null;
$agency         = $_POST['agency']   ?? null;
$bsb            = $_POST['bsb']      ?? null;
$account_number = $_POST['accountNumber'] ?? null;
$abn_number     = $_POST['abnNumber']     ?? null;

// ✅ Verifica se todos os campos estão preenchidos
if (!$bank_name || !$agency || !$bsb || !$account_number || !$abn_number) {
    echo json_encode(["error" => "Todos os campos são obrigatórios."]);
    exit;
}

// ✅ Verifica se o usuário já tem um registro na tabela `bank_details`
$stmt = $conn->prepare("SELECT id FROM bank_details WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // ✅ Usuário já tem registro → Faz UPDATE
    $stmt->close();
    $sql = "UPDATE bank_details 
            SET bank_name = ?, agency = ?, bsb = ?, account_number = ?, abn_number = ?, updated_at = NOW()
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $bank_name, $agency, $bsb, $account_number, $abn_number, $userId);
} else {
    // ❌ Usuário NÃO tem registro → Faz INSERT
    $stmt->close();
    $sql = "INSERT INTO bank_details (user_id, bank_name, agency, bsb, account_number, abn_number, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $userId, $bank_name, $agency, $bsb, $account_number, $abn_number);
}

// ✅ Executa a query correta
if ($stmt->execute()) {
    echo json_encode(["message" => "Informações bancárias salvas com sucesso!"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao salvar informações bancárias."]);
}

$stmt->close();
$conn->close();
?>