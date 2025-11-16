<?php
$host = 'sql202.infinityfree.com';
$dbname = 'if0_40430607_learntogetherdb';
$username = 'if0_40430607';
$password = 'AJtE6DQxSI';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
