<?php
session_start();
require 'db.php';
require 'google_config.php';

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);

$oauth = new Google_Service_Oauth2($client);
$googleUser = $oauth->userinfo->get();

$email = $googleUser->email;
$first_name = $googleUser->givenName;
$last_name = $googleUser->familyName;

$allowedDomain = "g.batstate-u.edu.ph";
$domain = substr(strrchr($email, "@"), 1);

if ($domain !== 'g.batstate-u.edu.ph') {
    echo "
        <div style='text-align:center;margin-top:50px;font-family:Arial,sans-serif;'>
            <h3 style='color:red;'>You must use your school Google account (@g.batstate-u.edu.ph)</h3>
            <a href='login.php' style='display:inline-block;margin-top:20px;padding:10px 20px;
                background-color:#28a745;color:white;text-decoration:none;border-radius:6px;'>
                Back to Login
            </a>
        </div>
    ";
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $insert = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, role, verified, password)
        VALUES (?, ?, ?, '', 1, '')
    ");
    $insert->execute([$first_name, $last_name, $email]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['logged_in'] = true;

if (empty($user['role'])) {
    header("Location: roleSelector.php");
    exit;
}

if ($user['role'] === 'tutor') {
    header("Location: Tutor/tutorDashboard.php");
} else {
    header("Location: Learner/learnerDashboard.php");
}
exit;
?>
