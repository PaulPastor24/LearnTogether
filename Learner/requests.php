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

if (!$user) {
    echo "User not found.";
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        r.id,
        r.subject,
        r.date AS session_date,
        r.time AS session_time,
        r.status,
        u.first_name AS tutor_first_name,
        u.last_name AS tutor_last_name
    FROM reservations r
    JOIN users u ON r.tutor_id = u.id
    WHERE r.learner_id = ?
    ORDER BY r.date DESC, r.time DESC
");

$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Requests — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar">
      <div class="profile-dropdown" id="profileDropdown" style="position:relative;cursor:pointer;">
        <div class="avatar">
          <?= strtoupper($user['first_name'][0]) ?>
        </div>
        <div>
          <div style="font-weight:700">
            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
          </div>
          <div style="font-size:13px;color:var(--muted)">
            Active <?= htmlspecialchars(ucfirst($user['role'])) ?>
          </div>
        </div>

        <div class="dropdown-menu" id="dropdownMenu"
            style="display:none;position:absolute;top:60px;left:0;background:white;
                    border:1px solid #ddd;border-radius:8px;
                    box-shadow:0 4px 10px rgba(0,0,0,0.1);
                    min-width:180px;z-index:999;">
          <a href="profile.php"
            style="display:block;padding:10px 15px;text-decoration:none;
                    color:#333;font-size:14px;">🧑‍💻 View Profile</a>
          <a href="settings.php"
            style="display:block;padding:10px 15px;text-decoration:none;
                    color:#333;font-size:14px;">⚙️ Settings</a>
          <hr style="margin:5px 0;border:none;border-top:1px solid #eee;">
          <a href="../logout.php"
            style="display:block;padding:10px 15px;text-decoration:none;
                    color:#333;font-size:14px;">🚪 Logout</a>
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
    <div class="logo">
      <div class="mark">LT</div>
      <div style="font-weight:700">LearnTogether</div>
    </div>
    <div class="search">
      <input id="searchInput" placeholder="Search tutors, subjects or topics" />
    </div>
    <div class="nav-actions">
      <button class="icon-btn">🔔</button>
      <button class="icon-btn">💬</button>
      <div style="display:flex;align-items:center;gap:8px">
        <div style="text-align:right;margin-right:6px">
          <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
          <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
        </div>
        <div class="avatar" style="width:40px;height:40px;border-radius:10px">
          <?= strtoupper(substr($user['first_name'], 0, 1)) . strtoupper(substr($user['last_name'], 0, 1)) ?>
        </div>
      </div>
    </div>
  </div>

  <main>
    <h1>My Session Requests</h1>

    <?php if (empty($requests)): ?>
      <p style="color:#666;margin-top:20px;">No requests yet.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;margin-top:20px;">
        <thead>
          <tr style="background:#f4f4f4;text-align:left;">
            <th style="padding:10px;">Tutor</th>
            <th style="padding:10px;">Subject</th>
            <th style="padding:10px;">Date</th>
            <th style="padding:10px;">Time</th>
            <th style="padding:10px;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $req): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px;"><?= htmlspecialchars($req['tutor_first_name'] . ' ' . $req['tutor_last_name']) ?></td>
              <td style="padding:10px;"><?= htmlspecialchars($req['subject']) ?></td>
              <td style="padding:10px;"><?= htmlspecialchars($req['session_date']) ?></td>
              <td style="padding:10px;"><?= htmlspecialchars($req['session_time']) ?></td>
              <td style="padding:10px;">
                <?php
                  $statusColor = match ($req['status']) {
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
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
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
