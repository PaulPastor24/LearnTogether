<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$reservation_id = $_POST['reservation_id'] ?? null;
$rating = $_POST['rating'] ?? null;

if (!$reservation_id || !$rating) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating value']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT learner_id, tutor_id FROM reservations WHERE id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
        exit;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tutor_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT NOT NULL,
            learner_id INT NOT NULL,
            tutor_id INT NOT NULL,
            rating INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rating (learner_id, tutor_id, reservation_id),
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
            INDEX idx_tutor (tutor_id),
            INDEX idx_learner (learner_id)
        )
    ");

    $stmt = $pdo->prepare("
        INSERT INTO tutor_ratings (reservation_id, learner_id, tutor_id, rating)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            updated_at = CURRENT_TIMESTAMP
    ");

    $stmt->execute([
        $reservation_id,
        $reservation['learner_id'],
        $reservation['tutor_id'],
        $rating
    ]);

    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
        FROM tutor_ratings 
        WHERE tutor_id = ?
    ");
    $stmt->execute([$reservation['tutor_id']]);
    $ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        UPDATE tutors 
        SET average_rating = ?, total_ratings = ?
        WHERE id = ?
    ");
    $stmt->execute([
        round($ratingStats['avg_rating'], 2),
        $ratingStats['total_ratings'],
        $reservation['tutor_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Rating submitted successfully',
        'average_rating' => round($ratingStats['avg_rating'], 2),
        'total_ratings' => $ratingStats['total_ratings']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
