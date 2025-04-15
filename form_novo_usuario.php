<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'conexao.php';

function calcularCategoria($successful) {
    if ($successful <= 20) return "Blue Topaz";
    if ($successful >= 21 && $successful <= 50) return "Blue Tanzanite";
    if ($successful > 99) return "Blue Sapphire";
    return "N/A";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $sobrenome = trim($_POST['sobrenome']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user_type = $_POST['user_type'];
    $referral_code = !empty($_POST['referral_code']) ? trim($_POST['referral_code']) : NULL;
    $created_at = date("Y-m-d H:i:s");

    $total_referrals = $_POST['total_referrals'] ?? 0;
    $successful = $_POST['successful'] ?? 0;
    $unsuccessful = $_POST['unsuccessful'] ?? 0;
    $pending = $_POST['pending'] ?? 0;
    $in_negotiation = $_POST['in_negotiation'] ?? 0;
    $ranking_info = $_POST['ranking_info'] ?? 0;

    $total_received = isset($_POST['total_received']) ? floatval($_POST['total_received']) : 0.0;
    $next_due = isset($_POST['next_due']) && is_numeric($_POST['next_due']) 
        ? number_format($_POST['next_due'], 2, '.', '') 
        : '0.00';

    $club_category = calcularCategoria($successful);

    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $error = "This email is already registered!";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO users (name, sobrenome, email, password, user_type, referral_code, club_category, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssss", $name, $sobrenome, $email, $password, $user_type, $referral_code, $club_category, $created_at);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            $stmt = $conn->prepare("
                INSERT INTO user_stats (user_id, total_referrals, successful, unsuccessful, pending, in_negotiation, ranking_info)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiiiiii", $user_id, $total_referrals, $successful, $unsuccessful, $pending, $in_negotiation, $ranking_info);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO user_payments_info (user_id, total_received, next_due)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE total_received = VALUES(total_received), next_due = VALUES(next_due)
            ");
            $stmt->bind_param("ids", $user_id, $total_received, $next_due);
            $stmt->execute();
            $stmt->close();

            header("Location: admin_dashboard.php?success=User created successfully!");
            exit;
        } else {
            $error = "Error creating user: " . $stmt->error;
        }
    }
    $checkEmail->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>
<div class="container">
    <header>
        <h2 style="color:#fff;">Add New User</h2>
        <a href="admin_dashboard.php" class="btn-gold">Back</a>
    </header>
    <hr>

    <?php if (isset($error)) : ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="form_novo_usuario.php" method="POST">
        <label for="name">First Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="sobrenome">Last Name:</label>
        <input type="text" id="sobrenome" name="sobrenome" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="user_type">User Type:</label>
        <select id="user_type" name="user_type">
            <option value="user">User</option>
            <option value="admin">Administrator</option>
        </select>

        <label for="referral_code">Referral Code:</label>
        <input type="text" id="referral_code" name="referral_code">

        <h3 style="color:#fff;">User Statistics</h3>
        <label for="total_referrals">Total Referrals:</label>
        <input type="number" id="total_referrals" name="total_referrals" value="0">

        <label for="successful">Successful:</label>
        <input type="number" id="successful" name="successful" value="0">

        <label for="unsuccessful">Unsuccessful:</label>
        <input type="number" id="unsuccessful" name="unsuccessful" value="0">

        <label for="pending">Pending:</label>
        <input type="number" id="pending" name="pending" value="0">

        <label for="in_negotiation">In Negotiation:</label>
        <input type="number" id="in_negotiation" name="in_negotiation" value="0">

        <label for="ranking_info">Ranking Info:</label>
        <input type="number" id="ranking_info" name="ranking_info" value="0">

        <button type="submit" class="btn-gold">Insert User</button>
    </form>
</div>
</body>
</html>