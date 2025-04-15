<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';
session_start();

// 🔐 Verificação de sessão e permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Access denied.");
}

// 🆔 Receber ID via GET ou fallback para sessão
$user_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die("Invalid user ID.");
}

// 🔍 Buscar nome do usuário na tabela correta
$stmtUser = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$result = $stmtUser->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}

$userData = $result->fetch_assoc();
$user_name = $userData['name'] ?? 'Desconhecido';

// 📊 Buscar indicações ligadas ao user_id
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, mobile, city, created_at 
    FROM potential_clients_referrals 
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$indicacoes = [];
while ($row = $result->fetch_assoc()) {
    $indicacoes[] = $row;
}
$total = count($indicacoes);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: 'Montserrat', sans-serif;
      padding: 16px;
      background: #fdfdfd;
    }
    h3 {
      margin-bottom: 10px;
      color: #11284B;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
      margin-top: 10px;
    }
    th, td {
      padding: 8px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #11284B;
      color: white;
    }
    .warning {
      background: #fff3cd;
      color: #856404;
      padding: 12px;
      border-radius: 6px;
    }
  </style>
</head>
<body>
  <h3>Indicações de <?= htmlspecialchars($user_name) ?>: <?= $total ?></h3>

  <?php if ($total > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>Email</th>
          <th>Mobile</th>
          <th>Cidade</th>
          <th>Data</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($indicacoes as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['mobile']) ?></td>
            <td><?= htmlspecialchars($row['city']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="warning">Nenhuma indicação registrada ainda para este usuário.</p>
  <?php endif; ?>
</body>
</html>