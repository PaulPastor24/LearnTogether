<?php
session_start();
require 'db.php'; // Database connection

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if verified
        if ($user['verified'] == 0) {
            $error = "Please verify your account first via OTP.";
        } elseif (password_verify($password, $user['password'])) {
            // Valid login — create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In - LearnTogether</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .login-card {
      max-width: 400px;
      width: 100%;
      border-radius: 12px;
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-light">

  <div class="card shadow p-4 login-card">
    <h2 class="mb-3 text-center">Log In</h2>
    <p class="text-muted text-center">
      Access your <span class="fw-bold text-success">LearnTogether</span> account
    </p>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-success w-100">Log In</button>
    </form>

    <div class="mt-3 text-center">
      <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a><br>
      <span>Don’t have an account? <a href="signup.php">Sign Up</a></span>
    </div>
  </div>

</body>
</html>
