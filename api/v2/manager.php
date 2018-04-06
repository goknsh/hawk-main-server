<?php
    ignore_user_abort(true);
    set_time_limit(0);
    error_reporting(0);
    ini_set('display_errors', 1);
    header('Content-Type: application/json');
    
    global $conn; global $validation; global $regions; global $gotRegions; global $time; global $lastWeek; global $lastMonth; $validation = 0; $gotRegions = 0;
    
    $regions = 2;
    $GLOBALS['conn'] = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping2", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
    $GLOBALS['conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $time = strtotime(date("Y-m-d h:m:s"));
    $lastWeek = strtotime(date("Y-m-d", strtotime("last week")));
    $lastMonth = strtotime(date("Y-m-d", strtotime("last month")));

    try {
        if (isset($_GET["new"]) && $_GET["new"] === "true") {

        } else {
            $sitesArray = array_unique($GLOBALS['conn']->query("SELECT site FROM `sites`")->fetchAll(PDO::FETCH_COLUMN));
            foreach ($sitesArray as $url) {
              $data = checkURL($url);
            }
        }
    } catch (PDOException $e) {
        $response = array(
            'response' => 'error',
            'more' => $e->getMessage()
        );
        echo json_encode($response);
    }

    function checkURL($url) {
        checkURLie($url);
        checkURLus($url);
    }

    function checkURLie($url) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, "https://ie-s1.herokuapp.com/api/v2/data?url=" . $url);
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
        curl_setopt($c, CURLOPT_URL, "https://us-s1.herokuapp.com/api/v2/data?url=" . $url);
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

    global $response; $response = array();
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
            print("<pre>");
            print_r($GLOBALS["response"]);
            print("</pre>");
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
            
            $speedFromDBUS = (int)$GLOBALS["conn"]->query("SELECT `us-speed` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $speedFromDBIE = (int)$GLOBALS["conn"]->query("SELECT `ie-speed` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $speedUS = (($speedFromDBUS * $checks) + $data["us"]["speed"]) / ($checks + 1);
            $speedIE = (($speedFromDBIE * $checks) + $data["ie"]["speed"]) / ($checks + 1);
            
            $latencyFromDBUS = (int)$GLOBALS["conn"]->query("SELECT `us-latency` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $latencyFromDBIE = (int)$GLOBALS["conn"]->query("SELECT `ie-latency` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $latencyUS = round((($latencyFromDBUS * $checks) + $data["us"]["latency"]) / ($checks + 1), 3); var_dump($latencyUS);
            $latencyIE = round((($latencyFromDBIE * $checks) + $data["ie"]["latency"]) / ($checks + 1), 3); var_dump($latencyIE);
            
            $lookupFromDBUS = (int)$GLOBALS["conn"]->query("SELECT `us-lookup` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $lookupFromDBIE = (int)$GLOBALS["conn"]->query("SELECT `ie-lookup` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $lookupUS = round((($lookupFromDBUS * $checks) + $data["us"]["lookup"]) / ($checks + 1), 3); var_dump($lookupUS);
            $lookupIE = round((($lookupFromDBIE * $checks) + $data["ie"]["lookup"]) / ($checks + 1), 3); var_dump($lookupIE);
            
            $uptimeWKFromDBUS = (int)$GLOBALS["conn"]->query("SELECT `us-uptime-wk` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $uptimeWKFromDBIE = (int)$GLOBALS["conn"]->query("SELECT `ie-uptime-wk` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            if ($GLOBALS["time"] - $GLOBALS["lastWeek"] < -60) {
                $uptimeWKUS = 100; $uptimeWKIE = 100; $checksWK = 0;
            } else {
                if (isset($data["us"]["status"]) && $data["us"]["status"] === "up") {
                    $uptimeWKUS = ((($uptimeWKFromDBUS/100) * ($checksWK * 60) + 60) / (($checksWK + 1) * 60)) * 100;
                } else {
                    $uptimeWKUS = ((($uptimeWKFromDBUS/100) * ($checksWK * 60)) / (($checksWK + 1) * 60)) * 100;
                } if (isset($data["ie"]["status"]) && $data["ie"]["status"] === "up") {
                    $uptimeWKIE = ((($uptimeWKFromDBIE/100) * ($checksWK * 60) + 60) / (($checksWK + 1) * 60)) * 100;
                } else {
                    $uptimeWKIE = ((($uptimeWKFromDBIE/100) * ($checksWK * 60)) / (($checksWK + 1) * 60)) * 100;
                }
            }
            
            $uptimeMNFromDBUS = $GLOBALS["conn"]->query("SELECT `us-uptime-mn` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            $uptimeMNFromDBIE = $GLOBALS["conn"]->query("SELECT `ie-uptime-mn` FROM sites WHERE site='$url'")->fetchAll(PDO::FETCH_COLUMN)["0"];
            if ($GLOBALS["time"] - $GLOBALS["lastMonth"] < -60) {
                $uptimeMNUS = 100; $uptimeMNIE = 100; $checksMN = 0;
            } else {
                if ($data["us"]["status"] === "up") {
                    $uptimeMNUS = ((($uptimeMNFromDBUS/100) * ($checksMN * 60) + 60) / (($checksMN + 1) * 60)) * 100;
                } else {
                    $uptimeMNUS = ((($uptimeMNFromDBUS/100) * ($checksMN * 60)) / (($checksMN + 1) * 60)) * 100;
                } if ($data["ie"]["status"] === "up") {
                    $uptimeMNIE = ((($uptimeMNFromDBIE/100) * ($checksMN * 60) + 60) / (($checksMN + 1) * 60)) * 100;
                } else {
                    $uptimeMNIE = ((($uptimeMNFromDBIE/100) * ($checksMN * 60)) / (($checksMN + 1) * 60)) * 100;
                }
            }
            
            $checks = $checks + 1; $checksWK = $checksWK + 1; $checksMN = $checksMN + 1;
            
            $GLOBALS["conn"]->prepare("UPDATE `sites` SET `us-speed`=$speedUS, `ie-speed`=$speedIE, `us-latency`=$latencyUS, `ie-latency`=$latencyIE, `us-lookup`=$lookupUS, `ie-lookup`=$lookupIE, `checks`=$checks, `checks-wk`=$checksWK, `checks-mn`=$checksMN, `us-uptime-wk`=$uptimeWKUS, `ie-uptime-wk`=$uptimeWKIE, `us-uptime-mn`=$uptimeMNUS, `ie-uptime-mn`=$uptimeMNIE, `us-ssl-auth`='$sslUS', `ie-ssl-auth`='$sslIE', `us-ssl-exp`='$sslexpiryUS', `ie-ssl-exp`='$sslexpiryIE' WHERE `site`='$url'")->execute();
            
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
            $sslUS = $data["us"]["ssl"]; $sslIE = $data["ie"]["ssl"];
            $sslexpiryUS = $data["us"]["sslexpiry"]; $sslexpiryIE = $data["ie"]["sslexpiry"];
            
            $GLOBALS["conn"]->prepare("INSERT INTO `$url`(`outage`, `us-data`, `ie-data`, `us-status`, `ie-status`, `us-latency`, `ie-latency`, `us-code`, `ie-code`, `us-lookup`, `ie-lookup`) VALUES ($outage, $dataUS, $dataIE, $statusUS, $statusIE, $latencyUS, $latencyIE, $codeUS, $codeIE, $lookupUS, $lookupIE)")->execute();
            
            $response = array(
                'response' => 'success'
            );
            echo json_encode($response);
        } catch(PDOException $e) {
            $response = array(
                'response' => 'error',
                'more' => $e->getMessage(),
                'line' => $e->getLine()
            );
            echo json_encode($response);
        }
    }
    
function nettuts_error_handler($number, $message, $file, $line, $vars) {
    $email = "<p>An error ($number) occurred on line 
        <strong>$line</strong> and in the <strong>file: $file.</strong> 
        <p> $message </p>";
    $email .= "<pre>" . print_r($vars, 1) . "</pre>";
    $headers = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    error_log($email, 1, 'akaankshraj@gmail.com', $headers);
    if ( ($number !== E_NOTICE) && ($number < 2048) ) {
        die("There was an error. Please try again later.");
    }
}
set_error_handler('nettuts_error_handler');