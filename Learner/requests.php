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
</head>
<body>
<div class="app">
    <aside>
        <div class="sidebar">
            <div class="profile-dropdown">
                <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active Learner</div>
                </div>
            </div>
            <nav class="navlinks">
                <a href="learnerDashboard.php">🏠 Overview</a>
                <a href="subjects.php">📚 My Subjects</a>
                <a href="searchTutors.php">🔎 Find Tutors</a>
                <a href="schedule.php">📅 My Schedule</a>
                <a class="active" href="requests.php">✉️ Requests</a>
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
        <h1>My Requests to Tutors</h1>

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
                                    <button onclick="window.open('../agoraconvo.php?user=<?= $req['tutor_user_id'] ?>&reservation_id=<?= $req['reservation_id'] ?>', '_blank')"
                                            style="padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;">
                                        View
                                    </button>
                                <?php elseif ($req['status'] === 'Rejected'): ?>
                                    <a href="requests.php?delete=<?= $req['reservation_id'] ?>"
                                       onclick="return confirm('Delete this rejected request?');"
                                       style="display:inline-block;padding:6px 10px;background:#ef4444;color:#fff;border-radius:6px;text-decoration:none;">
                                        Delete
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
</body>
</html>
