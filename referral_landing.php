<?php
$referralCode = basename($_SERVER['REQUEST_URI']); // ex: REF1234
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Join Blue Referral Club</title>
  <meta property="og:title" content="Discover Blue's Quality Service!" />
  <meta property="og:description" content="Join via a friend and experience excellence." />
  <meta property="og:image" content="https://bluefacilityservices.com.au/images/blue_referral_banner.jpg" />
  <meta property="og:url" content="https://bluefacilityservices.com.au/<?= htmlspecialchars($referralCode) ?>" />
  <meta name="twitter:card" content="summary_large_image">
  <style>
    body { font-family: Arial; background: #f4f4f4; padding: 20px; }
    form { background: #fff; padding: 20px; max-width: 500px; margin: auto; border-radius: 8px; }
    input, button { width: 100%; padding: 10px; margin: 10px 0; }
    button { background: #0073e6; color: white; border: none; cursor: pointer; }
  </style>
</head>
<body>
  <h2 style="text-align:center;">Discover the quality of Blue's services</h2>
  <p style="text-align:center;">Complete the form below and we’ll be in touch!</p>

  <form action="process_referral_form.php" method="POST">
    <input type="text" name="name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="text" name="mobile" placeholder="Mobile" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="city" placeholder="City" required>

    <!-- Hidden field with referral -->
    <input type="text" name="referral_code" readonly value="<?= htmlspecialchars($referralCode) ?>">

    <button type="submit">Send my contact</button>
  </form>
</body>
</html>