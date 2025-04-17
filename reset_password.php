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
    <style>
      .input-icon-wrapper {
        position: relative;
      }
      .input-icon-wrapper input {
        padding-right: 40px;
      }
      .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
      }
      .toggle-password img {
        width: 20px;
        opacity: 0.6;
      }
    </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Redefinir Senha</h2>
      <form method="POST" onsubmit="return validarSenha();">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
          <label for="newPassword">Nova Senha</label>
          <div class="input-icon-wrapper">
            <input type="password" name="newPassword" id="newPassword" required>
            <span class="toggle-password" onclick="togglePassword(this, 'newPassword')">
              <img src="assets/img/eye.svg" alt="Mostrar">
            </span>
          </div>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirmar Nova Senha</label>
          <div class="input-icon-wrapper">
            <input type="password" name="confirmPassword" id="confirmPassword" required>
            <span class="toggle-password" onclick="togglePassword(this, 'confirmPassword')">
              <img src="assets/img/eye.svg" alt="Mostrar">
            </span>
          </div>
        </div>

        <button type="submit" class="btn-gold">Salvar Nova Senha</button>
      </form>
    </div>
  </div>

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

    function togglePassword(el, inputId) {
      const input = document.getElementById(inputId);
      const icon = el.querySelector("img");

      if (input.type === "password") {
        input.type = "text";
        icon.src = "assets/img/eye-off.svg";
      } else {
        input.type = "password";
        icon.src = "assets/img/eye.svg";
      }
    }
  </script>
</body>
</html>
