<?php
require_once 'conexao.php';
?>

<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Login - Projeto</title>
  <link rel="stylesheet" href="login-style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Blue Referral Club</h2>

      <!-- Login tradicional -->
      <form action="validar_login.php" method="POST" id="loginForm">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" name="password" id="password" required>
        </div>

        <button type="submit" class="btn-gold">Entrar</button>
      </form>

      <!-- Link "Esqueci a senha" -->
      <p style="margin-top: 15px;">
        <a href="#" id="forgotPasswordLink" style="color: white;">Forgot password?</a>
      </p>

      <!-- Formulário para redefinir senha -->
      <form id="forgotPasswordForm" style="display: none; margin-top: 20px;">
        <div class="form-group">
          <label for="resetEmail">Enter your email to reset</label>
          <input type="email" id="resetEmail" name="resetEmail" placeholder="Your email" required>
        </div>
        <button type="submit" class="btn-gold">Send reset email</button>
      </form>
    </div>
  </div>

  <!-- JavaScript -->
  <script>
    // Alternar exibição entre login e "esqueci senha"
    document.getElementById("forgotPasswordLink").addEventListener("click", function (e) {
      e.preventDefault();
      document.getElementById("forgotPasswordForm").style.display = "block";
      document.getElementById("loginForm").style.display = "none";
    });

    // Envio do e-mail de recuperação via fetch
    document.getElementById("forgotPasswordForm").addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      const response = await fetch("send_reset_email.php", {
        method: "POST",
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        alert(data.success);
        this.reset();
      } else {
        alert(data.error || "Erro ao processar solicitação.");
      }
    });
  </script>
</body>
</html>