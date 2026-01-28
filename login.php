<?php
session_start();
require 'db.php';
require 'security.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {
        $email = trim($_POST['email']);
        if (!checkRateLimit($email, 5, 30)) {
            $error = "Too many login attempts. Please try again in 30 seconds.";
        } else {
            $password = $_POST['password'];

            if (!preg_match('/@g\.batstate-u\.edu\.ph$/', $email)) {
                $error = "Please use your GSuite account (@g.batstate-u.edu.ph) to log in.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    if ($user['verified'] == 0) {
                        $error = "Please verify your account first via OTP.";
                    } elseif (password_verify($password, $user['password'])) {
                        regenerateSession();
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;

                        if (empty($user['role'])) {
                            header("Location: roleSelector.php");
                        } else {
                            if ($user['role'] === 'tutor') {
                                header("Location: Tutor/tutorDashboard.php");
                            } else {
                                header("Location: Learner/learnerDashboard.php");
                            }
                        }
                        exit;
                    } else {
                        $error = "Incorrect password. Please try again.";
                    }
                } else {
                    $error = "No account found with that email.";
                }
            }
        }
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
    :root { --primary: #10b981; }
    body { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
    .card { background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 253, 244, 0.95) 100%); backdrop-filter: blur(8px); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 16px; box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15); }
    h2 { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800; }
    .form-control { background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%); border: 1px solid rgba(16, 185, 129, 0.2); }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
    .btn-success { background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important; border: none !important; }
    a { color: var(--primary); }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

  <div class="card p-4" style="max-width:400px;width:100%;">
    <h2 class="mb-2 text-center">Welcome Back</h2>
    <p class="text-muted text-center mb-4">Access your <span class="fw-bold" style="color: var(--primary);">LearnTogether</span> account</p>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 text-center" style="margin-bottom:15px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= getCSRFTokenInput() ?>
      <div class="mb-3">
        <input type="email" name="email" class="form-control" placeholder="GSuite Email (@g.batstate-u.edu.ph)" required pattern=".*@g\.batstate-u\.edu\.ph$" title="Please use your GSuite account (@g.batstate-u.edu.ph)" style="border-radius:6px;">
      </div>
      <div class="mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required style="border-radius:6px;">
      </div>
      <button type="submit" class="btn btn-success w-100" style="border-radius:6px;">Log In</button>
    </form>

    <div class="text-center mt-3">
      <a href="google-login.php" class="btn btn-light w-100" style="border:1px solid #ccc;border-radius:6px;">
        <img src="https://developers.google.com/identity/images/g-logo.png" width="20" style="margin-right:8px;">
        Login with Google
      </a>
    </div>

    <div class="mt-3 text-center">
      <a href="forgotPassword.php" class="text-decoration-none">Forgot password?</a><br>
      <span>Don’t have an account? <a href="signup.php">Sign Up</a></span>
    </div>
  </div>

</body>
</html>
