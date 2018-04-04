<?php
    ignore_user_abort(true);
    set_time_limit(0);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    global $conn; global $validation; $validation = 0;
    // $conn = new PDO("mysql:host=11.70.0.18:3306;dbname=suseping_ping", "cpses_suseping_server@192.168.0.2", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
    
    $conn = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        if (isset($_GET["new"]) && $_GET["new"] === "true") {

        } else {
            $sitesArray = array_unique($GLOBALS['conn']->query("SELECT site FROM `sites`")->fetchAll(PDO::FETCH_COLUMN));
            foreach ($sitesArray as $url) {
              $data = checkURL($url);
            }
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
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
        $GLOBALS["response"][$region]["latency"] = $data["2"];
        $GLOBALS["response"][$region]["code"] = $data["3"];
        $GLOBALS["response"][$region]["speed"] = $data["4"];
        $GLOBALS["response"][$region]["size"] = $data["5"];
        $GLOBALS["response"][$region]["lookup"] = $data["6"];
        $GLOBALS["response"][$region]["establish"] = $data["7"];
        $GLOBALS["response"][$region]["ssl"] = $ssl;
        $GLOBALS["response"][$region]["sslexpiry"] = $sslexpiry;
            
        if ($region === "us") {
            print("<pre>");
            print_r($GLOBALS["response"]);
            print("</pre>");
            addToDB($GLOBALS["response"]);
        }
    }
    
    function addToDB($data) {
        
    }