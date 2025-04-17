<?php
// save_bank_details.php

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$bankName      = trim($_POST['bankName']      ?? '');
$agency        = trim($_POST['agency']        ?? '');
$bsb           = trim($_POST['bsb']           ?? '');
$accountNumber = trim($_POST['accountNumber'] ?? '');
$abnNumber     = trim($_POST['abnNumber']     ?? '');

if ($bankName === '' && $agency === '' && $bsb === '' && $accountNumber === '' && $abnNumber === '') {
    echo json_encode(['error' => 'Please fill in at least one field before saving.']);
    exit;
}

try {
    // Verifica se já existe registro
    $check = $conn->prepare("SELECT COUNT(*) FROM user_bank_details WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        // Atualiza
        $stmt = $conn->prepare("
            UPDATE user_bank_details
            SET bank_name = ?, agency = ?, bsb = ?, account_number = ?, abn_number = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param(
            "sssssi",
            $bankName,
            $agency,
            $bsb,
            $accountNumber,
            $abnNumber,
            $user_id
        );
        $stmt->execute();
        $stmt->close();

        echo json_encode(['message' => 'Bank information updated successfully.']);
    } else {
        // Insere
        $stmt = $conn->prepare("
            INSERT INTO user_bank_details
            (user_id, bank_name, agency, bsb, account_number, abn_number)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssss",
            $user_id,
            $bankName,
            $agency,
            $bsb,
            $accountNumber,
            $abnNumber
        );
        $stmt->execute();
        $stmt->close();

        echo json_encode(['message' => 'Bank information saved successfully!']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}