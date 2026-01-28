<?php
session_start();
require '../db.php';
require '../Agora/agora_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['reservation_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$reservation_id = $_GET['reservation_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, l.user_id AS learner_user_id, t.user_id AS tutor_user_id, t.id AS tutor_id, r.subject,
           u_learner.first_name AS learner_first, u_learner.last_name AS learner_last,
           u_tutor.first_name AS tutor_first, u_tutor.last_name AS tutor_last
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN tutors t ON r.tutor_id = t.id
    JOIN users u_learner ON l.user_id = u_learner.id
    JOIN users u_tutor ON t.user_id = u_tutor.id
    WHERE r.id = ? AND r.status = 'Scheduled'
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || ($reservation['learner_user_id'] != $user_id && $reservation['tutor_user_id'] != $user_id)) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$subject = $reservation['subject'];
$tutor_id = $reservation['tutor_id'];
$learner_id = $reservation['learner_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role = $reservation['learner_user_id'] == $user_id ? 'Learner' : 'Tutor';

$allTopics = [];
$stmt = $pdo->prepare("SELECT topics FROM tutor_subjects WHERE tutor_id = ? AND subject_name = ?");
$stmt->execute([$tutor_id, $subject]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['topics'])) {
    $topics = array_map('trim', explode(',', $row['topics']));
    foreach ($topics as $t) {
        if ($t !== '') {
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
            
            $stmt_status = $pdo->prepare("
                SELECT status FROM topic_status 
                WHERE tutor_id = ? AND learner_id = ? AND subject = ? AND topic = ?
            ");
            $stmt_status->execute([$tutor_id, $learner_id, $subject, $t]);
            $status_row = $stmt_status->fetch(PDO::FETCH_ASSOC);
            $status = $status_row ? $status_row['status'] : 'Pending';
            
            $allTopics[] = [
                'title' => $t,
                'status' => $status
            ];
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
$sessionsCount = $totalTopics;
$pendingCount = $pendingTopics;

$stmt_rating = $pdo->prepare("
    SELECT id FROM tutor_ratings 
    WHERE reservation_id = ? AND learner_id = ?
");
$stmt_rating->execute([$reservation_id, $learner_id]);
$existingRating = $stmt_rating->fetch(PDO::FETCH_ASSOC);
$hasRated = !empty($existingRating);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($subject) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/tutor.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="overflow-x: hidden;">
<div class="app">

  <nav class="navbar-top">
    <button class="menu-toggle" id="hamburger">☰</button>
    <div class="navbar-brand-section">
      <div class="navbar-logo">
        <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
        <span class="brand-name">LearnTogether</span>
      </div>
    </div>
    <div class="navbar-search">
      <input type="text" id="searchInput" placeholder="Search topics..." class="search-input">
    </div>
    <div class="navbar-user">
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($user['first_name'] ?? 'User') ?></span>
        <span class="user-role"><?= $role ?></span>
      </div>
      <div class="user-avatar">
        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
      </div>
    </div>
  </nav>

  <aside id="sidebar">
    <div class="sidebar">
      <div class="profile" id="sidebarProfile" title="View Profile">
        <div class="avatar">
          <?= strtoupper($user['first_name'][0]) ?>
        </div>
        <div class="profile-text">
          <div class="profile-name"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
          <div class="profile-status">Active <?= $role ?></div>
        </div>
        <div class="view-profile-tooltip">View Profile</div>
      </div>

      <nav class="navlinks">
        <?php if ($reservation['learner_user_id'] == $user_id): ?>
          <a class="nav-link" href="learnerDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link active" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link" href="searchTutors.php">
            <span class="nav-text">Find Tutors</span>
          </a>
          <a class="nav-link" href="schedule.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="setting.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        <?php else: ?>
          <a class="nav-link" href="../Tutor/tutorDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link active" href="../Tutor/subjects.php">
            <span class="nav-text">Subjects</span>
          </a>
          <a class="nav-link" href="../Tutor/calendar.php">
            <span class="nav-text">Schedule</span>
          </a>
          <a class="nav-link" href="../Tutor/requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="../Tutor/setting.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="dashboard-main">

    <div class="main-container bg-white p-4 rounded shadow-sm">

      <div class="header-section d-flex justify-content-between align-items-start">

        <div class="header-left">
          <h1 class="welcome-title">Topics for <?= htmlspecialchars($subject) ?></h1>

          <p><?= htmlspecialchars($reservation['learner_first'].' '.$reservation['learner_last']) ?></p>

          <small class="text-muted">
            Tutor: <?= htmlspecialchars($reservation['tutor_first'].' '.$reservation['tutor_last']) ?> |
            Learner: <?= htmlspecialchars($reservation['learner_first'].' '.$reservation['learner_last']) ?>
          </small>
        </div>

        <div class="d-flex gap-3">
          <div class="stats-box">
            <div class="fs-4 fw-bold text-primary"><?= $progress ?>%</div>
            <div class="text-muted">Progress</div>
          </div>
          <div class="stats-box">
            <div class="fs-4 fw-bold text-info"><?= $sessionsCount ?></div>
            <div class="text-muted">Sessions</div>
          </div>
          <div class="stats-box">
            <div class="fs-4 fw-bold text-warning"><?= $pendingCount ?></div>
            <div class="text-muted">Pending</div>
          </div>
        </div>

      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
          <a href="../agoraconvo.php?reservation_id=<?= $reservation_id ?>"
             class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;" 
             title="Chat with Tutor">💬</a>

          <a href="../meetingPage.php?reservation_id=<?= urlencode($reservation_id) ?>"
             class="btn btn-secondary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;" 
             title="Video Call">🎥</a>
      </div>


        <?php if (!empty($allTopics)): ?>
          <?php 
          $num = 1;
          foreach ($allTopics as $topic): 
              $badgeClass = $topic['status']==='Done' ? 'bg-success' : 'bg-warning';
          ?>
            <div class="topic-card">
              <div>
                <div class="fw-bold">Topic <?= $num++ ?></div>
                <div><?= htmlspecialchars($topic['title']) ?></div>
              </div>

              <div class="status-badge <?= $badgeClass ?> text-white rounded px-3 py-1">
                <?= htmlspecialchars($topic['status']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">No topics available for this subject yet.</p>
        <?php endif; ?>

        <a href="<?= $reservation['learner_user_id'] == $user_id ? 'subjects.php' : 'learnerDashboard.php' ?>" 
           class="btn btn-secondary mt-4">Back</a>
    </div>
  </main>
</div>

<div id="ratingModal">
  <div class="rating-modal-content">
    <div class="rating-title">Rate Your Tutor</div>
    
    <div id="starRating">
      <span class="star" data-rating="1">★</span>
      <span class="star" data-rating="2">★</span>
      <span class="star" data-rating="3">★</span>
      <span class="star" data-rating="4">★</span>
      <span class="star" data-rating="5">★</span>
    </div>
    
    <div class="rating-buttons">
      <button type="button" class="btn-secondary" onclick="closeRatingModal()">Skip</button>
      <button type="button" id="submitRatingBtn" onclick="submitRating()" disabled>Submit Rating</button>
    </div>
  </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

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

let currentRating = 0;
let allTopicsDone = false;
let ratingSubmitted = false;
let hasRated = <?= json_encode($hasRated) ?>;

document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        currentRating = this.dataset.rating;
        updateStarDisplay(currentRating);
        document.getElementById('submitRatingBtn').disabled = false;
    });
    
    star.addEventListener('mouseover', function() {
        updateStarDisplay(this.dataset.rating);
    });
});

document.getElementById('starRating').addEventListener('mouseleave', function() {
    updateStarDisplay(currentRating);
});

function updateStarDisplay(rating) {
    document.querySelectorAll('.star').forEach(star => {
        if (star.dataset.rating <= rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function closeRatingModal() {
    const modal = document.getElementById('ratingModal');
    modal.style.display = 'none';
}

function submitRating() {
    if (currentRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const reservationId = new URLSearchParams(window.location.search).get('reservation_id');
    
    fetch('../Tutor/submitRating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `reservation_id=${encodeURIComponent(reservationId)}&rating=${currentRating}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your rating!');
            ratingSubmitted = true;
            closeRatingModal();
        } else {
            alert('Error submitting rating');
        }
    })
    .catch(error => console.error('Error:', error));
}

function checkAllTopicsDone() {
    const topics = document.querySelectorAll('.topic-card');
    let allDone = true;
    
    topics.forEach(topic => {
        const badge = topic.querySelector('.status-badge');
        if (badge && badge.textContent.trim() !== 'Done') {
            allDone = false;
        }
    });
    
    return allDone;
}

function showRatingModal() {
    if (allTopicsDone && !ratingSubmitted && !hasRated) {
        const modal = document.getElementById('ratingModal');
        modal.style.display = 'block';
        modal.classList.add('show');
    }
}

setInterval(async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const reservationId = params.get('reservation_id');
        
        if (!reservationId) return;
        
        const response = await fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) return;
        
        const html = await response.text();
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        const oldTopics = document.querySelectorAll('.topic-card');
        const newTopics = newDoc.querySelectorAll('.topic-card');
        
        oldTopics.forEach((oldCard, index) => {
            if (newTopics[index]) {
                const oldBadge = oldCard.querySelector('.status-badge');
                const newBadge = newTopics[index].querySelector('.status-badge');
                
                if (oldBadge && newBadge && oldBadge.textContent !== newBadge.textContent) {
                    oldBadge.style.transition = 'all 0.3s ease';
                    oldBadge.textContent = newBadge.textContent;
                    oldBadge.className = newBadge.className;
                }
            }
        });
        
        const oldStats = document.querySelectorAll('.stats-box .fs-4');
        const newStats = newDoc.querySelectorAll('.stats-box .fs-4');
        
        oldStats.forEach((oldStat, index) => {
            if (newStats[index] && oldStat.textContent !== newStats[index].textContent) {
                oldStat.style.transition = 'all 0.3s ease';
                oldStat.textContent = newStats[index].textContent;
            }
        });
        
        allTopicsDone = checkAllTopicsDone();
        showRatingModal();
    } catch (error) {
        console.log('Auto-refresh error:', error);
    }
}, 2000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
