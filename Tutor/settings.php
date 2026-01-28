<?php
session_start();
require '../db.php';
require '../security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, email, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, description, phone FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutorInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$tutor_id = $tutorInfo['id'] ?? null;
$description = $tutorInfo['description'] ?? '';
$phone = $tutorInfo['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $first = trim($_POST['first_name']);
        $last = trim($_POST['last_name']);
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
        $stmt->execute([$first, $last, $email, $user_id]);
        $success_message = "Account information updated successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tutor_info'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $desc = trim($_POST['description']);
        $phone_num = trim($_POST['phone']);

        $stmt = $pdo->prepare("UPDATE tutors SET description = ?, phone = ? WHERE id = ?");
        $stmt->execute([$desc, $phone_num, $tutor_id]);
        
        $description = $desc;
        $phone = $phone_num;
        $success_message = "Profile information updated successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
    } else {
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);

        if (empty($name) || empty($email) || empty($message)) {
            $error_message = "❌ All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "❌ Invalid email format.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, name, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $name, $email, $message]);
            $success_message = "✅ Feedback submitted successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings • Tutor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/req.css">
    <link rel="stylesheet" href="../CSS/setting.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile" id="sidebarProfile" title="View Profile">
          <div class="avatar"><?= strtoupper($tutor['first_name'][0] ?? 'T') ?></div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div class="profile-status">Active Tutor</div>
          </div>
          <div class="view-profile-tooltip">View Profile</div>
        </div>
        <nav class="navlinks">
          <a class="nav-link" href="tutorDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link" href="calendar.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link active" href="settings.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <nav class="navbar-top">
      <button class="menu-toggle" id="hamburger">☰</button>
      <div class="navbar-brand-section">
        <div class="navbar-logo">
          <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
          <span class="brand-name">LearnTogether</span>
        </div>
      </div>
      <div class="navbar-search">
        <input type="text" placeholder="Search settings..." class="search-input">
      </div>
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></span>
          <span class="user-role">Tutor</span>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)) ?>
        </div>
      </div>
    </nav>

    <main class="lt-main mb-4">
    <h1 class="mb-4" style="font-weight:800">Settings</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success text-center"><?= $success_message ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger text-center"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="row no-gap">
        <div class="col-12">
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

        <div class="col-12">
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

        <div class="col-12">
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

        <div class="col-12">
            <div class="card card-custom p-3 narrow-card">
                <div class="card-body">
                    <h2 class="card-title h5 mb-3">Feedback</h2>
                    <p>Have questions or suggestions? We're here to help.</p>
                    <form method="POST" class="text-start">
                        <input type="hidden" name="submit_feedback" value="1">
                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tutor['email']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success mt-2 w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
  <script src="../JS/dashboardSearch.js"></script>
  <script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const profile = document.getElementById('profileDropdown');
    const dropdown = document.getElementById('dropdownMenu');

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });

    overlay.addEventListener('click', () => {
        hamburger.classList.remove('open');
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    profile.addEventListener('click', () => {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!profile.contains(e.target)) dropdown.style.display = 'none';
    });
  </script>
</body>
</html>
