<?php
session_start();
require '../db.php';
require '../vendor/autoload.php';
require '../Agora/agora_config.php';
use Agora\RtcTokenBuilder;

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_GET['reservation_id'] ?? null;

if (!$reservation_id) die("Reservation ID missing.");

$stmt = $pdo->prepare("
    SELECT r.id, r.subject, r.date, r.time, r.tutor_id, u.first_name AS tutor_first, u.last_name AS tutor_last
    FROM reservations r
    JOIN users u ON r.tutor_id = u.id
    WHERE r.id = ? AND r.learner_id = ?
");
$stmt->execute([$reservation_id, $user_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reservation) die("Reservation not found.");

$channelName = "session_" . $reservation['id'];
$uid = rand(1000, 9999);
$role = RtcTokenBuilder::RoleAttendee;
$expireTimeInSeconds = 3600;
$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

$token = RtcTokenBuilder::buildTokenWithUid(
    $AGORA_APP_ID,
    $AGORA_APP_CERT,
    $channelName,
    $uid,
    $role,
    $privilegeExpiredTs
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Video Call - <?= htmlspecialchars($reservation['subject']) ?></title>
<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
</head>
<body>
<h2>Session: <?= htmlspecialchars($reservation['subject']) ?> with <?= htmlspecialchars($reservation['tutor_first'] . ' ' . $reservation['tutor_last']) ?></h2>
<div id="local-player" style="width:400px;height:300px;border:2px solid green;"></div>
<div id="remote-player" style="width:400px;height:300px;border:2px solid orange;margin-top:10px;"></div>

<script>
const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
(async () => {
    const uid = <?= $uid ?>;
    await client.join("<?= $AGORA_APP_ID ?>", "<?= $channelName ?>", "<?= $token ?>", uid);
    const [micTrack, camTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
    camTrack.play("local-player");
    await client.publish([micTrack, camTrack]);

    client.on("user-published", async (user, mediaType) => {
        await client.subscribe(user, mediaType);
        if (mediaType === "video") user.videoTrack.play("remote-player");
        if (mediaType === "audio") user.audioTrack.play();
    });
})();
</script>
</body>
</html>
