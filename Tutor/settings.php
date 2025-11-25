<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

/* UPDATE ACCOUNT INFO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$first, $last, $email, $user_id]);
    $success_message = "Account information updated successfully!";
}

/* UPDATE PASSWORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $tutor['password'])) {
        $error_message = "❌ Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error_message = "❌ New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error_message = "❌ Password must be at least 6 characters.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$new_hash, $user_id]);
        $success_message = "✅ Password updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings • Tutor Dashboard</title>
<link rel="stylesheet" href="../CSS/setting.css"> <!-- FIX HERE -->
<link rel="stylesheet" href="../CSS/req.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<aside>
    <div class="sidebar" style="width: 230px; height: 400px;">
        <div class="profile">
            <div class="avatar"><?= strtoupper($tutor['first_name'][0] ?? 'T') ?></div>
            <div>
                <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
                <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
            </div>
        </div>
        <nav class="navlinks fw-bold" style="margin-top: 12px;">
            <a href="tutorDashboard.php">🏠 Overview</a>
            <a href="subjects.php">📚 Subjects</a>
            <a href="calendar.php">📅 Schedule</a>
            <a href="requests.php">✉️ Requests</a>
            <a class="active" href="settings.php">⚙️ Settings</a>
            <a href="../logout.php">🚪 Logout</a>
        </nav>
    </div>
</aside>

<main class="container-fluid">
    <h1 class="mb-4 text-center">Settings</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success text-center"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger text-center"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="row no-gap justify-content-center">
        <div class="col-12 col-md-6">
            <div class="card card-custom p-3 narrow-card">
                <div class="card-body">
                    <h2 class="card-title h5 mb-3">Account Information</h2>
                    <form method="POST">
                        <input type="hidden" name="update_account" value="1">
                        <div class="mb-2">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($tutor['first_name']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($tutor['last_name']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tutor['email']) ?>" required>
                        </div>
                        <button type="submit" class="btn-success btn mt-2 w-100">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card card-custom p-3 narrow-card">
                <div class="card-body">
                    <h2 class="card-title h5 mb-3">Password</h2>
                    <form method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div class="mb-2">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn-success btn mt-2 w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
  <script src="../JS/dashboardSearch.js"></script>
</body>
</html>
