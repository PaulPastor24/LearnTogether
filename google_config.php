<?php
require 'vendor/autoload.php';

$client = new Google_Client();

// $client->setClientId("630084650794-7dma8caltgmf1a97bc9411qn9g0cs71e.apps.googleusercontent.com");
// $client->setClientSecret("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");

$host = $_SERVER['HTTP_HOST'];
$folder = dirname($_SERVER['PHP_SELF']); 
$redirect_uri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $host . $folder . "/google-callback.php";

$client->setRedirectUri($redirect_uri);


$client->addScope("email");
$client->addScope("profile");
?>
