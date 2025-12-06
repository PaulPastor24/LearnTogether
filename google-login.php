<?php
require 'google_config.php';

$client->setApprovalPrompt('auto');
$client->setAccessType('offline');

$authUrl = $client->createAuthUrl();

$separator = strpos($authUrl, '?') !== false ? '&' : '?';
$authUrl .= $separator . 'hd=batstate-u.edu.ph';

header("Location: " . $authUrl);
exit;
?>
