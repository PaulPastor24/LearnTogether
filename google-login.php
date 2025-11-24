<?php
require 'google_config.php';
header("Location: " . $client->createAuthUrl());
exit;
?>
