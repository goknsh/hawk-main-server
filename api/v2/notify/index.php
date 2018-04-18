<?php

$params = array(
    'api_user' => 'akaanksh',
    'api_key' => 'Lookoutside@123',
    'to' => $_GET["email"],
    'subject' => 'testing from curl',
    'html' => 'testing body',
    'text' => 'testing body',
    'from' => 'notify@usehawk.ga',
);

$session = curl_init('https://api.sendgrid.com/api/mail.send.json');
curl_setopt ($session, CURLOPT_POST, true);
curl_setopt ($session, CURLOPT_POSTFIELDS, $params);
curl_setopt($session, CURLOPT_HEADER, false);
curl_setopt($session, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($session);
curl_close($session);
print_r($response);

?>