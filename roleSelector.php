<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $user_id]);

    if ($role === 'tutor') {
        $stmt = $pdo->prepare("INSERT INTO tutors (user_id, expertise, bio, profile_image, hourly_rate, availability)
                              VALUES (?, '', '', '', 0, 'Available')");
        $stmt->execute([$user_id]);

        header("Location: Tutor/tutorDashboard.php");
    } elseif ($role === 'learner') {
        $stmt = $pdo->prepare("INSERT INTO learners (user_id, grade_level, interests, profile_image)
                               VALUES (?, '', '', '')");
        $stmt->execute([$user_id]);

        header("Location: Learner/learnerDashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Choose Your Role</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

  <div class="card shadow-lg p-5 text-center" style="max-width: 600px; width: 100%;">
    <h2 class="mb-3">👋 Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h2>
    <p class="text-muted">How would you like to continue?</p>

    <form method="POST">
      <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-4">
        <button type="submit" name="role" value="tutor" class="btn btn-success btn-lg px-4">
          I want to be a Tutor
        </button>
        <button type="submit" name="role" value="learner" class="btn btn-primary btn-lg px-4">
          I need a Tutor
        </button>
      </div>
    </form>

    <p class="mt-4 text-muted">You can always switch roles later from your profile settings.</p>
  </div>

</body>
</html>

