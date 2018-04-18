<?php

ignore_user_abort(true);
set_time_limit(0);
ob_start();
header('Content-Type: application/json');

global $conn; global $validation; global $regions; global $gotRegions; global $time; global $response; $response = array(); $validation = 0; $gotRegions = 0;

$regions = 2;
$time = strtotime(date("Y-m-d h:m:s"));
$lastWeek = strtotime(date("Y-m-d", strtotime("last week")));
$lastMonth = strtotime(date("Y-m-d", strtotime("last month")));

connect();

function connect() {
    try {
        $GLOBALS['conn'] = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping2", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
        $GLOBALS['conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (isset($_GET["new"]) && $_GET["new"] === "true") {
            checkURL($_GET["url"]);
        } else {
            $sitesArray = array_unique($GLOBALS['conn']->query("SELECT site FROM `sites`")->fetchAll(PDO::FETCH_COLUMN));
            foreach ($sitesArray as $url) {
                checkURL($url);
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() === 1203) {
            connect();
        } else {
            $response = array(
                'response' => 'error',
                'more' => $e->getMessage()
            );
            echo json_encode($response);
        }
    }
}

function checkURL($url) {
    checkURLie($url);
    checkURLus($url);
}

function checkURLie($url) {
    $c = curl_init();
    if ((int)date("j") < 15) {
        curl_setopt($c, CURLOPT_URL, "https://ie-s1.herokuapp.com/api/v2/data?url=" . $url);
    } else {
        curl_setopt($c, CURLOPT_URL, "https://ie-s2.herokuapp.com/api/v2/data?url=" . $url);
    }
    curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
    $response = json_decode(curl_exec($c), true);
    curl_close($c);

    if ($response["1"] !== "up") {
        $GLOBALS["validation"] = $GLOBALS["validation"] + 1;
        if ($GLOBALS["validation"] >= 2) {
            $GLOBALS["validation"] = 0;
        }
    } else {
        coSign($response, $url, "ie");
    }
}

function checkURLus($url) {
    $c = curl_init();
    if ((int)date("j") < 15) {
        curl_setopt($c, CURLOPT_URL, "https://us-s1.herokuapp.com/api/v2/data?url=" . $url);
    } else {
        curl_setopt($c, CURLOPT_URL, "https://us-s2.herokuapp.com/api/v2/data?url=" . $url);
    }
    curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, TRUE);
    $response = json_decode(curl_exec($c), true);
    curl_close($c);

    if ($response["1"] !== "up") {
        $GLOBALS["validation"] = $GLOBALS["validation"] + 1;
        if ($GLOBALS["validation"] >= 2) {
            $GLOBALS["validation"] = 0;
        }
    } else {
        coSign($response, $url, "us");
    }
}

function coSign($data, $url, $region) {
    $GLOBALS["validation"] = 0; $GLOBALS["response"]["url"] = $url; $GLOBALS["response"][$region] = array();
    
    $ssl = $data["8"]; $sslexpiry = $data["9"]; if ($ssl === null | $ssl === "") {$ssl = "Not present";$sslexpiry = "Not present";}
    $GLOBALS["response"][$region]["status"] = $data["1"];
    $GLOBALS["response"][$region]["latency"] = round($data["2"], 3);
    $GLOBALS["response"][$region]["code"] = $data["3"];
    $GLOBALS["response"][$region]["speed"] = round($data["4"], 3);
    $GLOBALS["response"][$region]["size"] = $data["5"];
    $GLOBALS["response"][$region]["lookup"] = round($data["6"], 3);
    $GLOBALS["response"][$region]["establish"] = round($data["7"], 3);
    $GLOBALS["response"][$region]["ssl"] = $ssl;
    $GLOBALS["response"][$region]["sslexpiry"] = $sslexpiry;
    $GLOBALS["gotRegions"] = $GLOBALS["gotRegions"] + 1;
    
    if ($GLOBALS["gotRegions"] === $GLOBALS["regions"]) {
        $GLOBALS["gotRegions"] = 0;
        addToDB($GLOBALS["response"]);
    }
}

function addToDB($data) {
    try {
        $url = $data["url"];
        $checks = (int)$GLOBALS["conn"]->query("SELECT `checks` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
        $checksWK = (int)$GLOBALS["conn"]->query("SELECT `checks-wk` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
        $checksMN = (int)$GLOBALS["conn"]->query("SELECT `checks-mn` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
        
        $speedFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-speed` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $speedFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-speed` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $speedUS = (($speedFromDBUS * $checks) + $data["us"]["speed"]) / ($checks + 1);
        $speedIE = (($speedFromDBIE * $checks) + $data["ie"]["speed"]) / ($checks + 1);
        
        $latencyFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-latency` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $latencyFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-latency` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $latencyUS = round((($latencyFromDBUS * $checks) + $data["us"]["latency"]) / ($checks + 1), 3);
        $latencyIE = round((($latencyFromDBIE * $checks) + $data["ie"]["latency"]) / ($checks + 1), 3);
        
        $lookupFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-lookup` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $lookupFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-lookup` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $lookupUS = round((($lookupFromDBUS * $checks) + $data["us"]["lookup"]) / ($checks + 1), 3);
        $lookupIE = round((($lookupFromDBIE * $checks) + $data["ie"]["lookup"]) / ($checks + 1), 3);
        
        $uptimeFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-uptime` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $uptimeFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-uptime` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        if (isset($data["us"]["status"]) && $data["us"]["status"] === "up") {
            $uptimeUS = ((($uptimeFromDBUS/100) * ($checks * 60) + 60) / (($checks + 1) * 60)) * 100;
        } else {
            $uptimeUS = ((($uptimeFromDBUS/100) * ($checks * 60)) / (($checks + 1) * 60)) * 100;
        } if (isset($data["ie"]["status"]) && $data["ie"]["status"] === "up") {
            $uptimeIE = ((($uptimeFromDBIE/100) * ($checks * 60) + 60) / (($checks + 1) * 60)) * 100;
        } else {
            $uptimeIE = ((($uptimeFromDBIE/100) * ($checks * 60)) / (($checks + 1) * 60)) * 100;
        }
        
        $uptimeWKFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-uptime-wk` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $uptimeWKFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-uptime-wk` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        if (isset($data["us"]["status"]) && $data["us"]["status"] === "up") {
            $uptimeWKUS = ((($uptimeWKFromDBUS/100) * ($checksWK * 60) + 60) / (($checksWK + 1) * 60)) * 100;
        } else {
            $uptimeWKUS = ((($uptimeWKFromDBUS/100) * ($checksWK * 60)) / (($checksWK + 1) * 60)) * 100;
        } if (isset($data["ie"]["status"]) && $data["ie"]["status"] === "up") {
            $uptimeWKIE = ((($uptimeWKFromDBIE/100) * ($checksWK * 60) + 60) / (($checksWK + 1) * 60)) * 100;
        } else {
            $uptimeWKIE = ((($uptimeWKFromDBIE/100) * ($checksWK * 60)) / (($checksWK + 1) * 60)) * 100;
        }
        
        $uptimeMNFromDBUS = floatval($GLOBALS["conn"]->query("SELECT `us-uptime-mn` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        $uptimeMNFromDBIE = floatval($GLOBALS["conn"]->query("SELECT `ie-uptime-mn` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"]);
        if ($data["us"]["status"] === "up") {
            $uptimeMNUS = ((($uptimeMNFromDBUS/100) * ($checksMN * 60) + 60) / (($checksMN + 1) * 60)) * 100;
        } else {
            $uptimeMNUS = ((($uptimeMNFromDBUS/100) * ($checksMN * 60)) / (($checksMN + 1) * 60)) * 100;
        } if ($data["ie"]["status"] === "up") {
            $uptimeMNIE = ((($uptimeMNFromDBIE/100) * ($checksMN * 60) + 60) / (($checksMN + 1) * 60)) * 100;
        } else {
            $uptimeMNIE = ((($uptimeMNFromDBIE/100) * ($checksMN * 60)) / (($checksMN + 1) * 60)) * 100;
        }
        
        $checks = $checks + 1; $checksWK = $checksWK + 1; $checksMN = $checksMN + 1;
        $sslUS = $data["us"]["ssl"]; $sslIE = $data["ie"]["ssl"];
        $sslexpiryUS = $data["us"]["sslexpiry"]; $sslexpiryIE = $data["ie"]["sslexpiry"];
        
        $GLOBALS["conn"]->prepare("UPDATE `sites` SET `us-speed`=$speedUS, `ie-speed`=$speedIE, `us-latency`=$latencyUS, `ie-latency`=$latencyIE, `us-lookup`=$lookupUS, `ie-lookup`=$lookupIE, `checks`=$checks, `checks-wk`=$checksWK, `checks-mn`=$checksMN, `us-uptime`=$uptimeUS, `ie-uptime`=$uptimeIE, `us-uptime-wk`=$uptimeWKUS, `ie-uptime-wk`=$uptimeWKIE, `us-uptime-mn`=$uptimeMNUS, `ie-uptime-mn`=$uptimeMNIE, `us-ssl-auth`='$sslUS', `ie-ssl-auth`='$sslIE', `us-ssl-exp`='$sslexpiryUS', `ie-ssl-exp`='$sslexpiryIE' WHERE `site`='$url'")->execute();
        
        if ($data["us"]["status"] === "up") {
            $statusUS = 0;
            $outage = 0;
        } if ($data["us"]["status"] === "down") {
            $statusUS = 1;
            $outage = 1;
        } if ($data["us"]["status"] === "timeout") {
            $statusUS = 2;
            $outage = 1;
        }
        
        if ($data["ie"]["status"] === "up") {
            $statusIE = 0;
        } if ($data["ie"]["status"] === "down") {
            $statusIE = 1;
            $outage = 1;
        } if ($data["ie"]["status"] === "timeout") {
            $statusIE = 2;
            $outage = 1;
        }
        
        $latencyUS = $data["us"]["latency"]; $latencyIE = $data["ie"]["latency"];
        $codeUS = $data["us"]["code"]; $codeIE = $data["ie"]["code"];
        $lookupUS = $data["us"]["lookup"]; $lookupIE = $data["ie"]["lookup"];
        $dataUS = $data["us"]["size"]; $dataIE = $data["ie"]["size"];
        $speedUS = $data["us"]["speed"]; $speedIE = $data["ie"]["speed"];
        
        $GLOBALS["conn"]->prepare("INSERT INTO `$url`(`time`, `outage`, `us-data`, `ie-data`, `us-status`, `ie-status`, `us-latency`, `ie-latency`, `us-code`, `ie-code`, `us-lookup`, `ie-lookup`, `us-speed`, `ie-speed`) VALUES (utc_timestamp(), $outage, $dataUS, $dataIE, $statusUS, $statusIE, $latencyUS, $latencyIE, $codeUS, $codeIE, $lookupUS, $lookupIE, $speedUS, $speedIE)")->execute();
        
        $response = array(
            'response' => 'success',
            'url' => $url
        );
        echo json_encode($response);
    } catch(PDOException $e) {
        if ($e->getCode() === 1203) {
            addToDB($data);
        } else {
            $response = array(
                'response' => 'error',
                'more' => $e->getMessage()
            );
            echo json_encode($response);
        }
    }
}