<?php
require_once 'conexao.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($newPassword !== $confirmPassword) {
        die("As senhas não coincidem.");
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

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
    $stmt->bind_param("ss", $newPasswordHash, $email);
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
    <script>
      function validarSenha() {
        const senha = document.getElementById("newPassword").value;
        const confirmar = document.getElementById("confirmPassword").value;
        if (senha !== confirmar) {
          alert("As senhas não coincidem.");
          return false;
        }
        return true;
      }

      function toggleSenha() {
        const campos = ["newPassword", "confirmPassword"];
        campos.forEach(id => {
          const input = document.getElementById(id);
          input.type = input.type === "password" ? "text" : "password";
        });
      }
    </script>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Redefinir Senha</h2>
      <form method="POST" onsubmit="return validarSenha();">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
          <label for="newPassword">Nova Senha</label>
          <input type="password" name="newPassword" id="newPassword" required>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirmar Nova Senha</label>
          <input type="password" name="confirmPassword" id="confirmPassword" required>
        </div>

        <div class="form-group">
          <input type="checkbox" id="mostrarSenha" onclick="toggleSenha()">
          <label for="mostrarSenha">Mostrar Senhas</label>
        </div>

        <button type="submit" class="btn-gold">Salvar Nova Senha</button>
      </form>
    </div>
  </div>
</body>
</html>
