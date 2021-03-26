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
        $rate = 1;
        $client_email = $_POST["email"];

        if(!preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $client_email)){
            $_SESSION["error"] = "Error: Invalid email";
            header("Location: /api/", 301);
            exit();
        }
        
        $mysqli = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

        $result = $mysqli->query("SELECT * FROM api_keys WHERE email=\"$client_email\" AND valid=\"true\";");
        if($result->num_rows > 0){
            $mysqli->close();
            $_SESSION["error"] = "Error: A key already exists for " . $client_email;
            header("Location: /api/", 301);
            exit();
        }

        $mysqli->set_charset("utf8mb4");
        $result = $mysqli->query("CREATE TABLE " . $hash . " (data MEDIUMTEXT, date TINYTEXT, flag TINYTEXT, id MEDIUMTEXT);");
        if($result){
            $result = $mysqli->query("INSERT INTO api_keys (api_key, valid, expiration, rate, last_op, email) VALUES (\"{$key}\", \"true\", \"{$expiration}\", \"{$rate}\", \"{$time}\", \"$client_email\");");
        }
        echo $mysqli->error;

        if($result){
            ini_set( 'display_errors', 1 );
            error_reporting( E_ALL );
            $from = "admin@kelseywilliams.net";
            $to = $client_email;
            $subject = "kelseywilliams.net api key";
            $message = "Your api key is: " . $key . "\nDo not lose this key as you are only allowed one key per email until it expires in 7 days.\nRead the documentation on the kelseywilliams.net api homepage for details on connecting to and using the api. ";
            $headers = "From:" . $from;
            if(mail($to,$subject,$message, $headers)) {
                $_SESSION["success"] = "Success: Your api key has been sent to " . $client_email . "! Read below for instructions on how to use the service.  Enjoy!";
            } 
            else {
                $_SESSION["error"] = "Error: An error occured when trying to send the email to " . $client_email . ".  No email was sent and no key was issued.  Please try again or contact the website adminstrator.";
                $mysqli->query("DELETE FROM api_keys WHERE api_key=\"$key\";");
            }
        }
        else{
            $_SESSION["error"] = "Error: An error occurred when pushing your api key to the database.  No email was sent.  Please contact the website adminstrator.";
        }
        $mysqli->close();
        header("Location: /api/", 301);
    }
    else if($METHOD == "GET"){
        header("Location: /api/", 301);
        exit();
    }
?>