<?php
    $METHOD = $_SERVER["REQUEST_METHOD"];
    if($METHOD == "POST"){
        session_start();
        $config = include("config.php");
        $DB_HOST = $config["host"];
        $DB_USERNAME = $config["username"];
        $DB_PASSWORD = $config["password"];
        $DB = $config["db"];

        $prefix = uniqid();
        $hash = hash("sha256", uniqid());
        $key = $prefix . "." . $hash;
        $expiration = time() + 604800;
        $time = time();
        $rate = 5;
        $client_email = $_POST["email"];
        
        $mysqli = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

        $result = $mysqli->query("SELECT * FROM api_keys WHERE email=\"$client_email\"");
        if($result->num_rows > 0){
            $mysqli->close();
            $_SESSION["error"] = "A key already exists for " . $client_email;
            header("Location: /API/keys.html", 301);
            exit();
        }

        $mysqli->set_charset("utf8mb4");
        $result = $mysqli->query("CREATE TABLE " . $hash . " (data TEXT(65535), date TEXT(255), flag TEXT(20));");
        if($result){
            $result = $mysqli->query("INSERT INTO api_keys (api_key, valid, expiration, rate, last_op, email) VALUES (\"{$key}\", \"true\", \"{$expiration}\", \"{$rate}\", \"{$time}\", \"$client_email\");");
        }

        if($result){
           /* ini_set( 'display_errors', 1 );
            error_reporting( E_ALL );
            $from = "admin@kelseywilliams.net";
            $to = $client_email;
            $subject = "API key";
            $message = "Your api key is " . $key . "\nDo not lose this key as you are only allowed one until it expires.";
            $headers = "From:" . $from;
            if(mail($to,$subject,$message, $headers)) {
                $_SESSION["success"] = "Your api key has been sent to " . $client_email . "! Read below for instructions on how to use the service.  Enjoy!";
                exit();
            } 
            else {
                $_SESSION["error"] = "An error occured when trying to send the email to " . $client_email . ".  No email was sent and no key was issued.  Please try again or contact the website adminstrator.";
                $mysqli->query("DELETE FROM api_keys WHERE api_key=\"$key\";");
                exit();
            }*/
            $_SESSION["success"] = "Your api key has been sent to " . $client_email . "! Read below for instructions on how to use the service.  Enjoy!";
        }
        else{
            $_SESSION["error"] = "An error occurred when pushing your api key to the database.  No email was sent.  Please contact the website adminstrator.";
        }
        $mysqli->close();
        header("Location: /API/keys.html", 301);
    }
    else if($METHOD == "GET"){
        header("Location: /API/keys.html", 301);
        exit();
    }
?>