<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php?error=User not found.");
    exit;
}

$user_id = intval($_GET['id']);
$editing_id = $_GET['edit_id'] ?? null;

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: admin_dashboard.php?error=User not found.");
    exit;
}
$stmt->close();

// Inserir novo pagamento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_payment'])) {
    $payment_value = $_POST['payment_value'] ?? 0;
    $referral_name_payment = trim($_POST['referral_name'] ?? '');
    $payment_date = $_POST['payment_date'] ?? null;

    $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_value, referral_name, payment_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $payment_value, $referral_name_payment, $payment_date);
    $stmt->execute();
    $stmt->close();

    header("Location: adicionar_pagamento.php?id=$user_id&success=Payment added successfully!");
    exit;
}

// Editar pagamento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $payment_value = $_POST['payment_value'] ?? 0;
    $referral_name_payment = trim($_POST['referral_name'] ?? '');
    $payment_date = $_POST['payment_date'] ?? null;

    $stmt = $conn->prepare("UPDATE payments SET payment_value = ?, referral_name = ?, payment_date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dssii", $payment_value, $referral_name_payment, $payment_date, $payment_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: adicionar_pagamento.php?id=$user_id&success=Payment updated!");
    exit;
}

// Lista de pagamentos
$stmt = $conn->prepare("SELECT id, payment_value, referral_name, payment_date FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Payment</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Montserrat', sans-serif;
      background: transparent;
      color: #333;
      padding: 16px;
    }
    .bento-box {
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 20px;
    }
    h2 {
      font-size: 1.25rem;
      margin-bottom: 10px;
      color: #11284B;
    }
    p {
      margin-bottom: 10px;
      color: green;
    }
    form {
      display: grid;
      gap: 12px;
      margin-bottom: 24px;
    }
    label {
      font-weight: 600;
      font-size: 0.95rem;
    }
    input {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
    }
    button {
      background-color: #FFC107;
      color: #11284B;
      font-weight: bold;
      padding: 10px 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }
    button:hover {
      background-color: #e6ad06;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
      background: #f9f9f9;
      border-radius: 10px;
      overflow: hidden;
    }
    th, td {
      text-align: left;
      padding: 10px;
      border-bottom: 1px solid #eaeaea;
    }
    th {
      background-color: #f1f1f1;
      font-weight: 600;
    }
    a {
      margin-left: 8px;
      text-decoration: none;
      color: #11284B;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="bento-box">
    <h2>Add Payment for <?= htmlspecialchars($user['name']) ?></h2>

    <?php if (isset($_GET['success'])) : ?>
        <p><?= htmlspecialchars($_GET['success']) ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="add_payment" value="1">
      <label>Payment Amount:</label>
      <input type="number" step="0.01" name="payment_value" required>
      <label>Referral Name:</label>
      <input type="text" name="referral_name" required>
      <label>Payment Date:</label>
      <input type="date" name="payment_date" required>
      <button type="submit">Add</button>
    </form>

    <h3>Payments Made</h3>
    <table>
      <thead>
        <tr>
          <th>Amount</th>
          <th>Referral Name</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <?php if ($editing_id == $p['id']) : ?>
          <tr>
            <form method="POST">
              <input type="hidden" name="edit_payment" value="1">
              <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
              <td><input type="number" step="0.01" name="payment_value" value="<?= $p['payment_value'] ?>" required></td>
              <td><input type="text" name="referral_name" value="<?= htmlspecialchars($p['referral_name']) ?>" required></td>
              <td><input type="date" name="payment_date" value="<?= $p['payment_date'] ?>" required></td>
              <td>
                <button type="submit">Save</button>
                <a href="adicionar_pagamento.php?id=<?= $user_id ?>">Cancel</a>
              </td>
            </form>
          </tr>
          <?php else: ?>
          <tr>
            <td>$ <?= number_format($p['payment_value'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($p['referral_name']) ?></td>
            <td><?= $p['payment_date'] ?></td>
            <td>
              <a href="adicionar_pagamento.php?id=<?= $user_id ?>&edit_id=<?= $p['id'] ?>">Edit</a>
            </td>
          </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>