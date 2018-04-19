<?php
    // error_reporting(0);
    ini_set('memory_limit', '100G'); ini_set('xdebug.max_nesting_level', 256);
    ob_start();
    
    if (!isset($_GET["email"]) | $_GET["email"] === "" && !isset($_GET["pass"]) | $_GET["pass"] === "") {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Hawk - Redirecting...</title><script>let time = 8;setInterval(function(){time = time - 1;document.querySelector('#seconds').innerHTML = time;if (time === 1) {document.querySelector('#grammar').innerHTML = 'second';}if (time === 0) {document.querySelector('#grammar').innerHTML = 'Should redirect now...';}}, 1000);</script></head><body style='padding:0;margin:0;font-family:monospace;padding:0 2rem;margin:0;display:flex;justify-content:center;align-items:center;height:100vh;max-width:500px;margin:0 auto;text-align:center;'><div><h1 style='margin-top:0;color:red;'>You shouldn&rsquo;t be here</h1><p>So, we&rsquo;re taking you to our main website in <span id='seconds'>8</span> <span id='grammar'>seconds</span>. <b style='color:red;'>Please do not return here.</b> Our servers are very busy and you coming back here will just slow down things for everyone. We <b style='color:red;'>will ban your IP</b> if you keep coming back. Thanks for understanding.</p></div></body></html>";
        header('refresh:7;url=https://usehawk.ga/');
        exit;
    } else {
        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: *");
	    connect();
    }
	
	function connect() {
    	try {
            global $conn;
			$GLOBALS['conn'] = new PDO("mysql:host=ricky.heliohost.org:3306;dbname=goark_ping2", "goark_server", "serverkey2", array(PDO::ATTR_PERSISTENT => true));
            
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if (isset($_GET["signup"]) && $_GET["signup"] === "true") {
                signup();
            } if(isset($_GET["delete"]) && $_GET["delete"] === "true") {
                deleteSite();
            } if (isset($_GET["add"]) && $_GET["add"] === "true") {
                addSite();
            } if (isset($_GET["login"]) && $_GET["login"] === "true") {
                login();
            } if (isset($_GET["site"]) && $_GET["site"] === "get") {
                getSite();
            } if (isset($_GET["site"]) && $_GET["site"] === "more") {
                getMoreStats();
            } if (isset($_GET["change"]) && $_GET["change"] === "latency") {
                changeLatency();
            } if (isset($_GET["change"]) && $_GET["change"] === "email") {
                changeEmail();
            } if (isset($_GET["change"]) && $_GET["change"] === "pass") {
                changePassword();
            } if (isset($_GET["change"]) && $_GET["change"] === "website") {
                changeWebsiteName();
            } if (isset($_GET["verify"]) && $_GET["verify"] === "true") {
                verifyEmail();
            } if (isset($_GET["cleanup"]) && $_GET["cleanup"] === "weekly") {
                weeklyCleanup();
            } if (isset($_GET["cleanup"]) && $_GET["cleanup"] === "monthly") {
                monthlyCleanup();
            } else {
                $response = array(
                    'response' => 'up'
                );
            }
    	} catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                connect();
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
    	}
	}
	
	function weeklyCleanup() {
        try {
            $GLOBALS["conn"]->prepare("UPDATE `sites` SET `checks-wk`=0, `us-uptime-wk`=100.000, `ie-uptime-wk`=100.000")->execute();
            $response = array(
                'response' => 'success',
                'type' => 'weekly'
            );
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                weeklyCleanup();
                exit;
            }
        }
	}
	
	function monthlyCleanup() {
        try {
            $GLOBALS["conn"]->prepare("UPDATE `sites` SET `checks-mn`=0, `us-uptime-mn`=100.000, `ie-uptime-mn`=100.000")->execute();
            $response = array(
                'response' => 'success',
                'type' => 'monthly'
            );
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                monthlyCleanup();
                exit;
            }
        }
	}
	
	function verifyEmail() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $hash = $_GET['hash'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE pass <> 'DATA'")->fetchColumn();
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    if ($hash === $GLOBALS['conn']->query("SELECT sites FROM `$email` WHERE pass <> 'DATA'")->fetchColumn()) {
                        $GLOBALS['conn']->prepare("UPDATE `$email` SET `sites`='DATA'")->execute();
                        $response = array(
                            'response' => 'success',
                            'email' => strtolower($email),
                            'name' => $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE pass <> 'DATA'")->fetchColumn()
                        );
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = array(
                            'response' => 'nomatch'
                        );
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                changeEmail();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function changeEmail() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $to = $_GET['to'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $GLOBALS['conn']->prepare("RENAME TABLE `$email` TO `$to`;")->execute();
                    $GLOBALS['conn']->prepare("UPDATE `sites` SET `email`='$to' where `email`='$email'")->execute();
                    $response = array(
                        'response' => 'success'
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                changeEmail();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function changePassword() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $newpass = password_hash($_GET['newpass'], PASSWORD_BCRYPT, ['salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)]);
                    $GLOBALS['conn']->prepare("UPDATE `$email` SET `pass`='$newpass' where `sites`='DATA'")->execute();
                    $response = array(
                        'response' => 'success'
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                changePassword();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function changeWebsiteName() {
	    $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $url = $_GET['url'];
        $to = $_GET['to'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $GLOBALS['conn']->prepare("UPDATE `sites` SET `name`='$to' WHERE `site`='$url' and `email`='$email'")->execute();
                    $response = array(
                        'response' => 'success',
                        'name' => $to
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                changeWebsiteName();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function changeLatency() {
	    $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $url = $_GET['url'];
        $to = $_GET['to'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $GLOBALS['conn']->prepare("UPDATE `sites` SET `thresh`=$to WHERE `site`='$url' and `email`='$email'")->execute();
                    $response = array(
                        'response' => 'success',
                        'thresh' => $to
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                changeLatency();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function deleteSite() {
	    $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $id = (int)$_GET["id"];
        $url = $_GET["url"];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $GLOBALS['conn']->exec("delete from `$email` where sites='$url'");
                    $GLOBALS['conn']->exec("delete from `sites` where id=$id");
                    
                    $response = array(
                        'response' => 'success'
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                deleteSite();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function addSite() {
	    $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        $url = $_GET["url"];
        $thresh = $_GET["timeout"];
        $name = htmlentities($_GET['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    if (strlen($GLOBALS['conn']->query("SELECT site FROM `sites` WHERE site='$url' and email='$email'")->fetchColumn()) !== 0) {
                        $response = array(
                            'response' => 'exists'
                        );
                        echo json_encode($response);
                        exit;
                    } else {
                        $GLOBALS['conn']->query("INSERT INTO sites (site, email, thresh, name) VALUES ('$url', '$email', '$thresh', '$name')");
                        $GLOBALS['conn']->query("INSERT INTO `$email`(`name`, `sites`, `email`, `pass`) VALUES ('$name', '$url', '$email', 'DATA')");
                        
                        $sql = "CREATE TABLE IF NOT EXISTS `$url` (
                            `id` int AUTO_INCREMENT,
				            `time` timestamp DEFAULT CURRENT_TIMESTAMP,
                            `outage` int(1),
                            
                            `us-status` int(1),
                            `ie-status` int(1),
                            
                            `us-latency` varchar(10),
                            `ie-latency` varchar(10),
                            
                            `us-data` int(255),
                            `ie-data` int(255),
                            
                            `us-code` int(3),
                            `ie-code` int(3),
                            
                            `us-lookup` decimal(65, 3),
                            `ie-lookup` decimal(65, 3),
                            
                            `us-speed` decimal(65, 3),
                            `ie-speed` decimal(65, 3),
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `id` (`id`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
                        $GLOBALS['conn']->exec($sql);
                        
                        $urlx = 'https://' . $_SERVER[HTTP_HOST] . '/api/v2/manager.php?new=true&url=' . $_GET["url"];
                        $c = curl_init();
                        curl_setopt($c, CURLOPT_URL, $urlx);
                        curl_setopt($c, CURLOPT_HEADER, TRUE);
                        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
                        curl_setopt($c, CURLOPT_FRESH_CONNECT, TRUE);
                        curl_setopt($c, CURLOPT_FILETIME, TRUE);
                        curl_setopt($c, CURLOPT_CERTINFO, TRUE);
                        curl_exec($c);
                        curl_close($c);
                        
                        $response = array(
                            'response' => 'success'
                        );
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = array(
                        'response' => 'mismatch',
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                addSite();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function signup() {
        try {
            $name = $_GET['name'];
            $email = strtolower($_GET['email']);
            $pass = password_hash($_GET["pass"], PASSWORD_BCRYPT, ['salt' => mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)]);
            $sql = "CREATE TABLE IF NOT EXISTS `sites` (
				`id` int AUTO_INCREMENT,
				`site` varchar(255),
				`email` varchar(255),
				`thresh` int(255),
				`name` varchar(255),
                `us-speed` decimal(65, 3) DEFAULT 0,
                `ie-speed` decimal(65, 3) DEFAULT 0,
                `us-latency` decimal(65, 3) DEFAULT 0,
                `ie-latency` decimal(65, 3) DEFAULT 0,
                `us-lookup` decimal(65, 3) DEFAULT 0,
                `ie-lookup` decimal(65, 3) DEFAULT 0,
                `checks` int(255) DEFAULT 0,
                `checks-mn` int(255) DEFAULT 0,
                `checks-wk` int(255) DEFAULT 0,
                `us-uptime` decimal(65, 3) DEFAULT 100,
                `ie-uptime` decimal(65, 3) DEFAULT 100,
                `us-uptime-wk` decimal(65, 3) DEFAULT 100,
                `ie-uptime-wk` decimal(65, 3) DEFAULT 100,
                `us-uptime-mn` decimal(65, 3) DEFAULT 100,
                `ie-uptime-mn` decimal(65, 3) DEFAULT 100,
                `us-ssl-auth` longtext,
                `ie-ssl-auth` longtext,
                `us-ssl-exp` longtext,
                `ie-ssl-exp` longtext,
				UNIQUE KEY `id` (`id`),
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $GLOBALS['conn']->exec($sql);
            
            $sql = "CREATE TABLE IF NOT EXISTS `$email` (
				`name` varchar(255),
                `sites` varchar(255),
                `email` varchar(255),
                `pass` longtext,
                `token` longtext,
                UNIQUE KEY `sites` (`sites`),
                PRIMARY KEY (`sites`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            $GLOBALS['conn']->exec($sql);
			$hash = hash('sha512', uniqid());
            $GLOBALS['conn']->exec("INSERT INTO `$email`(`sites`, `email`, `pass`, `name`, `token`) VALUES ('".$hash."', '$email', '$pass', '$name', '')");
            
            $urlx = 'https://' . $_SERVER[HTTP_HOST] . '/api/v2/notify/?type=verify&email=' . $email . '&hash=' . $hash . '&name=' . str_replace(" ", "%20", $name);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlx);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);
            curl_close($ch);
            
            if ($email !== '') {
                $response = array(
                    'response' => 'verify',
                    'name' => $_GET['name'],
                    'email' => $email,
					'pass' => $_GET["pass"]
                );
                echo json_encode($response);
                exit;
            }
        } catch(PDOException $e) {
            if ($e->getCode() === 1203) {
                signUp();
            } else {
                $response = array(
                    'response' => 'exists',
                    'email' => $_GET['email'],
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
    }
	
	function getMoreStats() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'name' => null
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $email = strtolower($_GET['email']); $id = $_GET['id'];
                    $site = $GLOBALS['conn']->query("SELECT site FROM `sites` WHERE email='$email' AND id=$id")->fetchColumn();
                    $siteArr = array(); $siteRows = array(); $rows = array(); $siteArr2 = array();
                    
                    $sth = $GLOBALS['conn']->query("SELECT * FROM `$site` ORDER BY id DESC");
                    while($r = $sth->fetch(PDO::FETCH_ASSOC)) {
                        if($r["us-status"] === "0") {
                            $r["us-status"] = "Up";
                        } if ($r["us-status"] === "1") {
                            $r["us-status"] = "Down";
                        } if ($r["us-status"] === "2") {
                            $r["us-status"] = "Timeout";
                        }
                        if($r["ie-status"] === "0") {
                            $r["ie-status"] = "Up";
                        } if ($r["ie-status"] === "1") {
                            $r["ie-status"] = "Down";
                        } if ($r["ie-status"] === "2") {
                            $r["ie-status"] = "Timeout";
                        }
                        // $siteRows = array(
                        //     'data' => $r
                        // );
                        $siteArr = array(
                            $r
                        );
                        array_push($siteArr2, $siteArr);
                    }
                    $rows = array(
                        'site' => $site,
                        'id' => (int)$GLOBALS['conn']->query("SELECT id from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'length' => (int)$GLOBALS['conn']->query("SELECT count(*) FROM `$site`")->fetchColumn(),
                        'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site' AND email='$email'")->fetchColumn()),
                        'thresh' => (int)$GLOBALS['conn']->query("SELECT thresh from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-uptime' => $GLOBALS['conn']->query("SELECT `us-uptime` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-uptime' => $GLOBALS['conn']->query("SELECT `ie-uptime` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-uptime-wk' => $GLOBALS['conn']->query("SELECT `us-uptime-wk` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-uptime-wk' => $GLOBALS['conn']->query("SELECT `ie-uptime-wk` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-uptime-mn' => $GLOBALS['conn']->query("SELECT `us-uptime-mn` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-uptime-mn' => $GLOBALS['conn']->query("SELECT `ie-uptime-mn` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-lookup' => $GLOBALS['conn']->query("SELECT `us-lookup` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-lookup' => $GLOBALS['conn']->query("SELECT `ie-lookup` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-speed' => $GLOBALS['conn']->query("SELECT `us-speed` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-speed' => $GLOBALS['conn']->query("SELECT `ie-speed` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-latency' => $GLOBALS['conn']->query("SELECT `us-latency` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-latency' => $GLOBALS['conn']->query("SELECT `ie-latency` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-ssl-auth' => $GLOBALS['conn']->query("SELECT `us-ssl-auth` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-ssl-auth' => $GLOBALS['conn']->query("SELECT `ie-ssl-auth` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'us-ssl-exp' => $GLOBALS['conn']->query("SELECT `us-ssl-exp` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        'ie-ssl-exp' => $GLOBALS['conn']->query("SELECT `ie-ssl-exp` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        $siteArr2
                    );
                    echo json_encode($rows);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                        'email' => $_GET['email']
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                getSite();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'email' => $_GET['email'],
                    'more' => $e->getMessage(),
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
    function login() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
	    try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE pass <> 'DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'name' => null
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    if ($GLOBALS['conn']->query("SELECT sites FROM `$email` WHERE pass <> 'DATA'")->fetchColumn() !== "DATA") {
                        $response = array(
                            'response' => 'verify',
                            'email' => strtolower($email)
                        );
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = array(
                            'response' => 'success',
                            'email' => strtolower($email),
                            'name' => $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE pass <> 'DATA'")->fetchColumn()
                        );
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = array(
                        'response' => 'mismatch',
                        'email' => $_GET['email'],
                        'name' => null
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                login();
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'name' => null
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'email' => $_GET['email'],
                    'more' => $e->getMessage(),
                    'name' => null
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function getSite() {
        $email = strtolower($_GET["email"]);
        $pass = $_GET['pass'];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'name' => null
                );
                echo json_encode($response);
                exit;
            } else {
                if (password_verify($pass, $dbPass)) {
                    $email = strtolower($_GET['email']);
                    $sth = $GLOBALS['conn']->query("SELECT site FROM `sites` where email='$email'");
                    $rows = array(); $rows2 = array();  $sitesArr = array();
                    while($r1 = $sth->fetch(PDO::FETCH_ASSOC)) {
                        $rows[] = $r1;
                    }
                    foreach ($rows as $row) {
                        $sitesArr[] = $row["site"];   
                    }
                    
                    foreach($sitesArr as $site) {
                        $sth = $GLOBALS['conn']->query("SELECT * FROM `$site` ORDER BY id DESC LIMIT 1");
                        $siteArr2 = array(
                            'site' => $site,
                            'id' => (int)$GLOBALS['conn']->query("SELECT id from `sites` where site='$site' and email='$email'")->fetchColumn(),
                            'length' => (int)$GLOBALS['conn']->query("SELECT count(*) FROM `$site`")->fetchColumn(),
                            'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site'")->fetchColumn()),
                            'us-uptime' => $GLOBALS['conn']->query("SELECT `us-uptime` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'ie-uptime' => $GLOBALS['conn']->query("SELECT `ie-uptime` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'us-uptime-mn' => $GLOBALS['conn']->query("SELECT `us-uptime-mn` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'ie-uptime-mn' => $GLOBALS['conn']->query("SELECT `ie-uptime-mn` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'us-uptime-wk' => $GLOBALS['conn']->query("SELECT `us-uptime-wk` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'ie-uptime-wk' => $GLOBALS['conn']->query("SELECT `ie-uptime-wk` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'us-ssl-exp' => $GLOBALS['conn']->query("SELECT `us-ssl-exp` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                            'ie-ssl-exp' => $GLOBALS['conn']->query("SELECT `ie-ssl-exp` from `sites` WHERE site='$site' AND email='$email'")->fetchColumn(),
                        );
                        
                        while($r = $sth->fetch(PDO::FETCH_ASSOC)) {
                            if($r["us-status"] === "0") {
                                $r["us-status"] = "Up";
                            } if ($r["us-status"] === "1") {
                                $r["us-status"] = "Down";
                            } if ($r["us-status"] === "2") {
                                $r["us-status"] = "Timeout";
                            }
                            if($r["ie-status"] === "0") {
                                $r["ie-status"] = "Up";
                            } if ($r["ie-status"] === "1") {
                                $r["ie-status"] = "Down";
                            } if ($r["ie-status"] === "2") {
                                $r["ie-status"] = "Timeout";
                            }
                            $siteRows = array(
                                'data' => $r
                            );
                            array_push($siteArr2, $siteRows);
                        }
                        array_push($rows2, $siteArr2);
                    }
                    echo json_encode($rows2);
                    exit;
                } else {
                    $response = array(
                        'response' => 'mismatch',
                        'email' => $_GET['email'],
                        'name' => null
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                getSite();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    'response' => 'mismatch',
                    'email' => $_GET['email'],
                    'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site'")->fetchColumn())
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    'response' => 'error',
                    'email' => $_GET['email'],
                    'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site'")->fetchColumn()),
                    'more' => $e->getMessage()
                );
                echo json_encode($response);
                exit;
            }
        }
	}
?>