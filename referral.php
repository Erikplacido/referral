<?php
session_start();
require_once 'conexao.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar nome e referral_code
$stmt = $conn->prepare("SELECT name, referral_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $referral_code);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Give a Referral</title>
  <link rel="stylesheet" href="login-style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Give a Referral</h2>
      <form action="referral_process.php" method="POST">
        <!-- Usuário atual -->
        <div class="form-group">
          <label>Your name</label>
          <input type="text" value="<?= htmlspecialchars($name) ?>" readonly>
        </div>
        <div class="form-group">
          <label>Your referral code</label>
          <input type="text" value="<?= htmlspecialchars($referral_code) ?>" readonly>
        </div>

        <!-- Dados da indicação -->
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="nome" required>
        </div>
        <div class="form-group">
             <label>Last name</label>
             <input type="text" name="sobrenome">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group">
          <label>Mobile</label>
          <input type="text" name="mobile" required>
        </div>
        <div class="form-group">
          <label>City</label>
          <select name="cidade" required>
            <option value="">Selecione</option>
            <option value="Melbourne">Melbourne</option>
            <option value="Sydney">Sydney</option>
          </select>
        </div>
        <div class="form-group">
          <label>More details (optional)</label>
          <input type="text" name="detalhes">
        </div>

        <button type="submit" class="btn-gold">Submit</button>
      </form>
    </div>
  </div>
</body>
</html>