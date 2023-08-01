<?php
    $http_response = 401;
    $json_response = array(
        "success"=>"false",
        "error"=>"false"
    );
    $log_item = array(
        "ip"=>getRealIPAddr(),
        "timestamp"=>date("Y-m-d H:i:s"),
        "result"=>"error"
    );

    function getRealIPAddr(){
        //check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $env = parse_ini_file('.env');
        $user = filter_input(INPUT_POST, "user", FILTER_VALIDATE_INT);
        if(($user !== null) && ($user !== false)){
            $signature = filter_input(INPUT_POST, "token", FILTER_DEFAULT);
            if(($signature !== null) && ($signature !== false)){
                $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
                $get_secret_stmt = $conn->prepare("SELECT secret FROM credentials WHERE id=?");
                $get_secret_stmt->bind_param("i", $user);
                $get_secret_stmt->execute();
                $get_secret_stmt->bind_result($secret);
                $get_secret_stmt->fetch();
                $get_secret_stmt->close();
                $token = hash("sha512", $user.$secret, true);
                $log_item["user-id"] = $user;
                $pubkey = openssl_get_publickey(file_get_contents(".secrets/pubkey.pem"));
                if($pubkey){
                    if(openssl_verify($token, $signature, $pubkey, openssl_get_md_methods()[14]) == 1){
                        $json_response["success"] = "true";
                        $http_response = 200;
                        $log_item["result"] = "success";
                    } else {
                        $http_response = 403;
                        $log_item["result"] = "fail";
                    }
                } else {
                    $http_response = 500;
                    $json_response["error"] = "OpenSSL: Error loading public key";
                }
            } else {
                $http_response = 400;
                $json_response["error"] = "Invalid token field in request";
            }
        } else {
            $http_response = 400;
            $json_response["error"] = "Invalid user field in request";
        }
    }
    else {
        $http_response = 400;
        $json_response["error"] = "Invalid request type";
    }
    $log_item["error-string"] = $json_response["error"];
    $json_logdata = json_decode(file_get_contents('logs/auth.log'), true);
    $json_logdata[] = $log_item;
    file_put_contents('logs/auth.log', json_encode($json_logdata));

    ob_clean();
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    $json_out = json_encode($json_response);
    if ($json_out === false) {
        // Set HTTP response status code to: 500 - Internal Server Error
        $http_response = 500;
        // Avoid echo of empty string (which is invalid JSON), and
        // JSONify the error message instead:
        $json_out = json_encode(["jsonError" => json_last_error_msg()]);
        if ($json_out === false) {
            // This should not happen, but we go all the way now:
            $json_out = '{"jsonError":"unknown"}';
        }
    }
    echo $json_out;
    http_response_code($http_response);
    exit();
?>