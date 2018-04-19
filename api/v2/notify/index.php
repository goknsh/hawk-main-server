<?php

$hash = $_GET["hash"];

if ($_GET["type"] = "verify") {
    $subject = "Verify your email for Hawk";
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verify your email - Hawk</title></head><body><div class="body"><h1>Hey $name,</h1><p>Lets get your account verified so that you can use Hawk. You can do that by clicking the button below. Also, if you didn’t signup, you can ignore this email or go <a href="https://usehawk.ga/">checkout Hawk</a>.</p><p><a href="https://usehawk.ga/verify/' . $hash . '" class="btn btn-primary">Verify Email</a></p></div></body></html>';
    $text = "Verify your email so we can finish setting up your account.";
}

$params = array(
    'api_user' => 'akaanksh',
    'api_key' => 'Lookoutside@123',
    'to' => $_GET["email"],
    'subject' => $subject,
    'html' => $html,
    'text' => $text,
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