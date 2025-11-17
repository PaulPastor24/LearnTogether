<?php
// $host = 'sql202.infinityfree.com';
// $dbname = 'if0_40430607_learntogetherdb';
// $username = 'if0_40430607';
// $password = 'AJtE6DQxSI';

$host = 'localhost';
$dbname = 'learntogetherdb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
