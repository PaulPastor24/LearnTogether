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

$sess_stmt = $pdo->prepare("
    SELECT s.id, s.subject, s.day_of_week AS session_day, s.start_time, s.end_time,
           CONCAT(u.first_name,' ',u.last_name) AS student_name
    FROM schedules s
    JOIN reservations r ON s.reservation_id = r.id
    JOIN learners l ON s.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE s.tutor_id = ? AND r.status != 'Cancelled'
    ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), s.start_time ASC
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
    $dayIndex = array_search($s['session_day'], $weekdays);
    if ($dayIndex === false) continue;
    $startHour = (int)explode(':', $s['start_time'])[0];
    $endHour = (int)explode(':', $s['end_time'])[0];
    for ($h = $startHour; $h < $endHour; $h++) {
        $slotIndex = $h - 8;
        if ($slotIndex < 0 || $slotIndex >= count($times)) continue;
        $grid[$slotIndex][$dayIndex][] = [
            'subject'=>$s['subject'],
            'student'=>$s['student_name'],
            'start_time'=>$s['start_time'],
            'end_time'=>$s['end_time'],
            'id'=>$s['id']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Calendar — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/calendar.css">
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
          <a class="nav-link active" href="calendar.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="settings.php">
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
        <input type="text" placeholder="Search schedules..." class="search-input">
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

    <main class="dashboard-main">
      <h1 class="welcome-title">Schedule</h1>
      <div class="calendar-wrapper">
        <div class="calendar-header">
          <div class="calendar-title">Weekly Schedule</div>
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
                        <div class="session-block" title="<?= htmlspecialchars($sess['subject'].' — '.$sess['student'].' @ '.$sess['start_time'].'-'.$sess['end_time']) ?>">
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
