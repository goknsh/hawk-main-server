<?php

if ($_GET["type"] = "verify") {
    $subject = "Verify your email for Hawk";
    $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Verify your email &mdash; Hawk</title></head><body style="padding:0;margin:0;font-family:Colfax,Segoe UI"><header style="display:flex;padding:1rem;background:#004a70;color:white"><div style="width:50%" class="left">Hawk</div><div style="width:50%;display:flex;justify-content:flex-end" class="right"><a style="color:#fafafa;text-decoration:none;opacity:.65" href="https://usehawk.ga/verify/' . $_GET["hash"] . '">Verify Email</a></div></header><div class="body" style="height:90vh;display:flex;align-items:center;justify-content:center;max-width:600px;padding:0 2rem;flex-flow:column wrap;margin:0 auto;text-align:left;min-height:450px"><h1 style="margin:0;width:100%">Hey ' . $_GET["name"] . ',</h1><p>Lets get your account verified so that you can use Hawk. You can do that by clicking the button below. Also, if you didn’t signup, you can ignore this email or <a style="color:#004a70;text-decoration:underline" href="https://usehawk.ga/">go checkout Hawk</a>.</p><div style="width:100%;margin:1rem 0 0 0"><a href="https://usehawk.ga/verify/' . $_GET["hash"] . '" style="background:#004a70;padding:.5rem 1.5rem;color:white;border-radius:5px;">Verify Email</a></div></div></body></html>';
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