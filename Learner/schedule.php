<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
$stmt->execute([$user_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) {
    die("⚠️ Learner profile not found.");
}

$learner_id = $learner['id'];

$stmt = $pdo->prepare("
    SELECT 
        s.subject,
        s.day_of_week AS session_day,
        s.start_time AS session_time,
        s.duration,
        CONCAT(u.first_name,' ',u.last_name) AS tutor_name
    FROM schedules s
    JOIN tutors t ON s.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    JOIN reservations r ON s.reservation_id = r.id
    WHERE s.learner_id = ? 
      AND r.status = 'Scheduled'
    ORDER BY FIELD(s.day_of_week, 
        'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
    ), s.start_time
");
$stmt->execute([$learner_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Schedule — LearnTogether</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/schedule2.css">
</head>
<body>
<div class="app">
    <aside>
        <div class="sidebar">
            <div class="profile-dropdown" id="profileDropdown" style="position:relative;cursor:pointer;">
                <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active Learner</div>
                </div>
            </div>
            <nav class="navlinks">
                <a href="learnerDashboard.php">🏠 Overview</a>
                <a href="subjects.php">📚 My Subjects</a>
                <a href="searchTutors.php">🔎 Find Tutors</a>
                <a class="active" href="schedule.php">📅 My Schedule</a>
                <a href="requests.php">✉️ Requests</a>
                <a href="../logout.php">🚪 Logout</a>
            </nav>
        </div>
    </aside>

    <div class="nav" role="navigation">
        <div class="logo" style="display:flex; align-items:center;">
            <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px;">
            <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
        </div>
        <div class="search">
            <input placeholder="Search tutors, subjects or topics" />
        </div>
        <div class="nav-actions">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="text-align:right;margin-right:6px">
                    <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
                    <div style="font-size:12px;color:var(--muted)">Learner</div>
                </div>
                <div class="avatar" style="width:40px;height:40px;border-radius:10px">
                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                </div>
            </div>
        </div>
    </div>

<main>
    <h1>My Schedule</h1>

    <div class="schedule-container">
        <?php if (count($sessions) > 0): ?>
            <?php foreach ($sessions as $s): 
                $start = date("H:i", strtotime($s['session_time']));
                $end = date("H:i", strtotime($s['session_time'] . " +{$s['duration']} minutes"));
            ?>
                <div class="schedule-item">
                    <div class="schedule-day">
                        <strong><?= htmlspecialchars($s['session_day']) ?></strong>
                        <span><?= $start ?>–<?= $end ?></span>
                    </div>

                    <div class="schedule-info">
                        <div class="schedule-title">
                            <?= htmlspecialchars($s['subject']) ?> — <?= htmlspecialchars($s['tutor_name']) ?>
                        </div>
                        <div class="schedule-meta">
                            Online • <?= $s['duration'] ?> min
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p style="color:#666;">You have no scheduled sessions yet.</p>
        <?php endif; ?>
    </div>
</main>
</div>
</body>
</html>
