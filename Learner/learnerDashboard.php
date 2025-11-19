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

  $topSubjectStmt = $pdo->prepare("
      SELECT subject, COUNT(*) AS count 
      FROM reservations 
      WHERE learner_id = ? 
      GROUP BY subject 
      ORDER BY count DESC 
      LIMIT 1
  ");
  $topSubjectStmt->execute([$user_id]);
  $topSubject = $topSubjectStmt->fetch(PDO::FETCH_ASSOC);

  $recommendedTutors = [];

  if ($topSubject) {
      $subject = $topSubject['subject'];

      $tutorStmt = $pdo->prepare("
          SELECT u.id, u.first_name, u.last_name, t.specialization, t.rating, t.hours_taught, t.availability
          FROM tutors t
          JOIN users u ON t.user_id = u.id
          WHERE t.specialization LIKE ? AND u.role = 'tutor'
          ORDER BY RAND()
          LIMIT 3
      ");
      $tutorStmt->execute(["%$subject%"]);
      $recommendedTutors = $tutorStmt->fetchAll(PDO::FETCH_ASSOC);
  }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Requests — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
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
          <div style="font-size:13px;color:var(--muted)">Active <?= htmlspecialchars(ucfirst($user['role'] === 'tutor' ? 'learner' : $user['role'])) ?></div>
        </div>
      </div>

      <nav class="navlinks">
        <a class="active" href="learnerDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 My Subjects</a>
        <a href="searchTutors.php">🔎 Find Tutors</a>
        <a href="schedule.php">📅 My Schedule</a>
        <a href="requests.php">✉️ Requests</a>
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
          <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
        </div>
      </div>
    </div>
  </div>

  <main>
    <?php if (!empty($recommendedTutors)): ?>
      <section class="card-lg mt-16" style="margin-top:30px;">
        <div class="title mb-2" style="font-weight:700;font-size:18px;">Recommended Tutors</div>
        <div class="tutors-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;">
          <?php foreach ($recommendedTutors as $t): ?>
            <div class="tutor" style="display:flex;align-items:center;background:white;border:1px solid #eee;border-radius:12px;padding:15px;box-shadow:0 3px 8px rgba(0,0,0,0.05);">
              <div class="tutor-avatar" style="width:50px;height:50px;border-radius:50%;background:#4f46e5;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;margin-right:12px;">
                <?= strtoupper($t['first_name'][0] . $t['last_name'][0]) ?>
              </div>
              <div style="flex:1;">
                <div class="tutor-name" style="font-weight:600;font-size:15px;">
                  <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
                  <span class="expertise" style="font-weight:400;font-size:13px;color:#6b7280;">• <?= htmlspecialchars($t['specialization']) ?></span>
                </div>
                <div class="tutor-details" style="font-size:13px;color:#6b7280;margin-top:3px;">
                  <?= $t['rating'] ?? '—' ?> ★ • <?= $t['hours_taught'] ?? '—' ?> hrs • <?= htmlspecialchars($t['availability'] ?? 'N/A') ?>
                </div>
              </div>
              <a href="tutorProfile.php?id=<?= $t['id'] ?>" class="cta" style="padding:6px 12px;background:#4f46e5;color:white;border-radius:6px;text-decoration:none;font-size:13px;margin-left:8px;">
                Request
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <h1 style="margin-top:40px;">My Session Requests</h1>

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
  profile.addEventListener('click', () => {
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  });

  document.addEventListener('click', (e) => {
    if (!profile.contains(e.target)) dropdown.style.display = 'none';
  });
</script>
</body>
</html>
