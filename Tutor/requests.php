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

if (!$tutor) {
    header("Location: ../roleSelector.php");
    exit;
}

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

$tutor_first = htmlspecialchars($tutor['first_name']);
$tutor_last = htmlspecialchars($tutor['last_name']);
$avatar_initial = strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Tutor Requests — LearnTogether</title>
<link rel="stylesheet" href="../CSS/req.css">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>

    <aside>
      <div class="sidebar" style="width: 230px; height: 345px;">
        <div class="profile">
          <div class="avatar">
            <?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0]) : 'T' ?>
          </div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
        </div>
        <nav class="navlinks fw-bold" style="margin-top: 12px;">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="calendar.php">📅 Schedule</a>
          <a class="active" href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav" style="height: 85px; width: calc(100% - 260px);">
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

  <main class="lt-main mb-4" style="margin-left: 115px; margin-top: -68px;">
    <div class="content-wrap">
      <h1 class="page-title" style="font-weight: 500px">Session Requests</h1>

      <div class="card content-card">
        <div class="card-body p-0">
          <?php if (empty($requests)): ?>
            <div class="p-4 text-muted">No session requests yet.</div>
          <?php else: ?>
            <div class="table-responsive p-3">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Learner</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($requests as $req): ?>
                    <tr>
                      <td><?= htmlspecialchars($req['learner_first_name'].' '.$req['learner_last_name']) ?></td>
                      <td><?= htmlspecialchars($req['subject']) ?></td>
                      <td><?= htmlspecialchars($req['session_date']) ?></td>
                      <td><?= htmlspecialchars($req['session_time']) ?></td>
                      <td>
                        <?php
                        $color = $req['status'] === 'Pending' ? '#f59e0b' :
                                 ($req['status'] === 'Confirmed' ? '#10b981' :
                                 ($req['status'] === 'Rejected' ? '#ef4444' : '#6b7280'));
                        ?>
                        <span class="fw-semibold" style="color:<?= $color ?>;">
                          <?= htmlspecialchars($req['status']) ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($req['status'] === 'Pending'): ?>
                          <a href="requests.php?approve=<?= $req['reservation_id'] ?>" class="btn btn-success btn-sm">Approve</a>
                          <a href="requests.php?reject=<?= $req['reservation_id'] ?>" class="btn btn-danger btn-sm ms-1">Reject</a>
                        <?php elseif ($req['status'] === 'Confirmed'): ?>
                          <button onclick="window.open('../agoraconvo.php?reservation_id=<?= $req['reservation_id'] ?>', '_blank')" class="btn btn-primary btn-sm">View</button>
                        <?php else: ?>
                          <span class="text-muted">N/A</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>