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

// Get tutor-specific info
$stmt = $pdo->prepare("SELECT id, description, phone FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$tutor_id = $tutorInfo['id'] ?? null;
$description = $tutorInfo['description'] ?? '';
$phone = $tutorInfo['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$first, $last, $email, $user_id]);
    $success_message = "Account information updated successfully!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tutor_info'])) {
    $desc = trim($_POST['description']);
    $phone_num = trim($_POST['phone']);

    $stmt = $pdo->prepare("UPDATE tutors SET description = ?, phone = ? WHERE id = ?");
    $stmt->execute([$desc, $phone_num, $tutor_id]);
    
    $description = $desc;
    $phone = $phone_num;
    $success_message = "Profile information updated successfully!";
}

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
    <link rel="stylesheet" href="../CSS/req.css">
    <link rel="stylesheet" href="../CSS/setting.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<aside>
    <div class="sidebar" style="width: 230px; height: 420px;">
        <div class="profile" id="sidebarProfile" style="cursor: pointer; position: relative; border-radius: 8px; padding: 10px; transition: all 0.3s ease;" title="View Profile">
            <div class="avatar"><?= strtoupper($tutor['first_name'][0] ?? 'T') ?></div>
            <div>
                <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
                <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
            </div>
            <div class="view-profile-tooltip" style="position: absolute; bottom: -35px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; z-index: 10;">👤 View Profile</div>
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

<button class="menu-toggle" style="position: fixed; top: 10px; left: 10px; z-index: 1000;">&#9776;</button>

<main class="lt-main mb-4" style="margin-top: 30px; margin-left: 30px;">
    <h1 class="mb-4 text-center" style="font-weight:800">Settings</h1>

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
                    <h2 class="card-title h5 mb-3">Profile Information</h2>
                    <form method="POST">
                        <input type="hidden" name="update_tutor_info" value="1">
                        <div class="mb-2">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" placeholder="Enter your phone number">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Description / Bio</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Write a brief bio about yourself as a tutor"><?= htmlspecialchars($description) ?></textarea>
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
  <script>
    document.querySelector('.menu-toggle').addEventListener('click', function() {
      document.querySelector('aside').classList.toggle('show');
    });

    // Make profile clickable and show tooltip
    const sidebarProfile = document.getElementById('sidebarProfile');
    const tooltip = document.querySelector('.view-profile-tooltip');

    if (sidebarProfile) {
      sidebarProfile.addEventListener('mouseenter', function() {
        tooltip.style.opacity = '1';
      });

      sidebarProfile.addEventListener('mouseleave', function() {
        tooltip.style.opacity = '0';
      });

      sidebarProfile.addEventListener('click', function() {
        window.location.href = 'viewProfile.php';
      });
    }
  </script>
</body>
</html>
