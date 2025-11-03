<?php
session_start();
require 'db.php';

$apiToken = 'f7decd4cf2fe2e1fac8e7843dc67e4a315432d9e';
$apiUrl = 'https://sms.iprogtech.com/api/v1/sms_messages';

$error = "";
$step = $_SESSION['reset_step'] ?? 'request';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $phone = preg_replace('/\D/', '', $_POST['phone']);

    if (strpos($phone, '63') !== 0) {
        $phone = '63' . ltrim($phone, '0');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999);
        $pdo->prepare("UPDATE users SET otp_code = ? WHERE phone = ?")->execute([$otp, $phone]);

        $message = "Your LearnTogether password reset code is $otp";

        $data = [
            'api_token' => $apiToken,
            'message' => $message,
            'phone_number' => $phone
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200 || $status === 201) {
            $_SESSION['reset_phone'] = $phone;
            $_SESSION['reset_step'] = 'verify';
            header("Location: forgot_password.php");
            exit;
        } else {
            $error = "Failed to send OTP. Please try again later.";
        }
    } else {
        $error = "No account found with that phone number.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = implode('', $_POST['otp']);
    $phone = $_SESSION['reset_phone'] ?? null;

    if ($phone) {
        $stmt = $pdo->prepare("SELECT otp_code FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user && $user['otp_code'] == $entered_otp) {
            $_SESSION['reset_step'] = 'reset';
            $pdo->prepare("UPDATE users SET otp_code = NULL WHERE phone = ?")->execute([$phone]);
            header("Location: forgot_password.php");
            exit;
        } else {
            $error = "Invalid or expired OTP.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    $phone = $_SESSION['reset_phone'] ?? null;

    if ($phone) {
        $pdo->prepare("UPDATE users SET password = ? WHERE phone = ?")->execute([$password, $phone]);
        unset($_SESSION['reset_phone'], $_SESSION['reset_step']);
        $_SESSION['reset_step'] = 'done';
        header("Location: forgot_password.php");
        exit;
    } else {
        $error = "Session expired. Please restart the process.";
    }
}

if (($_SESSION['reset_step'] ?? '') === 'done') {
    unset($_SESSION['reset_phone'], $_SESSION['reset_step']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | LearnTogether</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="CSS/signup.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

<?php if ($step === 'request'): ?>
<div class="card shadow p-4 auth-card active">
  <h2 class="mb-3 text-center">Forgot Password</h2>
  <p class="text-muted text-center">Enter your phone number to receive a reset code.</p>

  <?php if ($error): ?><p class="text-danger text-center"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form method="POST">
    <input type="text" name="phone" class="form-control mb-3" placeholder="Phone Number (e.g. 09123456789)" required>
    <button type="submit" name="send_otp" class="btn btn-success w-100">Send OTP</button>
  </form>

  <div class="text-center mt-3">
    <a href="login.php">← Back to Login</a>
  </div>
</div>
<?php endif; ?>

<?php if ($step === 'verify'): ?>
<div class="card shadow p-4 auth-card active text-center">
  <h2 class="mb-3">Verify OTP</h2>
  <p class="text-muted">Enter the 6-digit code sent to your phone.</p>

  <?php if ($error): ?><p class="text-danger"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form method="POST">
    <div class="d-flex justify-content-center gap-2 my-3">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" maxlength="1" name="otp[]" class="form-control otp-input" required>
      <?php endfor; ?>
    </div>
    <button type="submit" name="verify_otp" class="btn btn-success w-100">Verify</button>
  </form>
</div>
<?php endif; ?>

<?php if ($step === 'reset'): ?>
<div class="card shadow p-4 auth-card active text-center">
  <h2 class="mb-3">Reset Password</h2>
  <p class="text-muted">Enter your new password.</p>

  <?php if ($error): ?><p class="text-danger"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form method="POST">
    <input type="password" name="new_password" class="form-control mb-3" placeholder="New Password" required minlength="6">
    <button type="submit" name="reset_password" class="btn btn-success w-100">Reset Password</button>
  </form>
</div>
<?php endif; ?>

<?php if ($step === 'done'): ?>
<div class="card shadow p-4 auth-card active text-center">
  <h2 class="mb-3 text-success">✅ Password Updated!</h2>
  <p>You can now log in using your new password.</p>
  <a href="login.php" class="btn btn-success w-100">Go to Login</a>
</div>
<?php endif; ?>

<script>
const otpInputs = document.querySelectorAll('.otp-input');
otpInputs.forEach((input, index) => {
  input.addEventListener('input', (e) => {
    if (e.target.value.length === 1 && index < otpInputs.length - 1) {
      otpInputs[index + 1].focus();
    }
  });
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !e.target.value && index > 0) {
      otpInputs[index - 1].focus();
    }
  });
});
</script>

</body>
</html>
