<?php
    ignore_user_abort(true);
    set_time_limit(0);
    ob_start();
    header('Content-Type: application/json');
    
    clean();
    
    function clean() {
        try {
            if ($_GET["type"] === "weekly") {
                $GLOBALS['conn'] = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping2", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
                $GLOBALS['conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $GLOBALS["conn"]->prepare("UPDATE `sites` SET `checks-wk`=0, `us-uptime-wk`=100.000, `ie-uptime-wk`=100.000")->execute();
                $response = array(
                    'response' => 'success',
                    'type' => 'weekly'
                );
                echo json_encode($response);
                exit;
            } if ($_GET["type"] === "monthly") {
                $GLOBALS['conn'] = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping2", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
                $GLOBALS['conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $GLOBALS["conn"]->prepare("UPDATE `sites` SET `checks-mn`=0, `us-uptime-mn`=100.000, `ie-uptime-mn`=100.000")->execute();
                $response = array(
                    'response' => 'success',
                    'type' => 'monthly'
                );
                echo json_encode($response);
                exit;
            } else {
                echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Hawk - Redirecting...</title><script>let time = 8;setInterval(function(){time = time - 1;document.querySelector('#seconds').innerHTML = time;if (time === 1) {document.querySelector('#grammar').innerHTML = 'second';}if (time === 0) {document.querySelector('#grammar').innerHTML = 'Should redirect now...';}}, 1000);</script></head><body style='padding:0;margin:0;font-family:monospace;padding:0 2rem;margin:0;display:flex;justify-content:center;align-items:center;height:100vh;max-width:500px;margin:0 auto;text-align:center;'><div><h1 style='margin-top:0;color:red;'>You shouldn&rsquo;t be here</h1><p>So, we&rsquo;re taking you to our main website in <span id='seconds'>8</span> <span id='grammar'>seconds</span>. <b style='color:red;'>Please do not return here.</b> Our servers are very busy and you coming back here will just slow down things for everyone. We <b style='color:red;'>will ban your IP</b> if you keep coming back. Thanks for understanding.</p></div></body></html>";
                header('refresh:7;url=https://usehawk.ga/');
                exit;
            }

        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                clean();
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage()
                );
                echo json_encode($response);
            }
        }
    }

?>