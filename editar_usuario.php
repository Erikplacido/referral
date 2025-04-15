<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Conexão com o banco de dados
require_once 'conexao.php';

// Verifica se foi passado um ID válido na URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php?error=Usuário não encontrado.");
    exit;
}

$user_id = intval($_GET['id']);

// Busca os dados do usuário no banco (busca um registro para preencher os formulários)
$stmt = $conn->prepare("
    SELECT         
        u.id, 
        u.name,
        u.sobrenome,
        u.email, 
        u.user_type, 
        u.referral_code, 
        u.club_category, 
        u.created_at,
        s.total_referrals, 
        s.successful, 
        s.unsuccessful, 
        s.pending, 
        s.in_negotiation, 
        s.ranking_info,
        p.payment_value, 
        p.referral_name, 
        p.payment_date,
        pi.total_received, 
        pi.next_due
    FROM users u
    LEFT JOIN user_stats s ON u.id = s.user_id
    LEFT JOIN payments p ON u.id = p.user_id
    LEFT JOIN user_payments_info pi ON u.id = pi.user_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: admin_dashboard.php?error=Usuário não encontrado.");
    exit;
}
$stmt->close();

// Processamento dos formulários
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Atualização dos dados do usuário (inclui dados da tabela users, user_stats, payments e user_payments_info)
    if (isset($_POST['update_user'])) {
        // Dados da tabela users
        $name = trim($_POST['name']);
        $sobrenome = trim($_POST['sobrenome']);
        $email = trim($_POST['email']);
        $user_type = $_POST['user_type'];
        $referral_code = !empty($_POST['referral_code']) ? trim($_POST['referral_code']) : NULL;
        $club_category = !empty($_POST['club_category']) ? trim($_POST['club_category']) : NULL;
        $referral_ranking = $_POST['referral_ranking'] ?? 0;

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, user_type = ?, referral_code = ?, club_category = ?, referral_ranking = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $email, $user_type, $referral_code, $club_category, $referral_ranking, $user_id);
        $stmt->execute();
        $stmt->close();

        // Dados da tabela user_stats
        $total_referrals = $_POST['total_referrals'] ?? 0;
        $successful = $_POST['successful'] ?? 0;
        $unsuccessful = $_POST['unsuccessful'] ?? 0;
        $pending = $_POST['pending'] ?? 0;
        $in_negotiation = $_POST['in_negotiation'] ?? 0;
        $ranking_info = $_POST['ranking_info'] ?? 0;

        $stmt = $conn->prepare("UPDATE user_stats SET total_referrals = ?, successful = ?, unsuccessful = ?, pending = ?, in_negotiation = ?, ranking_info = ? WHERE user_id = ?");
        $stmt->bind_param("iiiiiii", $total_referrals, $successful, $unsuccessful, $pending, $in_negotiation, $ranking_info, $user_id);
        $stmt->execute();
        $stmt->close();

        // Atualiza a(s) entrada(s) na tabela payments (atualiza os registros já existentes)
        $payment_value = $_POST['payment_value'] ?? 0;
        $referral_name_payment = !empty($_POST['referral_name']) ? trim($_POST['referral_name']) : NULL;
        $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : NULL;

        $stmt = $conn->prepare("UPDATE payments SET payment_value = ?, referral_name = ?, payment_date = ? WHERE user_id = ?");
        $stmt->bind_param("dssi", $payment_value, $referral_name_payment, $payment_date, $user_id);
        $stmt->execute();
        $stmt->close();

        // Dados da tabela user_payments_info
        $total_received = isset($_POST['total_received']) ? floatval($_POST['total_received']) : 0.0;
        $next_due = isset($_POST['next_due']) && is_numeric($_POST['next_due']) 
            ? number_format($_POST['next_due'], 2, '.', '') 
            : '0.00';

        $stmt = $conn->prepare("UPDATE user_payments_info SET total_received = ?, next_due = ? WHERE user_id = ?");
        $stmt->bind_param("dss", $total_received, $next_due, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: editar_usuario.php?id=$user_id&success=Usuário atualizado com sucesso!");
        exit;
    }

    // Adiciona novo pagamento sem substituir os anteriores
    if (isset($_POST['add_payment'])) {
        $payment_value = $_POST['payment_value'] ?? 0;
        $referral_name_payment = !empty($_POST['referral_name']) ? trim($_POST['referral_name']) : NULL;
        $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : NULL;

        $stmt = $conn->prepare("INSERT INTO payments (user_id, payment_value, referral_name, payment_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $user_id, $payment_value, $referral_name_payment, $payment_date);
        $stmt->execute();
        $stmt->close();

        header("Location: editar_usuario.php?id=$user_id&success=Pagamento adicionado!");
        exit;
    }
}

// Busca a lista de todos os pagamentos já realizados para o usuário
$stmt = $conn->prepare("SELECT id, payment_value, referral_name, payment_date FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>
<div class="container">
    <header>
        <h2 style="color:#fff;">Editar Usuário</h2>
        <a href="admin_dashboard.php" class="btn-gold">Voltar</a>
    </header>
    <hr>

    <?php if (isset($_GET['success'])) : ?>
        <p style="color: green;"><?= htmlspecialchars($_GET['success']) ?></p>
    <?php endif; ?>

    <!-- Formulário para Atualizar Dados do Usuário -->
    <form action="editar_usuario.php?id=<?= $user_id ?>" method="POST">
        <input type="hidden" name="update_user" value="1">

        <label for="name">Nome:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        
        <label for="sobrenome">Sobrenome:</label> <!-- 🔄 CAMPO NOVO -->
        <input type="text" id="sobrenome" name="sobrenome" value="<?= htmlspecialchars($user['sobrenome'] ?? '') ?>" required>

        <label for="email">E-mail:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label for="user_type">Tipo de Usuário:</label>
        <select id="user_type" name="user_type">
            <option value="user" <?= ($user['user_type'] === 'user') ? 'selected' : '' ?>>Usuário Comum</option>
            <option value="admin" <?= ($user['user_type'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
        </select>

        <label for="referral_code">Referral Code:</label>
        <input type="text" id="referral_code" name="referral_code" value="<?= htmlspecialchars($user['referral_code'] ?? '') ?>">

        <label for="club_category">Club Category:</label>
        <input type="text" id="club_category" name="club_category" value="<?= htmlspecialchars($user['club_category'] ?? '') ?>">


        <h3>Estatísticas do Usuário</h3>
        <label for="total_referrals">Total Referrals:</label>
        <input type="number" id="total_referrals" name="total_referrals" value="<?= $user['total_referrals'] ?>">

        <label for="successful">Successful:</label>
        <input type="number" id="successful" name="successful" value="<?= $user['successful'] ?>">

        <label for="unsuccessful">Unsuccessful:</label>
        <input type="number" id="unsuccessful" name="unsuccessful" value="<?= $user['unsuccessful'] ?>">

        <label for="pending">Pending:</label>
        <input type="number" id="pending" name="pending" value="<?= $user['pending'] ?>">

        <label for="in_negotiation">In Negotiation:</label>
        <input type="number" id="in_negotiation" name="in_negotiation" value="<?= $user['in_negotiation'] ?>">

        <label for="ranking_info">Ranking Info:</label>
        <input type="number" id="ranking_info" name="ranking_info" value="<?= $user['ranking_info'] ?>">

        <h3>Pagamento (Atualização do registro existente)</h3>

        <label for="total_received">Total Recebido:</label>
        <input type="number" step="0.01" id="total_received" name="total_received" value="<?= $user['total_received'] ?>">

        <label for="next_due">Próximo Vencimento:</label>
        <input type="number" id="next_due" name="next_due" value="<?= $user['next_due'] ?>">

        <button type="submit" class="btn-gold">Salvar Alterações</button>
    </form>

    <hr>

    <!-- Formulário para Adicionar Novo Pagamento (sem substituir os existentes) -->
    <h3>Adicionar Novo Pagamento</h3>
    <form action="editar_usuario.php?id=<?= $user_id ?>" method="POST">
        <input type="hidden" name="add_payment" value="1">

        <label for="payment_value_new">Valor do Pagamento:</label>
        <input type="number" step="0.01" id="payment_value_new" name="payment_value" required>

        <label for="referral_name_new">Nome do Indicado:</label>
        <input type="text" id="referral_name_new" name="referral_name" required>

        <label for="payment_date_new">Data do Pagamento:</label>
        <input type="date" id="payment_date_new" name="payment_date" required>

        <button type="submit" class="btn-gold">Adicionar Pagamento</button>
    </form>

    <hr>

    <!-- Tabela de Pagamentos Realizados -->
    <h3>Pagamentos Realizados</h3>
    <table>
        <thead>
            <tr>
                <th>Valor</th>
                <th>Nome do Indicado</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment) : ?>
                <tr>
                    <td>R$ <?= number_format($payment['payment_value'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($payment['referral_name']) ?></td>
                    <td><?= $payment['payment_date'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>