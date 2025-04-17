<?php
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
$feedback = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name            = trim($_POST['name'] ?? '');
    $sobrenome       = trim($_POST['sobrenome'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $user_type       = $_POST['user_type'] ?? 'user';
    $referral_code   = trim($_POST['referral_code'] ?? '');
    $club_category   = trim($_POST['club_category'] ?? '');
    $total_referrals = intval($_POST['total_referrals'] ?? 0);
    $successful      = intval($_POST['successful'] ?? 0);
    $unsuccessful    = intval($_POST['unsuccessful'] ?? 0);
    $pending         = intval($_POST['pending'] ?? 0);
    $in_negotiation  = intval($_POST['in_negotiation'] ?? 0);

    // Atualiza tabela users
    $stmt = $conn->prepare("
        UPDATE users 
           SET name = ?, 
               sobrenome = ?, 
               email = ?, 
               user_type = ?, 
               referral_code = ?, 
               club_category = ?
         WHERE id = ?
    ");
    $stmt->bind_param("ssssssi", $name, $sobrenome, $email, $user_type, $referral_code, $club_category, $user_id);
    $stmt->execute();
    $stmt->close();

    // Atualiza tabela user_stats (sem ranking_info)
    $stmt = $conn->prepare("
        UPDATE user_stats 
           SET total_referrals = ?, 
               successful = ?, 
               unsuccessful = ?, 
               pending = ?, 
               in_negotiation = ?
         WHERE user_id = ?
    ");
    $stmt->bind_param("iiiiii", $total_referrals, $successful, $unsuccessful, $pending, $in_negotiation, $user_id);
    $stmt->execute();
    $stmt->close();

    $feedback = "Data updated successfully!";
}

// Pega os dados para exibir no formulário
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.name, 
        u.sobrenome,
        u.email, 
        u.user_type, 
        u.referral_code, 
        u.club_category,
        s.total_referrals, 
        s.successful, 
        s.unsuccessful, 
        s.pending, 
        s.in_negotiation
    FROM users u
    LEFT JOIN user_stats s ON (u.id = s.user_id)
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found in the database.");
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User Data</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0; 
      padding: 0; 
      box-sizing: border-box;
    }
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
      max-width: 650px;
      margin: 0 auto;
    }
   h2 {
      font-size: 16px;
      margin-bottom: 10px;
      color: #11284B;
    }
    .feedback {
      background: #defad3;
      color: #116700;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 10px;
      font-weight: bold;
    }
    form {
      display: grid;
      grid-template-columns: 130px 1fr;
      gap: 10px 16px;
      align-items: center;
      margin-bottom: 24px;
    }
    label {
      font-weight: 600;
      font-size: 0.95rem;
      text-align: left;
    }
    input, select {
      padding: 4px;
      font-size: 11px;
      height: 24px;
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 6px;
      box-sizing: border-box;
    }
    h3 {
      grid-column: 1 / 3;
      margin-top: 20px;
      margin-bottom: 6px;
      font-size: 1.1rem;
      color: #11284B;
    }
    button {
      grid-column: 2;
      background-color: #FFC107;
      color: #11284B;
      font-weight: bold;
      padding: 8px 16px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: 0.2s;
      width: fit-content;
    }
    button:hover {
      background-color: #e6ad06;
    }
  </style>
</head>
<body>
  <div class="bento-box">
    <h2>Edit User Data</h2>

    <?php if (!empty($feedback)): ?>
      <div class="feedback"><?= htmlspecialchars($feedback) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="name">Name:</label>
      <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label for="sobrenome">Lastname:</label>
      <input type="text" name="sobrenome" value="<?= htmlspecialchars($user['sobrenome']) ?>" required>

      <label for="email">Email:</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label for="user_type">Type:</label>
      <select name="user_type">
        <option value="user"  <?= ($user['user_type'] === 'user')  ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= ($user['user_type'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
      </select>

      <label for="referral_code">Referral:</label>
      <input type="text" name="referral_code" value="<?= htmlspecialchars($user['referral_code']) ?>">

      <label for="club_category">Category:</label>
      <input type="text" name="club_category" value="<?= htmlspecialchars($user['club_category']) ?>">

      <h3>Statistics</h3>

      <label for="total_referrals">Total:</label>
      <input type="number" name="total_referrals" value="<?= intval($user['total_referrals']) ?>">

      <label for="successful">Successes:</label>
      <input type="number" name="successful" value="<?= intval($user['successful']) ?>">

      <label for="unsuccessful">Unsuccessful:</label>
      <input type="number" name="unsuccessful" value="<?= intval($user['unsuccessful']) ?>">

      <label for="pending">Pending:</label>
      <input type="number" name="pending" value="<?= intval($user['pending']) ?>">

      <label for="in_negotiation">Negotiating:</label>
      <input type="number" name="in_negotiation" value="<?= intval($user['in_negotiation']) ?>">

      <button type="submit">Save</button>
    </form>
  </div>
</body>
</html>