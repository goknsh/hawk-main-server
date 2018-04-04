<?php
    session_start();
    error_reporting(0);
    ini_set('memory_limit', '100G'); 
    header('Content-Type: application/json');
    
	connect();
	
	function connect() {
    	try {
            global $conn;
            $conn = new PDO("mysql:host=localhost:3306;dbname=ping", "root", "root", array(PDO::ATTR_PERSISTENT => true));
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
            } else {
                $response = array(
                    response => 'up'
                );
            }
    	} catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                connect();
            } else {
                $response = array(
                    response => 'error',
                    email => null,
                    name => null,
                    more => $e->getMessage()
                );
                echo json_encode($response);
            }
    	}
	}
	
	function deleteSite() {
	    $email = strtolower($_GET["email"]);
        $pass = hash('sha512', $_GET['pass']);
        $id = (int)$_GET["id"];
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    response => 'mismatch'
                );
                echo json_encode($response);
            } else {
                if ($dbPass === $pass) {
                    $GLOBALS['conn']->exec("delete from `$email` where sites='$siteURL'");
                    $GLOBALS['conn']->exec("delete from `sites` where id=$id");
                    
                    $response = array(
                        response => 'success'
                    );
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array(
                        response => 'mismatch',
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
                    response => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    response => 'error',
                    more => $e->getMessage()
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
	function addSite() {
	    $email = strtolower($_GET["email"]);
        $pass = hash('sha512', $_GET['pass']);
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    response => 'mismatch'
                );
                echo json_encode($response);
            } else {
                if ($dbPass === $pass) {
                    $url = $_GET['url']; $thresh = $_GET['timeout']; $name = htmlentities($_GET['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    if (strlen($GLOBALS['conn']->query("SELECT email, site FROM `sites` WHERE site='$url' and email='$email'")->fetchColumn()) !== 0) {
                        $response = array(
                            response => 'exists'
                        );
                        echo json_encode($response);
                        exit;
                    } else {
	    				$email = strtolower($_GET["email"]); $url = $_GET['url']; $thresh = $_GET['timeout']; $name = htmlentities($_GET['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $GLOBALS['conn']->query("INSERT INTO sites (site, email, thresh, name) VALUES ('$url', '$email', '$thresh', '$name')");
                        $GLOBALS['conn']->query("INSERT INTO `$email`(`sites`, `email`, `pass`) VALUES ('$url', '$email', 'DATA')");
                        
                        $sql = "CREATE TABLE IF NOT EXISTS `$url` (
                            `id` int AUTO_INCREMENT,
                            `outage` int,
                            `time` timestamp,
                            
                            `us-status` int(1),
                            `ie-status` int(1),
                            `in-status` int(1),
                            
                            `us-latency` varchar(10),
                            `ie-latency` varchar(10),
                            `in-latency` varchar(10),
                            
                            `us-data` int(255),
                            `ie-data` int(255),
                            `in-data` int(255),
                            
                            `us-code` int(3),
                            `ie-code` int(3),
                            `in-code` int(3),
                            
                            `us-lookup` int(255),
                            `ie-lookup` int(255),
                            `in-lookup` int(255),
                            
                            `us-ssl-authority` varchar(255),
                            `ie-ssl-authority` varchar(255),
                            `in-ssl-authority` varchar(255),
                            
                            `us-ssl-expiry` varchar(255),
                            `ie-ssl-expiry` varchar(255),
                            `in-ssl-expiry` varchar(255),
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `id` (`id`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
                        $GLOBALS['conn']->exec($sql);
                        
                        // $urlx = '/api/v1/cron?new=true&url=' . $url;
                        // $c = curl_init();
                        // curl_setopt($c, CURLOPT_URL, $urlx);
                        // curl_setopt($c, CURLOPT_HEADER, TRUE);
                        // curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
                        // curl_setopt($c, CURLOPT_FRESH_CONNECT, TRUE);
                        // curl_setopt($c, CURLOPT_FILETIME, TRUE);
                        // curl_setopt($c, CURLOPT_CERTINFO, TRUE);
                        // curl_exec($c);
                        // curl_close($c);
                        
                        $response = array(
                            response => 'success'
                        );
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = array(
                        response => 'mismatch',
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
                    response => 'mismatch'
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    response => 'error',
                    more => $e->getMessage()
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
            $pass = hash('sha512', $_GET['pass']);
            $sql = "CREATE TABLE `sites` (
				`id` int AUTO_INCREMENT,
				`site` varchar(255),
				`email` varchar(255),
				`thresh` int,
				`name` varchar(255),
                `speed` int,
                `latency` int,
                `checks` int,
                `us-uptime` int,
                `ie-uptime` int,
                `in-uptime` int,
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
			
            $GLOBALS['conn']->exec("INSERT INTO `$email`(`sites`, `email`, `pass`, `name`, `token`) VALUES ('DATA', '$email', '$pass', '$name', '')");
            
            if ($email !== '') {
                $_SESSION["email"] = strtolower($email);
                $_SESSION["password"] = $pass;
                $_SESSION["name"] = $_GET['pass'];
                
                $response = array(
                    response => 'success',
                    name => $name,
                    email => $email,
					pass => $_GET["pass"]
                );
                echo json_encode($response);
            }
        } catch(PDOException $e) {
            if ($e->getCode() === 1203) {
                signUp();
            } else {
                $response = array(
                    response => 'exists',
                    name => null,
                    email => null
                );
                echo json_encode($response);
            }
        }
    }
	
	function getMoreStats() {
        $email = strtolower($_GET["email"]);
        $pass = hash('sha512', $_GET['pass']);
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
                exit;
            } else {
                if ($dbPass === $pass) {
                    $email = strtolower($_GET['email']); $id = $_GET['id'];
                    $site = $GLOBALS['conn']->query("SELECT site FROM `sites` WHERE email='$email' AND id=$id")->fetchColumn();
                    $siteArr = array(); $siteRows = array(); $rows = array(); $siteArr2 = array();
                    
                    $sth = $GLOBALS['conn']->query("SELECT * FROM `$site` ORDER BY id DESC");
                    while($r = $sth->fetch(PDO::FETCH_ASSOC)) {
                        if($r["us-status"] === "1") {
                            $r["us-status"] = "Up";
                        } if ($r["us-status"] === "0") {
                            $r["us-status"] = "Down";
                        } if ($r["us-status"] === "2") {
                            $r["us-status"] = "Timeout";
                        } 
                        if($r["in-status"] === "1") {
                            $r["in-status"] = "Up";
                        } if ($r["in-status"] === "0") {
                            $r["in-status"] = "Down";
                        } if ($r["in-status"] === "2") {
                            $r["in-status"] = "Timeout";
                        } 
                        if($r["ie-status"] === "1") {
                            $r["ie-status"] = "Up";
                        } if ($r["ie-status"] === "0") {
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
                        'id' => (int)$GLOBALS['conn']->query("SELECT id from `sites` where site='$site' AND email='$email'")->fetchColumn(),
                        'length' => (int)$GLOBALS['conn']->query("SELECT count(*) FROM `$site`")->fetchColumn(),
                        'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site' AND email='$email'")->fetchColumn()),
                        'thresh' => (int)$GLOBALS['conn']->query("SELECT thresh from `sites` where site='$site' AND email='$email'")->fetchColumn(),
                        $siteArr2
                    );
                    echo json_encode($rows);
                } else {
                    $response = array(
                        response => 'mismatch',
                        email => null,
                        name => null
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
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    response => 'error',
                    email => null,
                    name => null,
                    more => $e->getMessage()
                );
                echo json_encode($response);
                exit;
            }
        }
	}
	
    function login() {
        $email = strtolower($_GET["email"]);
        $pass = hash('sha512', $_GET['pass']);
	    try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
            } else {
                if ($dbPass === $pass) {
                    $response = array(
                        response => 'success',
                        email => strtolower($email),
                        name => $dbName
                    );
                    echo json_encode($response);
                } else {
                    $response = array(
                        response => 'mismatch',
                        email => null,
                        name => null
                    );
                    echo json_encode($response);
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === 1203) {
                login();
            } if ($e->getCode() === '42S02') {
                $response = array(
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
            } else {
                $response = array(
                    response => 'error',
                    email => null,
                    name => null,
                    more => $e->getMessage()
                );
                echo json_encode($response);
            }
        }
	}
	
	function getSite() {
        $email = strtolower($_GET["email"]);
        $pass = hash('sha512', $_GET['pass']);
        try {
            $dbPass = $GLOBALS['conn']->query("SELECT pass FROM `$email` WHERE sites='DATA'")->fetchColumn();
            $dbName = $GLOBALS['conn']->query("SELECT name FROM `$email` WHERE sites='DATA'")->fetchColumn();
            
            if ($dbPass === null) {
                $response = array(
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
                exit;
            } else {
                if ($dbPass === $pass) {
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
                            'name' => htmlspecialchars_decode($GLOBALS['conn']->query("SELECT name FROM sites WHERE site='$site'")->fetchColumn())
                        );
                        
                        while($r = $sth->fetch(PDO::FETCH_ASSOC)) {
                            if($r["us-status"] === "1") {
                                $r["us-status"] = "Up";
                            } if ($r["us-status"] === "0") {
                                $r["us-status"] = "Down";
                            } if ($r["us-status"] === "2") {
                                $r["us-status"] = "Timeout";
                            } 
                            $siteRows = array(
                                'data' => $r
                            );
                            array_push($siteArr2, $siteRows);
                        }
                        array_push($rows2, $siteArr2);
                    }
                    echo json_encode($rows2);
                } else {
                    $response = array(
                        response => 'mismatch',
                        email => null,
                        name => null
                    );
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
			echo $e->getMessage();
            if ($e->getCode() === 1203) {
                getSite();
                exit;
            } if ($e->getCode() === '42S02') {
                $response = array(
                    response => 'mismatch',
                    email => null,
                    name => null
                );
                echo json_encode($response);
                exit;
            } else {
                $response = array(
                    response => 'error',
                    email => null,
                    name => null,
                    more => $e->getMessage()
                );
                echo json_encode($response);
                exit;
            }
        }
	}
?>