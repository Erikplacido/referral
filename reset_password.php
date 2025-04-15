<?php
require_once 'conexao.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);

    // Valida token
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetData = $result->fetch_assoc();

    if (!$resetData) {
        die("Token inválido ou expirado.");
    }

    $email = $resetData['email'];

    // Atualiza senha
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $newPassword, $email);
    $stmt->execute();

    // Remove token
    $conn->query("DELETE FROM password_resets WHERE email = '$email'");

    echo "Senha redefinida com sucesso. <a href='login.php'>Acessar conta</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="login-style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Redefinir Senha</h2>
      <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
          <label for="newPassword">Nova Senha</label>
          <input type="password" name="newPassword" id="newPassword" required>
        </div>

        <button type="submit" class="btn-gold">Salvar Nova Senha</button>
      </form>
    </div>
  </div>
</body>
</html>