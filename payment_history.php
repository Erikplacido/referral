<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; // Obtém o tipo de usuário

// Buscar o nome do usuário
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Buscar todos os períodos disponíveis
$stmt = $conn->prepare("SELECT DISTINCT period_start, period_end FROM payment_history WHERE user_id = ? ORDER BY period_end DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$periods = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Buscar os pagamentos de um período específico
$payments = [];
$total_next_due = 0;
$period_start = $period_end = null;

if (isset($_GET['period_start']) && isset($_GET['period_end'])) {
    $period_start = $_GET['period_start'];
    $period_end = $_GET['period_end'];

    $stmt = $conn->prepare("SELECT payment_value, referral_name, payment_date FROM payment_history WHERE user_id = ? AND period_start = ? AND period_end = ?");
    $stmt->bind_param("iss", $user_id, $period_start, $period_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($payments as $p) {
        $total_next_due += (float)$p['payment_value'];
    }
}

// Executa manualmente os eventos se o admin clicar nos botões
if ($user_type === 'admin' && isset($_POST['execute_event'])) {
    $event = $_POST['execute_event'];

    if ($event === 'save_history') {
        $conn->query("CALL save_payment_history()");
        header("Location: payment_history.php?success=Histórico salvo manualmente!");
        exit;
    } elseif ($event === 'reset_payments') {
        $conn->query("CALL reset_payments()");
        header("Location: payment_history.php?success=Pagamentos zerados manualmente!");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Pagamentos</title>
    <link rel="stylesheet" href="style_payment.css">
</head>
<body>
<div class="container">
    <header>
        <h2>Payment History</h2>
        <p class="user-info">User: <strong><?= htmlspecialchars($user_name) ?></strong></p>
    </header>
    <hr>

    <?php if (isset($_GET['success'])) : ?>
        <p class="success-message"><?= htmlspecialchars($_GET['success']) ?></p>
    <?php endif; ?>

    <?php if ($user_type === 'admin') : ?>
        <form method="POST" class="admin-actions">
            <button type="submit" name="execute_event" value="save_history" class="btn-gold">Salvar Histórico</button>
            <button type="submit" name="execute_event" value="reset_payments" class="btn-danger">Zerar Pagamentos</button>
        </form>
        <hr>
    <?php endif; ?>

    <h3>Select a period:</h3>
    <ul class="period-list">
        <?php if (!empty($periods)) : ?>
            <?php foreach ($periods as $period) : ?>
                <li>
                    <a href="?period_start=<?= $period['period_start'] ?>&period_end=<?= $period['period_end'] ?>">
                        <?= date("d/m/Y", strtotime($period['period_start'])) . " - " . date("d/m/Y", strtotime($period['period_end'])) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="no-data">No periods available.</p>
        <?php endif; ?>
    </ul>

    <hr>

    <?php if (!empty($payments)) : ?>
        <h3>Payments for the period <?= date("d/m/Y", strtotime($period_start)) . " - " . date("d/m/Y", strtotime($period_end)) ?></h3>
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
                        <td>R$ <?= number_format($payment['payment_value'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($payment['referral_name']) ?></td>
                        <td><?= date("d/m/Y", strtotime($payment['payment_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="total-receber">
            Total a receber neste período: <strong>R$ <?= number_format($total_next_due, 2, ',', '.') ?></strong>
        </p>

        <!-- Botão Exportar PDF -->
        <form action="export_payment_pdf.php" method="POST" target="_blank">
            <input type="hidden" name="period_start" value="<?= $period_start ?>">
            <input type="hidden" name="period_end" value="<?= $period_end ?>">
            <button type="submit" class="btn-gold">Exportar PDF</button>
        </form>

    <?php elseif ($period_start && $period_end) : ?>
        <p class="no-data">No payments recorded for this period.</p>
    <?php endif; ?>
</div>
</body>
</html>