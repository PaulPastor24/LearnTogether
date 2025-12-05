<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, email, password, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: /LearnTogether/error.php");
    exit;
}

$sessions = [];
if ($user['role'] === 'learner') {
    $stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $learner = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($learner) {
        $learner_id = $learner['id'];
        $stmt = $pdo->prepare("
            SELECT 
                s.subject,
                s.day_of_week AS session_day,
                s.start_time AS session_time,
                s.end_time,
                s.duration,
                CONCAT(u.first_name,' ',u.last_name) AS tutor_name
            FROM schedules s
            JOIN tutors t ON s.tutor_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE s.learner_id = ?
            ORDER BY FIELD(s.day_of_week, 
                'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
            ), s.start_time
        ");
        $stmt->execute([$learner_id]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $first = trim($_POST['first_name']);
    $last = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$first, $last, $email, $user_id]);
    $success_message = "Account information updated successfully!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $error_message = "❌ Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error_message = "❌ New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error_message = "❌ Password must be at least 6 characters.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $success_message = "✅ Password updated successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings • Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/style2.css">
    <link rel="stylesheet" href="../CSS/setting.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="overflow-x: hidden;">
    <div class="app">
        <aside id="sidebar">
            <div class="sidebar" style="width: 265px;">
                <div class="profile-dropdown">
                    <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                    <div>
                        <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        <div style="font-size:13px;color:var(--muted)"><?= $user['role'] == 'tutor' ? 'Active Tutor' : 'Active Learner' ?></div>
                    </div>
                </div>
                <nav class="navlinks">
                    <a href="learnerDashboard.php">🏠 Overview</a>
                    <a href="subjects.php">📚 My Subjects</a>
                    <a href="searchTutors.php">🔎 Find Tutors</a>
                    <a href="schedule.php">📅 My Schedule</a>
                    <a href="requests.php">✉️ Requests</a>
                    <a class="active" href="setting.php">⚙️ Settings</a>
                    <a href="../logout.php">🚪 Logout</a>
                </nav>
            </div>
        </aside>

        <div class="overlay" id="overlay"></div>

        <div class="nav" role="navigation">
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="logo" style="display:flex; align-items:center;">
                <div>
                    <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px;">
                </div>
                <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
            </div>
            <div class="search">
                <input placeholder="Search tutors, subjects or topics" />
            </div>
            <div class="nav-actions">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="text-align:right;margin-right:6px">
                        <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
                        <div style="font-size:12px;color:var(--muted)"><?= $user['role'] == 'tutor' ? 'Tutor' : 'Learner' ?></div>
                    </div>
                    <div class="avatar" style="width:40px;height:40px;border-radius:10px">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>
        <main class="lt-main mb-4" style="margin-top: 30px; margin-left: 30px; margin-right: 30px;">
            <h1 class="mb-4 text-center" style="font-weight:800;">Settings</h1>

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
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
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
                                <input type="hidden" name="update_profile" value="1">
                                <div class="mb-2">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="4" placeholder="Write something about yourself"></textarea>
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

                <div class="col-12 col-md-6">
                    <div class="card card-custom p-3 narrow-card">
                        <div class="card-body">
                            <h2 class="card-title h5 mb-3">Feedback</h2>
                            <p>Have questions or suggestions? We're here to help.</p>
                            <form method="POST" class="text-start">
                                <input type="hidden" name="submit_feedback" value="1">
                                <div class="mb-2">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
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
    </div>
    <script src="../JS/dashboardSearch.js"></script>
    <script>
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

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
    </script>
</body>
</html>
