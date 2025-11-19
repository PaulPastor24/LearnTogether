<?php
session_start();
require '../db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: /LearnTogether/login.php");
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $topics = trim($_POST['topics'] ?? '');

    if ($_POST['action'] === 'add' && $subject_name) {
        $stmt = $pdo->prepare("INSERT INTO tutor_subjects (tutor_id, subject_name, description, topics) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tutor_id, $subject_name, $description, $topics]);
        header("Location: subjects.php");
        exit;
    }

    if ($_POST['action'] === 'edit' && $subject_name) {
        $subject_id = $_POST['subject_id'];
        $stmt = $pdo->prepare("UPDATE tutor_subjects SET subject_name = ?, description = ?, topics = ? WHERE id = ? AND tutor_id = ?");
        $stmt->execute([$subject_name, $description, $topics, $subject_id, $tutor_id]);
        header("Location: subjects.php");
        exit;
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM tutor_subjects WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$delete_id, $tutor_id]);
    header("Location: subjects.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tutor_subjects WHERE tutor_id = ? ORDER BY id DESC");
$stmt->execute([$tutor_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor Subjects - LearnTogether</title>
  <link rel="stylesheet" href="../CSS/style2.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar" style="width: 255px; height: 345px;">
      <div class="profile">
        <div class="avatar">
          <?= isset($tutor['first_name'], $tutor['last_name']) 
              ? strtoupper($tutor['first_name'][0]) 
              : 'T' ?>
        </div>
        <div>
          <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
        </div>
      </div>
      <nav class="navlinks fw-bold" style="margin-top: 12px;">
        <a href="tutorDashboard.php">🏠 Overview</a>
        <a class="active" href="subjects.php">📚 Subjects</a>
        <a href="scheduleTutor.php">📅 Schedule</a>
        <a href="requests.php">✉️ Requests</a>
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
        <input type="text" placeholder="Search students, subjects..." />
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

  <main class="p-3" style="margin-left: 260px; margin-top: 100px;">
    <h1>Manage Your Subjects</h1>

    <div class="mb-4">
        <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addSubjectForm" aria-expanded="false" aria-controls="addSubjectForm">
            + Add New Subject
        </button>
    </div>

    <div class="collapse" id="addSubjectForm">
        <div class="card mb-4 shadow-sm" style="max-width:600px;">
            <div class="card-header" style="font-weight:700; font-size:1.2rem;">
                Add New Subject
            </div>
            <div class="card-body">
                <form method="POST" style="display:flex;flex-direction:column;gap:15px;">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" id="subject_name" class="form-control" placeholder="Enter subject name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Short Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief description of your expertise"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="topics" class="form-label">Topics (comma separated)</label>
                        <input type="text" name="topics" id="topics" class="form-control" placeholder="e.g. Algebra, Calculus, Physics">
                    </div>
                    <button type="submit" class="btn btn-success">Add Subject</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-2">
      <?php if (count($subjects) > 0): ?>
        <?php foreach ($subjects as $sub): ?>
          <div class="col">
            <div class="card card-subject h-100 shadow-sm">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($sub['subject_name']) ?></h5>
                <p class="card-text"><em><?= htmlspecialchars($sub['description']) ?></em></p>
                <p class="card-text"><small>Topics: <?= htmlspecialchars($sub['topics']) ?></small></p>
              </div>
              <div class="card-footer d-flex justify-content-between">
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $sub['id'] ?>">Edit</button>
                <a href="?delete_id=<?= $sub['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
              </div>
            </div>
          </div>

          <div class="modal fade" id="editModal<?= $sub['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $sub['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= $sub['id'] ?>">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body" style="display:flex;flex-direction:column;gap:10px;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                    <input type="text" name="subject_name" value="<?= htmlspecialchars($sub['subject_name']) ?>" required style="padding:8px;">
                    <textarea name="description" rows="3" style="padding:8px;"><?= htmlspecialchars($sub['description']) ?></textarea>
                    <input type="text" name="topics" value="<?= htmlspecialchars($sub['topics']) ?>" style="padding:8px;">
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
      <?php else: ?>
        <p>No subjects added yet.</p>
      <?php endif; ?>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
