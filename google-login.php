<?php
require 'google_config.php';

// Set parameters for OAuth flow
$client->setApprovalPrompt('auto');
$client->setAccessType('offline');

// Create auth URL with hd parameter to restrict to school domain
$authUrl = $client->createAuthUrl();

// Add hd parameter to restrict to the school domain and force account selection
$separator = strpos($authUrl, '?') !== false ? '&' : '?';
$authUrl .= $separator . 'hd=batstate-u.edu.ph';

header("Location: " . $authUrl);
exit;
?>
