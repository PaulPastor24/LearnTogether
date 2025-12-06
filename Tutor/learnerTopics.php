<?php
session_start();
require '../db.php';
require '../security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

if (!isset($_GET['learner_id'])) {
    header("Location: tutorDashboard.php");
    exit;
}

$learner_id = $_GET['learner_id'];

$stmt = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor_row) {
    header("Location: tutorDashboard.php");
    exit;
}

$tutor_id = $tutor_row['id'];

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name
    FROM learners l
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$learner_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) {
    header("Location: tutorDashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Security validation failed']);
        exit;
    }
    
    $topic_title = $_POST['topic'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $new_status = $_POST['status'] ?? 'Pending';
    
    if (!in_array($new_status, ['Pending', 'Done'])) {
        $new_status = 'Pending';
    }
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS topic_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tutor_id INT NOT NULL,
            learner_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            topic VARCHAR(255) NOT NULL,
            status ENUM('Pending', 'Done') DEFAULT 'Pending',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_topic (tutor_id, learner_id, subject, topic)
        )");
    } catch (Exception $e) {
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO topic_status (tutor_id, learner_id, subject, topic, status)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");
    $stmt->execute([$tutor_id, $learner_id, $subject, $topic_title, $new_status]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'status' => $new_status]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.id AS reservation_id, r.subject, r.status
    FROM reservations r
    WHERE r.learner_id = ? AND r.tutor_id = ? AND r.status = 'Scheduled'
");
$stmt->execute([$learner_id, $tutor_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allTopics = [];
foreach ($reservations as $res) {
    $subject = $res['subject'];
    $stmt = $pdo->prepare("
        SELECT topics
        FROM tutor_subjects
        WHERE tutor_id = ? AND subject_name = ?
    ");
    $stmt->execute([$tutor_id, $subject]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['topics'])) {
        $topics = array_map('trim', explode(',', $row['topics']));
        foreach ($topics as $t) {
            if ($t !== '') {
                $stmt_status = $pdo->prepare("
                    SELECT status FROM topic_status 
                    WHERE tutor_id = ? AND learner_id = ? AND subject = ? AND topic = ?
                ");
                $stmt_status->execute([$tutor_id, $learner_id, $subject, $t]);
                $status_row = $stmt_status->fetch(PDO::FETCH_ASSOC);
                $status = $status_row ? $status_row['status'] : 'Pending';
                
                $allTopics[] = [
                    'subject' => $subject,
                    'title' => $t,
                    'status' => $status
                ];
            }
        }
    }
}

$totalTopics = count($allTopics);
$doneTopics = 0;
$pendingTopics = 0;
foreach ($allTopics as $topic) {
    if ($topic['status'] === 'Done') $doneTopics++;
    else $pendingTopics++;
}
$progress = $totalTopics > 0 ? round(($doneTopics / $totalTopics) * 100) : 0;
$sessionsCount = count($allTopics);
$pendingCount = $pendingTopics;
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($learner['first_name'].' '.$learner['last_name']) ?></title>
    <link rel="stylesheet" href="../CSS/style2.css">
    <link rel="stylesheet" href="../CSS/req.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar" style="width:230px; height: 400px;">
      <div class="profile">
        <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
        <div>
          <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'].' '.$tutor['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
        </div>
      </div>
      <nav class="navlinks fw-bold" style="margin-top:12px;">
        <a href="tutorDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 Subjects</a>
        <a href="calendar.php">📅 Schedule</a>
        <a href="requests.php">✉️ Requests</a>
        <a href="settings.php">⚙️ Settings</a>
        <a href="../logout.php">🚪 Logout</a>
      </nav>
    </div>
  </aside>
  <div class="nav" style="height:85px;">
    <div class="logo">
      <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px; margin-left:20px;">
      <div>LearnTogether</div>
    </div>
  </div>
  <main class="main-content" style="margin-top: 120px;">
    <div class="main-container bg-white p-4 rounded shadow-sm">
      <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
          <h1 class="fw-bold text-success"><?= htmlspecialchars($reservations[0]['subject'] ?? 'No Subject') ?></h1>
          <h4 class="text-muted"><?= htmlspecialchars($learner['first_name'].' '.$learner['last_name']) ?></h4>
        </div>
        <div class="d-flex gap-3">
          <div class="stats-box text-center">
            <div class="fs-4 fw-bold text-success"><?= $progress ?>%</div>
            <div class="text-muted">Progress</div>
          </div>
          <div class="stats-box text-center">
            <div class="fs-4 fw-bold text-info"><?= $sessionsCount ?></div>
            <div class="text-muted">Sessions</div>
          </div>
          <div class="stats-box text-center">
            <div class="fs-4 fw-bold text-warning"><?= $pendingCount ?></div>
            <div class="text-muted">Pending</div>
          </div>
        </div>
      </div>
      <?php foreach ($reservations as $res): ?>
        <div class="d-flex justify-content-end mb-4 gap-2">
          <a href="../agoraconvo.php?reservation_id=<?= $res['reservation_id'] ?>"
             class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;">💬</a>
          <a href="../meetingPage.php?reservation_id=<?= $res['reservation_id'] ?>"
             class="btn btn-secondary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;">🎥</a>
        </div>
      <?php endforeach; ?>
      <div class="lessons-section">
        <h5 class="fw-bold text-secondary">Lessons</h5>
        <?php if (!empty($allTopics)): ?>
          <?php foreach ($reservations as $res): ?>
            <h5 class="fw-semibold mt-4 mb-3 text-primary"><?= htmlspecialchars($res['subject']) ?></h5>
            <?php 
            $num = 1;
            foreach ($allTopics as $topic):
              if ($topic['subject'] !== $res['subject']) continue;
              $status = $topic['status'];
              $badgeClass = $status === 'Done' ? 'bg-success' : 'bg-warning';
              $topicId = urlencode($topic['title']);
            ?>
              <div class="lesson-card d-flex justify-content-between align-items-center p-3 mb-2 border rounded bg-light text-decoration-none text-dark">
                <div class="flex-grow-1">
                  <div class="fw-bold">Topic <?= $num++ ?></div>
                  <div><?= htmlspecialchars($topic['title']) ?></div>
                </div>
                <div class="status-badge <?= $badgeClass ?> px-3 py-1 rounded text-white fw-bold" 
                     style="cursor: pointer; margin-left: auto;" 
                     onclick="toggleStatus(event, '<?= htmlspecialchars($topic['subject']) ?>', '<?= htmlspecialchars($topic['title']) ?>', this)">
                  <?= htmlspecialchars($status) ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-topics text-muted">No topics available for this learner yet.</p>
        <?php endif; ?>
      </div>
      <a href="tutorDashboard.php" class="btn btn-secondary back-btn mt-4">Back to Dashboard</a>
    </div>
  </main>
</div>

  <script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.onkeydown = function(e) {
        if (e.keyCode == 123 || 
            (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() == 'U')) {
            return false;
        }
    };
  </script>
<script>
function toggleStatus(event, subject, topic, element) {
    event.preventDefault();
    event.stopPropagation();
    
    const currentStatus = element.textContent.trim();
    const newStatus = currentStatus === 'Done' ? 'Pending' : 'Done';
    
    // Show loading state
    element.textContent = 'Updating...';
    element.style.opacity = '0.5';
    
    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&csrf_token=<?= $csrfToken ?>&subject=${encodeURIComponent(subject)}&topic=${encodeURIComponent(topic)}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.textContent = newStatus;
            element.style.opacity = '1';
            
            element.classList.remove('bg-success', 'bg-warning');
            if (newStatus === 'Done') {
                element.classList.add('bg-success');
            } else {
                element.classList.add('bg-warning');
            }
            
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            element.textContent = currentStatus;
            element.style.opacity = '1';
            alert('Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        element.textContent = currentStatus;
        element.style.opacity = '1';
        alert('Error updating status');
    });
}
</script>

</body>
</html>
