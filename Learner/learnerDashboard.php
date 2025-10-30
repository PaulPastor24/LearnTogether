<?php
session_start();
require '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT 
    COUNT(*) AS total_sessions,
    SUM(status = 'Pending') AS pending_sessions,
    SUM(status = 'Confirmed') AS confirmed_sessions
    FROM reservations WHERE learner_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total = $stats['total_sessions'] ?: 1;
$progress = round(($stats['confirmed_sessions'] / $total) * 100);

$stmt = $pdo->prepare("SELECT subject, date, time, duration, status FROM reservations 
                       WHERE learner_id = ? ORDER BY date ASC, time ASC LIMIT 3");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT subject_name AS name, status FROM subjects WHERE learner_id = ?");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT t.id, u.first_name, u.last_name, t.expertise, t.availability, 
                            t.rating, t.hours_taught
                     FROM tutors t 
                     JOIN users u ON u.id = t.user_id
                     ORDER BY t.rating DESC LIMIT 3");
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>LearnTogether — Learner Dashboard</title>
  <link rel="stylesheet" href="../CSS/learner.css">
</head>
<body>
  <div class="app">
    <aside>
      <div class="sidebar">
        <div class="profile">
          <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
          <div>
            <div style="font-weight:700">
              <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            </div>
            <div style="font-size:13px;color:var(--muted)">Active student</div>
          </div>
        </div>

        <nav class="navlinks">
          <a class="active" href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a href="tutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <main>
      <section class="welcome">
        <div class="greeting">
          <h1>Welcome back, <span style="color:var(--accent-2)"><?= htmlspecialchars($user['first_name']) ?></span> 👋</h1>
          <p>Here’s what’s happening with your learning today.</p>

          <div class="quick">
            <div class="card">
              <div class="icon">📚</div>
              <div><div style="font-weight:700">Find a Tutor</div><small>Search tutors by subject.</small></div>
              <div style="margin-left:auto"><a href="tutors.php" class="cta">Search</a></div>
            </div>
            <div class="card">
              <div class="icon">📅</div>
              <div><div style="font-weight:700">My Schedule</div><small>View upcoming sessions.</small></div>
              <div style="margin-left:auto"><a href="schedule.php" class="cta">Open</a></div>
            </div>
            <div class="card">
              <div class="icon">✉️</div>
              <div><div style="font-weight:700">Requests</div><small>Track pending requests.</small></div>
              <div style="margin-left:auto"><a href="requests.php" class="cta">View</a></div>
            </div>
          </div>
        </div>

        <div style="min-width:240px">
          <div class="card-lg" style="text-align:center">
            <div style="font-size:13px;color:var(--muted)">Progress</div>
            <div style="font-size:28px;font-weight:800;margin-top:8px"><?= $progress ?>%</div>
            <div style="font-size:13px;color:var(--muted);margin-top:8px">toward your goal</div>
            <div class="progress" style="margin-top:14px">
              <div class="pill">
                <div style="font-weight:700"><?= $stats['total_sessions'] ?? 0 ?></div>
                <div style="font-size:12px;color:var(--muted)">Sessions</div>
              </div>
              <div class="pill">
                <div style="font-weight:700"><?= $stats['pending_sessions'] ?? 0 ?></div>
                <div style="font-size:12px;color:var(--muted)">Pending</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="layout">
        <div>
          <div class="card-lg">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
              <div style="font-weight:700">Upcoming Sessions</div>
              <div style="font-size:13px;color:var(--muted)">Next 7 days</div>
            </div>

            <?php if ($sessions): ?>
              <?php foreach ($sessions as $s): ?>
                <div class="session">
                  <div style="width:68px;text-align:center">
                    <div style="font-weight:700"><?= date('M d', strtotime($s['date'])) ?></div>
                    <small><?= date('g:i A', strtotime($s['time'])) ?></small>
                  </div>
                  <div>
                    <div style="font-weight:700"><?= htmlspecialchars($s['subject']) ?></div>
                    <small><?= htmlspecialchars($s['duration']) ?> min</small>
                  </div>
                  <div style="margin-left:auto;color:<?= $s['status'] == 'Confirmed' ? 'var(--accent-2)' : ($s['status'] == 'Pending' ? '#f59e0b' : 'var(--muted)') ?>;font-weight:700">
                    <?= htmlspecialchars($s['status']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="color:var(--muted);font-size:14px;">No upcoming sessions yet.</p>
            <?php endif; ?>
          </div>

          <div class="card-lg" style="margin-top:16px">
            <div style="font-weight:700;margin-bottom:12px">Recommended Tutors</div>
            <div class="tutors-grid">
              <?php foreach ($tutors as $t): ?>
                <div class="tutor">
                  <div class="avatar" style="width:56px;height:56px;border-radius:10px;">
                    <?= strtoupper($t['first_name'][0] . $t['last_name'][0]) ?>
                  </div>
                  <div class="meta">
                    <div style="font-weight:700">
                      <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?> 
                      <span style="font-size:12px;color:var(--muted);font-weight:600">• <?= htmlspecialchars($t['expertise']) ?></span>
                    </div>
                    <div style="font-size:13px;color:var(--muted)">
                      <?= $t['rating'] ?> ★ • <?= $t['hours_taught'] ?> hrs taught • <?= htmlspecialchars($t['availability']) ?>
                    </div>
                  </div>
                  <div><button class="cta">Request</button></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <aside>
          <div class="card-lg">
            <div style="font-weight:700;margin-bottom:8px">Your Subjects</div>
            <?php if ($subjects): ?>
              <?php foreach ($subjects as $sub): ?>
                <div style="padding:10px;border-radius:10px;background:#f7fffb;">
                  <?= htmlspecialchars($sub['name']) ?> • <?= htmlspecialchars($sub['status']) ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="font-size:13px;color:var(--muted)">No subjects added yet.</p>
            <?php endif; ?>
            <div style="margin-top:12px">
              <a href="subjects.php" class="cta">Manage Subjects</a>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>
</body>
</html>
