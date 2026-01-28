<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
$stmt->execute([$user_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);
$learner_id = $learner['id'] ?? null;

if (!$learner_id) {
    die("Learner profile not found.");
}

if (isset($_GET['delete']) && $learner_id) {
    $delete_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ? AND learner_id = ?");
    $stmt->execute([$delete_id, $learner_id]);
    header("Location: requests.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        r.id AS reservation_id,
        r.subject,
        r.date AS session_date,
        r.time AS session_time,
        r.status,
        t.id AS tutor_id,
        u.id AS tutor_user_id,
        u.first_name AS tutor_first_name,
        u.last_name AS tutor_last_name
    FROM reservations r
    JOIN tutors t ON r.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE r.learner_id = ?
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$learner_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Requests — LearnTogether</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/request.css">
<link rel="stylesheet" href="../CSS/search.css">
</head>
<body style="overflow-x: hidden;">
    <div class="app">
        <nav class="navbar-top">
            <button class="menu-toggle" id="hamburger">☰</button>
            <div class="navbar-brand-section">
                <div class="navbar-logo">
                    <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
                    <span class="brand-name">LearnTogether</span>
                </div>
            </div>
            <div class="navbar-search">
                <input type="text" id="searchInput" placeholder="Search requests..." class="search-input">
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($user['first_name'] ?? 'Learner') ?></span>
                    <span class="user-role"><?= $user['role'] == 'tutor' ? 'Tutor' : 'Learner' ?></span>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                </div>
            </div>
        </nav>

        <aside id="sidebar">
            <div class="sidebar">
                <div class="profile" id="sidebarProfile" title="View Profile">
                    <div class="avatar">
                        <?= strtoupper($user['first_name'][0]) ?>
                    </div>
                    <div class="profile-text">
                        <div class="profile-name"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                        <div class="profile-status"><?= $user['role'] == 'tutor' ? 'Active Tutor' : 'Active Learner' ?></div>
                    </div>
                    <div class="view-profile-tooltip">View Profile</div>
                </div>
                <nav class="navlinks">
                    <a class="nav-link" href="learnerDashboard.php">
                        <span class="nav-text">Overview</span>
                    </a>
                    <a class="nav-link" href="subjects.php">
                        <span class="nav-text">My Subjects</span>
                    </a>
                    <a class="nav-link" href="searchTutors.php">
                        <span class="nav-text">Find Tutors</span>
                    </a>
                    <a class="nav-link" href="schedule.php">
                        <span class="nav-text">My Schedule</span>
                    </a>
                    <a class="nav-link active" href="requests.php">
                        <span class="nav-text">Requests</span>
                    </a>
                    <a class="nav-link" href="setting.php">
                        <span class="nav-text">Settings</span>
                    </a>
                    <a class="nav-link logout" href="../logout.php">
                        <span class="nav-text">Log Out</span>
                    </a>
                </nav>
            </div>
        </aside>

        <div class="overlay" id="overlay"></div>

        <main class="dashboard-main">
            <h1 class="welcome-title">My Requests to Tutors</h1>

            <?php if (empty($requests)): ?>
                <p style="color:#666;">You haven't sent any session requests yet.</p>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                    <thead>
                        <tr style="background:#f4f4f4;text-align:left;">
                            <th style="padding:10px;">Tutor</th>
                            <th style="padding:10px;">Subject</th>
                            <th style="padding:10px;">Date</th>
                            <th style="padding:10px;">Time</th>
                            <th style="padding:10px;">Status</th>
                            <th style="padding:10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr style="border-bottom:1px solid #eee;">
                                <td style="padding:10px;"><?= htmlspecialchars($req['tutor_first_name'].' '.$req['tutor_last_name']) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($req['subject']) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($req['session_date']) ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($req['session_time']) ?></td>
                                <td style="padding:10px;">
                                    <?php
                                    $statusColor = match($req['status']) {
                                        'Pending' => '#f59e0b',
                                        'Confirmed' => '#10b981',
                                        'Rejected' => '#ef4444',
                                        default => '#6b7280'
                                    };
                                    ?>
                                    <span style="color:<?= $statusColor ?>;font-weight:600;"><?= htmlspecialchars($req['status']) ?></span>
                                </td>
                                <td style="padding:10px;">
                                    <?php if ($req['status'] === 'Confirmed'): ?>
                                        <a href="../agoraconvo.php?user=<?= $req['tutor_user_id'] ?>&reservation_id=<?= $req['reservation_id'] ?>"
                                                style="display:inline-block;padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;text-decoration:none;">
                                            View
                                        </a>
                                    <?php elseif ($req['status'] === 'Rejected'): ?>
                                        <a href="requests.php?delete=<?= $req['reservation_id'] ?>"
                                        onclick="return confirm('Delete this rejected request?');"
                                        style="display:inline-block;padding:6px 10px;background:#ef4444;color:#fff;border-radius:6px;text-decoration:none;">
                                            Delete
                                        </a>
                                    <?php elseif ($req['status'] === 'Scheduled'): ?>
                                        <a href="../agoraconvo.php?user=<?= $req['tutor_user_id'] ?>&reservation_id=<?= $req['reservation_id'] ?>"
                                                style="display:inline-block;padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;text-decoration:none;">
                                            View
                                        </a> 
                                    <?php else: ?>
                                        <span style="color:#999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

    <script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.onkeydown = function(e) {
        if (e.keyCode == 123 || 
            (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() == 'U')) {
            return false;
        }
    };
    </script>
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
