<?php
require_once 'conexao.php'; // Adiciona a conexão com o banco
session_start();

// Se não estiver logado, redirecionar:
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validar usuário no banco de dados
$user_id = $_SESSION['user_id'];
$query = "SELECT name, sobrenome, user_type FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    // Se o usuário não existir mais no banco, força logout
    session_destroy();
    header("Location: login.php");
    exit;
}

// Se quiser garantir que somente usuários comuns acessem:
if ($user['user_type'] !== 'user') {
    header("Location: admin_dashboard.php");
    exit;
}

// 1) Definir os cabeçalhos de segurança via header(), antes de enviar HTML:
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; img-src *;");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Blue Referral Club</title>
  
  <!-- 2) Removemos as meta http-equiv de X-Frame-Options e CSP,
       pois agora elas já estão definidas nos cabeçalhos HTTP. -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <?php
      if (isset($_SESSION['success_message'])) {
          echo "<div style='color: limegreen; text-align: center; margin-bottom: 10px;'>" . $_SESSION['success_message'] . "</div>";
          unset($_SESSION['success_message']);
      }
      if (isset($_SESSION['error_message'])) {
          echo "<div style='color: red; text-align: center; margin-bottom: 10px;'>" . $_SESSION['error_message'] . "</div>";
          unset($_SESSION['error_message']);
      }
    ?>
    <!-- Cabeçalho -->
    <header>
      <div class="user-details">
        <img class="user-icon" src="https://bluefacilityservices.com.au/wp-content/uploads/2024/10/default_icon.png" alt="User Icon">
        <div class="user-text">
          <h1>Hello! <span class="user-name"><?php echo htmlspecialchars($user['name'] . ' ' . $user['sobrenome']); ?></span></h1>
          <p>Seu referral code é <strong data-referral-code=""></strong></p>
          <p>Your category in the club is <strong data-club-category=""></strong></p>
          <p>Your position in the referral ranking is <strong data-referral-ranking=""></strong></p>
          <p id="ranking-info" class="ranking-info">Ranking: <span class="ranking-value" data-ranking-info>Load...</span></p>
          
          <!-- ✅ Botão para compartilhar o referral -->
          <button id="shareReferralBtn" class="btn-gold" style="margin-top: 10px;">
            Share your Referral Code
          </button>
          <p id="referralFeedback" style="color: limegreen; display: none;">Link copied! Ready to share 🎉</p>
        </div>
      </div>
      <div class="header-buttons">
        <button class="btn-gold" aria-label="View Invoice" onclick="window.location.href='https://bluefacilityservices.com.au/referralclub/referral.php'">Give a referral</button>
        <button class="btn-gold" aria-label="View Payment History" onclick="window.open('payment_history.php', '_blank');">Payment history</button>
        <button class="btn-gold icon-btn settings-icon" title="Settings" aria-label="Settings">&#9881;</button>
        <button class="btn-gold icon-btn logout-icon" title="Logout" aria-label="Logout">X</button>
      </div>
    </header>
    
    <hr>
    
    <!-- Seção Overview -->
    <section class="overview">
      <h2>Overview</h2>
      <div class="stats-container">
        <div class="stat-box" data-type="total-referrals">
          <div class="stat-number" data-total-referrals="">Load...</div>
          <div class="stat-label">Total Referrals</div>
        </div>
        <div class="stat-box" data-type="successful">
          <div class="stat-number" data-successful="">Load...</div>
          <div class="stat-label">Successful</div>
        </div>
        <div class="stat-box" data-type="unsuccessful">
          <div class="stat-number" data-unsuccessful="">Load...</div>
          <div class="stat-label">Unsuccessful</div>
        </div>
        <div class="stat-box" data-type="pending">
          <div class="stat-number" data-pending="">Load...</div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box" data-type="in-negotiation">
          <div class="stat-number" data-in-negotiation="">Load...</div>
          <div class="stat-label">In Negotiation</div>
        </div>
      </div>
    </section>
    
    <!-- Seção de pagamentos -->
    <div class="payments-info">
      <div class="payment-line" data-payment="total-received">
        <p>Total earnings</p>
        <div class="dot-separator"></div>
        <div class="payment-amount" data-total-received="">Load...</div>
      </div>

      <div class="payment-line" data-payment="next-due">
        <p>Upcoming payment</p>
        <div class="dot-separator"></div>
        <div class="payment-amount" data-next-due="">Load...</div>
      </div>

      <table class="payments-table">
        <thead>
          <tr>
            <th>Payment Value</th>
            <th>Referral Name</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody id="payments-body">
          <!-- Linhas da tabela serão adicionadas dinamicamente -->
        </tbody>
      </table>
    </div> <!-- ✅ Fechamento correto da <div class="payments-info"> -->

    <!-- Modal de Settings -->
    <div id="settingsModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-tabs">
          <button class="tab-btn active" data-tab="paymentHistory">Payment History</button>
          <button class="tab-btn" data-tab="changePassword">Change Password</button>
        </div>
        <div class="modal-tab-content">
          <!-- Aba Payment History -->
<div id="paymentHistory" class="tab-content active">
  <h2>Bank Account Details</h2>
  <form id="paymentHistoryForm" method="POST" action="save_bank_details.php">
    <?php
      // verifica se já há dados
      $hasData = strlen(trim($bankName . $agency . $bsb . $accountNumber . $abnNumber)) > 0;
    ?>
    <div class="form-group">
      <input
        type="text"
        id="bankName"
        name="bankName"
        placeholder="Enter Bank Name"
        value="<?php echo htmlspecialchars($bankName); ?>"
        <?php echo $hasData ? 'disabled' : ''; ?>>
    </div>
    <div class="form-group">
      <label for="agency">Agency</label>
      <input
        type="text"
        id="agency"
        name="agency"
        placeholder="Enter Agency"
        value="<?php echo htmlspecialchars($agency); ?>"
        <?php echo $hasData ? 'disabled' : ''; ?>>
    </div>
    <div class="form-group">
      <label for="bsb">BSB</label>
      <input
        type="text"
        id="bsb"
        name="bsb"
        placeholder="Enter BSB"
        value="<?php echo htmlspecialchars($bsb); ?>"
        <?php echo $hasData ? 'disabled' : ''; ?>>
    </div>
    <div class="form-group">
      <label for="accountNumber">Account Number</label>
      <input
        type="text"
        id="accountNumber"
        name="accountNumber"
        placeholder="Enter Account Number"
        value="<?php echo htmlspecialchars($accountNumber); ?>"
        <?php echo $hasData ? 'disabled' : ''; ?>>
    </div>
    <div class="form-group">
      <label for="abnNumber">ABN Number</label>
      <input
        type="text"
        id="abnNumber"
        name="abnNumber"
        placeholder="Enter ABN Number"
        value="<?php echo htmlspecialchars($abnNumber); ?>"
        <?php echo $hasData ? 'disabled' : ''; ?>>
    </div>

    <!-- Só exibe o botão Edit se já tiver dados -->
    <?php if ($hasData): ?>
      <button type="button" id="editBankDetails" class="btn-gold">Edit</button>
    <?php endif; ?>

    <button type="submit" class="btn-gold">
      <?php echo $hasData ? 'Update' : 'Save'; ?>
    </button>
  </form>
</div>

          <!-- Aba Change Password -->
          <div id="changePassword" class="tab-content">
            <h2>Change Password</h2>
            <form id="changePasswordForm" method="POST" action="update_password.php">
              <div class="form-group">
                <label for="currentPassword">Current Password</label>
                <div class="input-icon-wrapper">
                  <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter current password" required>
                </div>
              </div>
              <div class="form-group">
                <label for="newPassword">New Password</label>
                <div class="input-icon-wrapper">
                  <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" required>
                </div>
              </div>
              <div class="form-group">
                <label for="confirmPassword">Confirm New Password</label>
                <div class="input-icon-wrapper">
                  <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                </div>
              </div>

              <!-- 👁️ Botão único abaixo dos campos -->
              <div class="form-group toggle-password-group">
                <button type="button" class="toggle-password" onclick="toggleAllPasswords(this)">
                  <img src="assets/img/eye.svg" id="eyeIcon" alt="Show Passwords" width="24" height="24">
                  <span id="toggleText" style="margin-left: 8px;">Show Password</span>
                </button>
              </div>

              <button type="submit" class="btn-gold">Send</button>
            </form>

            <p style="margin-top: 15px;">
              <a href="#" id="forgotPasswordLink">Forgot password</a>
            </p>

            <form id="forgotPasswordForm" method="POST" action="send_reset_email.php" style="display: none; margin-top: 20px;">
              <div class="form-group">
                <label for="resetEmail">Enter your email to reset</label>
                <input type="email" id="resetEmail" name="resetEmail" placeholder="your email" required>
              </div>
              <button type="submit" class="btn-gold">Send reset email</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div> <!-- Fim da .container -->

  <!-- Inclusão do script externo -->
  <script src="script.js"></script>
  <script src="script_total_received.js"></script>
  <script src="script_next_due.js"></script>
  <script src="script_list_received.js"></script>
  <script src="script_ranking.js"></script>
  <script src="script_club.js"></script>
  <script src="script_posicao.js"></script>
  <script src="script_password.js"></script>
</body>
</html>