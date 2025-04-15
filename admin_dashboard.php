<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Se não for admin, redireciona para login
    header("Location: login.php");
    exit;
}

// Conexão com o banco de dados
require_once 'conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <header>
    <h2 style="color:#fff;">Welcome, <?php echo $_SESSION['user_name']; ?> (Admin)</h2>
    <a href="logout.php" class="btn-gold">Logout</a>
  </header>
  <hr>

  <!-- Exemplo: Gerenciar usuários -->
  <section>
    <h3 style="color:#fff;">Manage Users</h3><br>
    <a href="form_novo_usuario.php" class="btn-gold">Add New User</a>
    <br><br>

    <?php
    require_once 'conexao.php';
    $sql = "SELECT * FROM users ORDER BY id DESC";
    $result = $conn->query($sql);

    echo "<table class='payments-table'>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Edit</th><th>Delete</th></tr></thead>";
    echo "<tbody>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td><a href='dashboard_edicao.php?id={$row['id']}' class='btn-gold'>Edit</a></td>";
        echo "<td><a href='excluir_usuario.php?id={$row['id']}' class='btn-gold' onclick=\"return confirm('Tem certeza?');\">Delete</a></td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    ?>
  </section>

  <hr>

  <!-- Exemplo: Gerenciar dados do index.html -->
  <section>
    <h3 style="color:#fff;"></h3>
    <!-- Você pode criar um form para gerenciar por ex. estatísticas (overview)
         e outra tabela para "payments-info" etc. -->
    <!-- Logicamente, você vai salvar no BD e depois no script.js você recupera
         fazendo fetch em algo tipo fetch_data.php. -->
  </section>
</div>
</body>
</html>