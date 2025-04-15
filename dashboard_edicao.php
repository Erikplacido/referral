<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    die("User ID not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      font-family: 'Montserrat', sans-serif;
      background: #f7f9fc;
      color: #333;
    }

    header {
      padding: 20px;
      background: #ffffff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h2 {
      color: #11284B;
      font-size: 1.5rem;
    }

    .btn-gold {
      background-color: #FFC107;
      border: none;
      color: #11284B;
      padding: 10px 18px;
      border-radius: 5px;
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      transition: background-color 0.2s ease;
    }

    .btn-gold:hover {
      background-color: #e6ad06;
    }

    .dashboard {
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .top-panels {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }

    .bottom-panel {
      width: 100%;
    }

    .panel {
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.04);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 420px;
    }

    .panel iframe {
      flex: 1;
      width: 100%;
      border: none;
    }

    .panel-title {
      padding: 16px;
      font-size: 1.1rem;
      font-weight: 600;
      color: #11284B;
      border-bottom: 1px solid #eee;
      background: #fafafa;
    }

    @media (max-width: 1024px) {
      .top-panels {
        grid-template-columns: 1fr;
      }

      .panel {
        height: 400px;
      }
    }
  </style>
</head>
<body>

  <header>
    <h2>Edit Dashboard</h2>
    <a href="admin_dashboard.php" class="btn-gold">← Back to Dashboard</a>
  </header>

  <div class="dashboard">

    <!-- TOPO: 3 colunas -->
    <div class="top-panels">
      <div class="panel">
        <div class="panel-title">User Data</div>
        <iframe src="dados_usuario.php?id=<?= $user_id ?>"></iframe>
      </div>

      <div class="panel">
        <div class="panel-title">Add Payment</div>
        <iframe src="adicionar_pagamento.php?id=<?= $user_id ?>"></iframe>
      </div>

      <div class="panel">
        <div class="panel-title">Payment Data</div>
        <iframe src="dados_pagamento.php?id=<?= $user_id ?>"></iframe>
      </div>
    </div>

    <!-- BASE: 100% -->
    <div class="bottom-panel panel">
      <div class="panel-title">Referrals</div>
      <iframe src="dados_referrals.php?id=<?= $user_id ?>"></iframe>
    </div>

  </div>

</body>
</html>