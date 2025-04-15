<?php
// export_payment_pdf.php

session_start();
require_once 'conexao.php';
require('fpdf/fpdf.php'); // Ajuste o caminho conforme necessário

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['period_start']) || !isset($_POST['period_end'])) {
    echo "Período não especificado.";
    exit;
}

$period_start = $_POST['period_start'];
$period_end = $_POST['period_end'];

// Buscar o nome do usuário
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Buscar os pagamentos do período selecionado
$stmt = $conn->prepare("SELECT payment_value, referral_name, payment_date FROM payment_history 
                       WHERE user_id = ? AND period_start = ? AND period_end = ?");
$stmt->bind_param("iss", $user_id, $period_start, $period_end);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calcular o total a receber
$total_next_due = 0;
foreach ($payments as $payment) {
    $total_next_due += (float)$payment['payment_value'];
}

// Cria um novo PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

// Título (usar utf8_decode ou iconv)
$pdf->Cell(0, 10, utf8_decode("Histórico de Pagamentos"), 0, 1, 'C');

// Informações do usuário e do período
$pdf->SetFont('Arial','',12);
$pdf->Cell(0, 10, utf8_decode("Usuário: ") . utf8_decode($user_name), 0, 1);
$pdf->Cell(0, 10, utf8_decode("Período: ") 
                 . date("d/m/Y", strtotime($period_start)) 
                 . " - " 
                 . date("d/m/Y", strtotime($period_end)), 0, 1);
$pdf->Ln(5);

// Título dos pagamentos
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0, 10, utf8_decode("Pagamentos do Período"), 0, 1, 'C');

// Cabeçalho da tabela
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60, 10, utf8_decode("Valor"), 1, 0, 'C');
$pdf->Cell(80, 10, utf8_decode("Nome Indicação"), 1, 0, 'C');
$pdf->Cell(40, 10, utf8_decode("Data"), 1, 1, 'C');

// Dados da tabela
$pdf->SetFont('Arial','',12);
foreach ($payments as $payment) {
    // Converte cada campo para evitar problemas de acentuação
    $valor = "R$ " . number_format($payment['payment_value'], 2, ',', '.');
    $referral_name = utf8_decode($payment['referral_name']);
    $data = date("d/m/Y", strtotime($payment['payment_date']));

    $pdf->Cell(60, 10, utf8_decode($valor), 1, 0, 'C');
    $pdf->Cell(80, 10, $referral_name, 1, 0, 'C');
    $pdf->Cell(40, 10, utf8_decode($data), 1, 1, 'C');
}

$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode("Total a receber neste período: R$ ") 
                 . number_format($total_next_due, 2, ',', '.'), 0, 1);

// Exibe o PDF no navegador
$pdf->Output('I', 'historico_pagamentos.pdf');

$conn->close();
?>