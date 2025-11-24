<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id AS tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tutor_row) {
    header("Location: ../roleSelector.php");
    exit;
}
$tutor_id = $tutor_row['tutor_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tutor_row) {
    header("Location: ../roleSelector.php");
    exit;
}
$tutor_id = $tutor_row['tutor_id'];

$sess_stmt = $pdo->prepare("
    SELECT s.id, s.subject, s.date AS session_date, s.time AS session_time,
           CONCAT(u.first_name,' ',u.last_name) AS student_name
    FROM schedules s
    JOIN learners l ON s.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE s.tutor_id = ?
    ORDER BY s.date ASC, s.time ASC
");
$sess_stmt->execute([$tutor_id]);
$confirmed_sessions = $sess_stmt->fetchAll(PDO::FETCH_ASSOC);

$weekdays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$times = [];
for ($h = 8; $h < 20; $h++) {
    $start = str_pad($h,2,'0',STR_PAD_LEFT) . ':00';
    $end = str_pad($h+1,2,'0',STR_PAD_LEFT) . ':00';
    $times[] = $start . '-' . $end;
}

$grid = [];
for ($i = 0; $i < count($times); $i++) $grid[$i] = array_fill(0, 7, []);

foreach ($confirmed_sessions as $s) {
    $ts = strtotime($s['session_date'] . ' ' . $s['session_time']);
    if ($ts === false) continue;
    $dayIndex = (int)date('N',$ts)-1;
    $hour = (int)date('H',$ts);
    $slotIndex = $hour - 8;
    if ($slotIndex < 0 || $slotIndex >= count($times)) continue;
    $grid[$slotIndex][$dayIndex][] = [
        'subject'=>$s['subject'],
        'student'=>$s['student_name'],
        'time'=>date('H:i',$ts),
        'id'=>$s['id']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Calendar — LearnTogether</title>
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/calendar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside>
      <div class="sidebar" style="width: 230px; height: 400px;">
        <div class="profile">
          <div class="avatar"><?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0]) : 'T' ?></div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
        </div>
        <nav class="navlinks fw-bold" style="margin-top: 12px;">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a class="active" href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="settings.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav" style="height: 85px;">
      <div class="logo">
        <div class="mark" style="margin-left: 20px;">LT</div>
        <div>LearnTogether</div>
      </div>
      <div class="search">
        <input type="text" placeholder="Search students, subjects...">
      </div>
      <div class="nav-actions">
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="profile-info">
            <div><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></div>
            <div>Tutor</div>
          </div>
          <div class="avatar">
            <?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]) : 'T' ?>
          </div>
        </div>
      </div>
    </div>

    <main class="calendar-main">
      <h1 style="margin-bottom: 24px; font-weight: 800; font-size: 40px;">Schedule</h1>

      <div class="calendar-wrapper">
        <div class="calendar-header">
          <div class="calendar-title">Weekly Schedule</div>
          <div class="calendar-actions">
            <a href="scheduleTutor.php" class="btn btn-sm btn-outline-secondary">Manage Pending</a>
          </div>
        </div>

        <table class="calendar-table">
          <thead>
            <tr>
              <th class="time-col">Time</th>
              <?php foreach ($weekdays as $d): ?>
                <th><?= htmlspecialchars($d) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($times as $rowIndex => $slotLabel): ?>
              <tr>
                <td class="time-col"><?= htmlspecialchars($slotLabel) ?></td>
                <?php for ($w = 0; $w < 7; $w++): ?>
                  <td class="slot-cell">
                    <?php if (!empty($grid[$rowIndex][$w])): ?>
                      <?php foreach ($grid[$rowIndex][$w] as $sess): ?>
                        <div class="session-block" title="<?= htmlspecialchars($sess['subject'].' — '.$sess['student'].' @ '.$sess['time']) ?>">
                          <div class="session-subject"><?= htmlspecialchars($sess['subject']) ?></div>
                          <div class="session-student"><?= htmlspecialchars($sess['student']) ?></div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
              <a class="add-schedule-btn" href="scheduleTutor.php">Add Schedule</a>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>