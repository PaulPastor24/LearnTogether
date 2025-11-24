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
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
$stmt->execute([$user_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) {
    die("Learner profile not found.");
}
$learner_id = $learner['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_tutor'])) {
    $tutor_id = $_POST['tutor_id'] ?? null;
    $subject = $_POST['subject'] ?? null;

    if ($tutor_id && $subject) {
        $insert = $pdo->prepare("
            INSERT INTO reservations (learner_id, tutor_id, subject, status, date, time)
            VALUES (?, ?, ?, 'Pending', CURDATE(), CURTIME())
        ");
        try {
            $insert->execute([$learner_id, $tutor_id, $subject]);
            $success = "Request sent successfully!";
        } catch (PDOException $e) {
            $error = "Failed to send request. Please try again.";
        }
    } else {
        $error = "Failed to send request.";
    }
}

$stmt = $pdo->query("
    SELECT 
        u.id AS user_id,
        u.first_name,
        u.last_name,
        t.id AS tutor_id,
        t.expertise,
        t.bio,
        COALESCE(GROUP_CONCAT(DISTINCT ts.subject_name SEPARATOR ', '), '') AS subjects
    FROM users u
    JOIN tutors t ON u.id = t.user_id
    LEFT JOIN tutor_subjects ts ON t.id = ts.tutor_id
    WHERE u.role = 'tutor'
    GROUP BY u.id, t.id, t.expertise, t.bio
");
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Find Tutors — LearnTogether</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
</head>
<body>
<div class="app">
    <aside>
        <div class="sidebar">
            <div class="profile-dropdown" id="profileDropdown" style="position:relative;cursor:pointer;">
                <div class="avatar"><?= strtoupper($currentUser['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active Learner</div>
                </div>
            </div>

            <nav class="navlinks">
                <a href="learnerDashboard.php">🏠 Overview</a>
                <a href="subjects.php">📚 My Subjects</a>
                <a class="active" href="searchTutors.php">🔎 Find Tutors</a>
                <a href="schedule.php">📅 My Schedule</a>
                <a href="requests.php">✉️ Requests</a>
                <a href="../logout.php">🚪 Logout</a>
            </nav>
        </div>
    </aside>

    <div class="nav" role="navigation">
        <div class="logo" style="display:flex; align-items:center;">
            <div>
                <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px;">
            </div>
            <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
        </div>
        <div class="search">
            <input id="searchInput" placeholder="Search tutors, subjects or topics" />
        </div>
        <div class="nav-actions">
            <div style="display:flex;align-items:center;gap:8px">
                <div style="text-align:right;margin-right:6px">
                    <div style="font-weight:700"><?= htmlspecialchars($currentUser['first_name']) ?></div>
                    <div style="font-size:12px;color:var(--muted)">Learner</div>
                </div>
                <div class="avatar" style="width:40px;height:40px;border-radius:10px">
                    <?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?>
                </div>
            </div>
        </div>
    </div>

    <main>
        <h1>Find Tutors</h1>

        <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <div class="subjects-grid">
            <?php foreach ($tutors as $t): ?>
                <?php
                    $subjects_list = $t['subjects'] ? explode(',', $t['subjects']) : [];
                    $subject_name = trim($subjects_list[0] ?? 'Unknown');
                ?>
                <div class="subject-card">
                    <div class="subject-header">
                        <div class="icon" style="background: linear-gradient(180deg,#2563eb,#1e40af)">👩‍🏫</div>
                        <div class="subject-title"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                    </div>
                    <div class="subject-desc"><?= htmlspecialchars($t['bio'] ?: 'No description available.') ?></div>
                    <div class="topics">
                        <?php if ($subject_name) echo "<span class='topic'>" . htmlspecialchars($subject_name) . "</span>"; ?>
                    </div>

                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="tutor_id" value="<?= $t['tutor_id'] ?>">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars($subject_name) ?>">
                        <button type="submit" name="request_tutor" style="padding:6px 12px;background:#4f46e5;color:white;border:none;border-radius:6px;cursor:pointer;">Request Tutor</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script>
const profile = document.getElementById('profileDropdown');
const dropdown = document.getElementById('dropdownMenu');

profile.addEventListener('click', () => {
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if (!profile.contains(e.target)) dropdown.style.display = 'none';
});
</script>
</body>
</html>
