<?php
session_start();
require 'db.php';

$apiToken = 'f7decd4cf2fe2e1fac8e7843dc67e4a315432d9e';
$apiUrl = 'https://sms.iprogtech.com/api/v1/sms_messages';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    
    if (strpos($phone, '63') !== 0) {
        $phone = '63' . ltrim($phone, '0');
    }

    $otp = rand(100000, 999999);

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, phone, verified, otp_code) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $stmt->execute([$first, $last, $email, $password, $phone, $otp]);

    $message = "Your LearnTogether verification code is $otp";
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
        $_SESSION['user_email'] = $email;
        $_SESSION['step'] = 'verify';
        header("Location: signup.php");
        exit;
    } else {
        $error = "OTP failed to send. Try again later.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $entered_otp = implode('', $_POST['otp']);
    $email = $_SESSION['user_email'] ?? null;

    if ($email) {
        $stmt = $pdo->prepare("SELECT otp_code FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['otp_code'] == $entered_otp) {
            $pdo->prepare("UPDATE users SET verified = 1, otp_code = NULL WHERE email = ?")->execute([$email]);
            $_SESSION['step'] = 'complete';
        } else {
            $error = "Invalid or expired OTP. Please try again.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back_to_create'])) {
    unset($_SESSION['step']);
    header("Location: signup.php");
    exit;
}

if (($_SESSION['step'] ?? '') === 'complete') {
    unset($_SESSION['user_email']);
}

$step = $_SESSION['step'] ?? 'create';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LearnTogether Signup</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="CSS/signup.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

<?php if ($step === 'create'): ?>
<div id="create" class="card shadow p-4 auth-card active">
  <div class="d-flex justify-content-between mb-3 text-muted">
    <span class="fw-bold text-success">1 Create</span>
    <span>2 Confirm</span>
    <span>3 Complete</span>
  </div>

  <h2 class="mb-3">Create Your Account</h2>
  <p class="text-muted">Join the <span class="fw-bold text-success">LearnTogether</span> community!</p>

  <?php if ($error): ?>
    <p class="text-danger"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form id="signupForm" method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
      </div>
      <div class="col-md-6">
        <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
      </div>
      <div class="col-md-12">
        <input type="email" name="email" class="form-control" placeholder="Email Address" required>
      </div>
      <div class="col-md-12">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <div class="col-md-12">
        <input type="text" name="phone" class="form-control" placeholder="Phone Number (e.g. 09123456789)" required>
      </div>
    </div>
    <button type="submit" name="create_account" class="btn btn-success w-100 mt-3">Sign Up</button>
  </form>

  <div class="mt-3 text-center">
    Already have an account? <a href="login.php">Log In</a>
  </div>
</div>
<?php endif; ?>

<?php if ($step === 'verify'): ?>
<div id="verify" class="card shadow p-4 auth-card active text-center">
  <div class="d-flex justify-content-between mb-3 text-muted">
    <span>1 Create</span>
    <span class="fw-bold text-success">2 Confirm</span>
    <span>3 Complete</span>
  </div>

  <h2 class="mb-3">Secure Your Account</h2>
  <p class="text-muted">Enter the 6-digit code sent to your phone</p>

  <?php if ($error): ?>
    <p class="text-danger"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <div class="d-flex justify-content-center gap-2 my-3">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" maxlength="1" name="otp[]" class="form-control otp-input" required>
      <?php endfor; ?>
    </div>
    <button type="submit" name="verify_code" class="btn btn-success w-100">Confirm</button>
  </form>

  <form method="POST">
    <button type="submit" name="back_to_create" class="btn btn-link text-secondary mt-3">
      ← Back to Create Account
    </button>
  </form>
</div>
<?php endif; ?>

<?php if ($step === 'complete'): ?>
<div id="complete" class="card shadow p-4 auth-card active text-center">
  <div class="d-flex justify-content-between mb-3 text-muted">
    <span>1 Create</span>
    <span>2 Confirm</span>
    <span class="fw-bold text-success">3 Complete</span>
  </div>

  <h2 class="mb-3 text-success">✅ Your Account is Ready!</h2>
  <p>Welcome to the community! You can now securely access all features and connect with your peers.</p>
  <a href="login.php" class="btn btn-success w-100">Go to login</a>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
  $('#signupForm').on('submit', function(e) {
    let phone = $('input[name="phone"]').val();
    let password = $('input[name="password"]').val();

    if (!/^(09|\+639)\d{9}$/.test(phone)) {
      alert("Please enter a valid phone number (e.g. 09123456789 or +639123456789)");
      e.preventDefault();
    }

    if (password.length < 6) {
      alert("Password must be at least 6 characters.");
      e.preventDefault();
    }
  });

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
});
</script>
</body>
</html>
