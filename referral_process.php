<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PHPMailer - versão sem Composer (uso manual)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acesso não autorizado");
}

// Banco de dados
require_once 'conexao.php';

// Dados do usuário atual
$user_id = $_SESSION['user_id'];
$nome_indicador = $_SESSION['user_name'] ?? 'Usuário';
$referral_code = $_SESSION['referral_code'] ?? 'N/A';

// Dados do formulário
$nome = htmlspecialchars($_POST['nome']);
$sobrenome = htmlspecialchars($_POST['sobrenome']);
$email = htmlspecialchars($_POST['email']);
$mobile = htmlspecialchars($_POST['mobile']);
$cidade = htmlspecialchars($_POST['cidade']);
$detalhes = htmlspecialchars($_POST['detalhes'] ?? 'Não informado');

// Salva no banco (agora com user_id)
$stmt = $conn->prepare("
    INSERT INTO potential_clients_referrals 
    (first_name, last_name, email, mobile, city, details, referred_by, referral_code, user_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssssssi", 
    $nome, 
    $sobrenome, 
    $email, 
    $mobile, 
    $cidade, 
    $detalhes, 
    $nome_indicador, 
    $referral_code,
    $user_id
);

if (!$stmt->execute()) {
    echo "<script>alert('Erro ao salvar no banco de dados.'); history.back();</script>";
    exit;
}

$stmt->close();
$conn->close();

// ✉️ E-mail
$destinatario = ($cidade === 'Melbourne') 
    ? "mayza.mota@bluefacilityservices.com.au"
    : "lucas.garcia@bluefacilityservices.com.au";

// Conteúdo do e-mail
$assunto = "📬 Nova indicação - $cidade";
$mensagem = "
Indicação feita por: $nome_indicador
Referral code: $referral_code

Nome: $nome $sobrenome
Email: $email
Mobile: $mobile
Cidade: $cidade
Mais detalhes: $detalhes
";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'contact@bluefacilityservices.com.au'; // Substituir se necessário
    $mail->Password = 'BlueM@rketing33'; // Nunca compartilhe em público 😅
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('contact@bluefacilityservices.com.au', 'Blue Referral Club');
    $mail->addAddress($destinatario);
    $mail->addReplyTo($email, "$nome $sobrenome");

    $mail->Subject = $assunto;
    $mail->Body    = $mensagem;
    $mail->CharSet = 'UTF-8';

    $mail->send();
    echo "<script>alert('Referral submitted successfully!'); window.location.href='index.php';</script>";
} catch (Exception $e) {
    echo "<script>alert('Error sending your referral: {$mail->ErrorInfo}'); history.back();</script>";
}
?>