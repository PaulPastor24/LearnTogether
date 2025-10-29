<?php
require_once 'HTTP/Request2.php';
$request = new HTTP_Request2();
$request->setUrl('https://api.infobip.com/2fa/2/applications/{appId}/messages');
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setConfig(array(
    'follow_redirects' => TRUE
));
$request->setHeader(array(
    'Authorization' => 'App 347b703a2df2cbb836998bf335d0f9e5-9c19c0ef-f3ab-45a1-ad22-95894bcfad4b',
    'Content-Type' => 'application/json',
    'Accept' => 'application/json'
));
$request->setBody('{"pinType":"NUMERIC","messageText":"Your pin is {{pin}}","pinLength":4,"senderId":"ServiceSMS"}');
try {
    $response = $request->send();
    if ($response->getStatus() == 200) {
        echo $response->getBody();
    }
    else {
        echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
        $response->getReasonPhrase();
    }
}
catch(HTTP_Request2_Exception $e) {
    echo 'Error: ' . $e->getMessage();
}