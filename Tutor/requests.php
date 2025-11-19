<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'Confirmed' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: requests.php");
    exit;
}

if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'Rejected' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: requests.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT t.id AS tutor_id, u.first_name, u.last_name
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tutor) die("Tutor profile not found.");
$tutor_id = $tutor['tutor_id'];

$stmt = $pdo->prepare("
    SELECT 
        r.id AS reservation_id,
        r.subject,
        r.date AS session_date,
        r.time AS session_time,
        r.status,
        l.id AS learner_id,
        u.first_name AS learner_first_name,
        u.last_name AS learner_last_name
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE r.tutor_id = ?
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$tutor_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor Requests — LearnTogether</title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>
<div class="app">
    <aside>
      <div class="sidebar">
        <div class="profile">
          <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
          <div>
            <div style="font-weight:700"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
        </div>
        <nav class="navlinks">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="scheduleTutor.php">📅 Schedule</a>
          <a class="active" href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav">
        <div class="logo">
            <div class="mark">LT</div>
            <div style="font-weight:700">LearnTogether</div>
        </div>
    </div>

    <main>
        <h1>Session Requests</h1>

        <?php if (empty($requests)): ?>
            <p style="color:#666;">No session requests yet.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                <thead>
                    <tr style="background:#f4f4f4;text-align:left;">
                        <th style="padding:10px;">Learner</th>
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
                            <td style="padding:10px;"><?= htmlspecialchars($req['learner_first_name'].' '.$req['learner_last_name']) ?></td>
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
                                <span style="color:<?= $statusColor ?>;font-weight:600;">
                                    <?= htmlspecialchars($req['status']) ?>
                                </span>
                            </td>

                            <td style="padding:10px;">
                                <?php if ($req['status'] === 'Pending'): ?>

                                    <a href="requests.php?approve=<?= $req['reservation_id'] ?>" 
                                       style="padding:5px 10px;background:#10b981;color:white;border-radius:5px;text-decoration:none;">
                                       Approve
                                    </a>

                                    <a href="requests.php?reject=<?= $req['reservation_id'] ?>" 
                                       style="padding:5px 10px;background:#ef4444;color:white;border-radius:5px;text-decoration:none;margin-left:5px;">
                                       Reject
                                    </a>

                                <?php elseif ($req['status'] === 'Confirmed'): ?>
                                    <button onclick="window.open('../agoraconvo.php?reservation_id=<?= $req['reservation_id'] ?>', '_blank')"
                                            style="padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;">
                                        View
                                    </button>

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
