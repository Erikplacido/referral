<?php
// Display detailed errors in the development environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);
$user_type = $_SESSION['user_type'];

// Fetch the user's name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Fetch bank details
$stmt = $conn->prepare("
    SELECT 
        bank_name, 
        agency, 
        bsb, 
        account_number, 
        abn_number, 
        created_at, 
        updated_at
    FROM bank_details 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bank_data = $result->fetch_assoc();
$stmt->close();

// Fetch all available periods
$stmt = $conn->prepare("
    SELECT DISTINCT period_start, period_end
    FROM payment_history
    WHERE user_id = ?
    ORDER BY period_end DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$periods = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch payments for a specific period
$payments = [];
$period_start = null;
$period_end = null;
$total_next_due = 0.0;  // Corrected

if (isset($_GET['period_start']) && isset($_GET['period_end'])) {
    $period_start = $_GET['period_start'];
    $period_end   = $_GET['period_end'];

    $stmt = $conn->prepare("
        SELECT payment_value, referral_name, payment_date
        FROM payment_history
        WHERE user_id = ?
          AND period_start = ?
          AND period_end = ?
    ");
    $stmt->bind_param("iss", $user_id, $period_start, $period_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Sum total payments for this period
    foreach ($payments as $p) {
        $total_next_due += (float)$p['payment_value'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Data</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
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
    h2, h3 {
      font-size: 1.2rem;
      color: #11284B;
      margin-bottom: 12px;
    }
    .user-info {
      margin-bottom: 20px;
      font-weight: bold;
    }
    .summary-box {
      margin-bottom: 20px;
      background: #F8F9FB;
      border-left: 4px solid #FFC107;
      padding: 16px;
      border-radius: 12px;
    }
    .summary-box p {
      margin: 4px 0;
    }
    .period-list {
      list-style: none;
      padding: 0;
      display: grid;
      gap: 10px;
    }
    .period-list li a {
      display: block;
      padding: 10px;
      background: #f1f1f1;
      border-radius: 8px;
      text-decoration: none;
      color: #11284B;
      font-weight: 500;
    }
    .payment-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      background: #f9f9f9;
    }
    .payment-table th, .payment-table td {
      padding: 10px;
      border-bottom: 1px solid #e0e0e0;
      text-align: left;
    }
    .payment-table th {
      background: #f1f1f1;
      color: #11284B;
    }
    .total-receber {
      margin-top: 15px;
      font-weight: 600;
    }
    .btn-gold {
      background-color: #FFC107;
      color: #11284B;
      font-weight: bold;
      padding: 10px 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 10px;
    }
    .btn-gold:hover {
      background-color: #e6ad06;
    }
    .no-data {
      color: #999;
      font-style: italic;
    }
  </style>
</head>
<body>
  <div class="bento-box">
    <h2>Payment History</h2>
    <p class="user-info">User: <?= htmlspecialchars($user_name) ?></p>

    <?php if ($bank_data): ?>
      <div class="summary-box">
        <h3>Bank Account Summary</h3>
        <p><strong>Bank:</strong> <?= htmlspecialchars($bank_data['bank_name']) ?></p>
        <p>
          <strong>Agency:</strong> <?= htmlspecialchars($bank_data['agency']) ?>
          &nbsp;|&nbsp;
          <strong>BSB:</strong> <?= htmlspecialchars($bank_data['bsb']) ?>
        </p>
        <p>
          <strong>Account:</strong> <?= htmlspecialchars($bank_data['account_number']) ?>
          &nbsp;|&nbsp;
          <strong>ABN:</strong> <?= htmlspecialchars($bank_data['abn_number']) ?>
        </p>
        <p>
          <strong>Created on:</strong> 
          <?= $bank_data['created_at'] 
               ? date('d/m/Y H:i', strtotime($bank_data['created_at'])) 
               : 'N/A' ?>
          &nbsp;|&nbsp;
          <strong>Updated on:</strong> 
          <?= $bank_data['updated_at'] 
               ? date('d/m/Y H:i', strtotime($bank_data['updated_at'])) 
               : 'N/A' ?>
        </p>
      </div>
    <?php endif; ?>

    <h3>Select a period:</h3>
    <ul class="period-list">
      <?php if (!empty($periods)) : ?>
        <?php foreach ($periods as $period) : ?>
          <li>
            <a href="?id=<?= $user_id ?>&period_start=<?= urlencode($period['period_start']) ?>&period_end=<?= urlencode($period['period_end']) ?>">
              <?= date("d/m/Y", strtotime($period['period_start'])) 
                 . " - " 
                 . date("d/m/Y", strtotime($period['period_end'])) ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <p class="no-data">No periods available.</p>
      <?php endif; ?>
    </ul>

    <?php if ($period_start && $period_end): ?>
      <hr>
      <h3>Payments for the period 
        <?= date("d/m/Y", strtotime($period_start)) . " - " . date("d/m/Y", strtotime($period_end)) ?>
      </h3>
      <?php if (!empty($payments)) : ?>
        <table class="payment-table">
          <thead>
            <tr>
              <th>Amount</th>
              <th>Referral Name</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $payment) : ?>
              <tr>
                <td>$ <?= number_format($payment['payment_value'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($payment['referral_name']) ?></td>
                <td><?= date("d/m/Y", strtotime($payment['payment_date'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="total-receber">
          Total to receive in this period: 
          <strong>$ <?= number_format($total_next_due, 2, ',', '.') ?></strong>
        </p>
      <?php else: ?>
        <p class="no-data">No payments recorded for this period.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>