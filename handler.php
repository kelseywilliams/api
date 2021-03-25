<?php



    /*if($METHOD == "PATCH"){

    }

    if($METHOD == "DELETE"){

    }

    function authenticate($key){
        global $DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB;
        // table api_keys with fields key, boolean revoked, expiration_date in seconds since unix epoch
        $response = getDbResponse("SELECT * FROM api_keys WHERE key=\"{$key}\" AND valid=\"true\"");
        if($response == 0){
            return false;
        }
        else{
            $time_to_live = $response["expiration"] - time();
            if($time_to_live < 1){
                queryDb("UPDATE api_keys SET valid=\"false\" WHERE key=\"{$key}\"");
                return false;
            }
        }
        return true;
    }

    // Database access function using standard querying which returns the results as an array
    function getDbResponse($query){
        global $DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB;
        $mysqli = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

        $result = $mysqli->query($query);
        $mysqli->close();
        if($result->num_rows > 0){
            return $result->fetch_assoc();
        }
        else{
            return 0;
        }
    }

    // Database access function using standard querying with no return value
    function queryDb($query){
        global $DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB;
        $mysqli = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

        $mysqli->set_charset("utf8mb4");

        $mysqli->query($query);
        $mysqli->close();
    }

    // Database access function using prepared statements and a preselected table
    function queryDbPrepStmt($data, $date, $flag){
        global $DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB;
        $mysqli = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

        $mysqli->set_charset("utf8mb4");

        // Prepare the sql statement and insert the data into the database
        $stmt = $mysqli->prepare("INSERT INTO data (data, time, flag) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $data, $date, $flag);
        $stmt->execute();
        $stmt->close();
    }
    echo hash("sha256", uniqid());*/

    class API{

        // All response bodies returned as JSON with keys status, data, date, flag
        function __construct($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB){
            $this->DB_HOST = $DB_HOST;
            $this->DB_USERNAME = $DB_USERNAME;
            $this->DB_PASSWORD = $DB_PASSWORD;
            $this->DB = $DB;
        }

        public static function authenticate_api_key($key, $db_host, $db_username, $db_password, $db){
            // table api_keys with fields key, boolean revoked, expiration_date in seconds since unix epoch
            $mysqli = new mysqli($db_host, $db_username, $db_password, $db);

            $response = $mysqli->query("SELECT * FROM api_keys WHERE api_key=\"{$key}\" AND valid=\"true\";");
            if($response->num_rows > 0){
                $response = $response->fetch_assoc();
                $time_to_live = $response["expiration"] - time();
                if($time_to_live < 1){
                    $mysqli->set_charset("utf8mb4");

                    $mysqli->query("UPDATE api_keys SET valid=\"false\" WHERE key=\"{$key}\"");
                    $mysqli->close();
                    return false;
                }
                else{
                    $mysqli->close();
                    return true;
                }
            }
            else{
                return false;
            }
        }

        // non indempotent
        // Returns response code 201 for created objects are 200 when not created
        function post(){
            $key = $_POST["key"];
            $data = $_POST["data"];
            $date = $_POST["date"];
            $flag = $_POST["flag"];

            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);


            $mysqli->set_charset("utf8mb4");

            // Prepare the sql statement and insert the data into the database
            $stmt = $mysqli->prepare("INSERT INTO " . $hash . " (data, date, flag) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $data, $date, $flag);
            $result = $stmt->execute();
            $stmt->close();

            if($result){
                http_response_code(201);
            }
            else{
                http_response_code(200);
            }
            exit();
        }

        // indempotent
        function get(){
            $key = $_GET["key"];

            // Why is php so damn dramatic?  We got die() and now explode().  Did Michael Bay direct this language?
            $pieces = explode(".", $key);

            $prefix = $pieces[0];
            $hash = $pieces[1];

            if(!API::authenticate_api_key($key, $this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB)){
                http_response_code(400);
                exit();
            }

            $mysqli = new mysqli($this->DB_HOST, $this->DB_USERNAME, $this->DB_PASSWORD, $this->DB);

            $response = $mysqli->query("SELECT * FROM " . $hash );
            echo $mysqli->error;
            if($response->num_rows > 0){
                $arr = $response->fetch_assoc();
            }
            else{
                $arr = [];
            }
            echo json_encode($arr, JSON_FORCE_OBJECT);

            http_response_code(200);
        }
    }

    $config = include("config.php");
    $DB_HOST = $config["host"];
    $DB_USERNAME = $config["username"];
    $DB_PASSWORD = $config["password"];
    $DB = $config["db"];

    $METHOD = $_SERVER["REQUEST_METHOD"];

    $api = new API($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB);

    if($METHOD == "POST"){
        $api->post();
    }

    if($METHOD == "GET"){
        $api->get();
    }
?>