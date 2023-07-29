<?php
    $http_response = 401;
    $json_response = array(
        "success"=>false,
        "error"=>false
    );

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $env = parse_ini_file('.env');
        $user = filter_input(INPUT_POST, "user", FILTER_SANITIZE_STRING);
        if(($user !== null) && ($user !== false)){
            $signature = filter_input(INPUT_POST, "token", FILTER_DEFAULT);
            if(($signature !== null) && ($signature !== false)){
                $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
                $get_secret_stmt = $conn->prepare("SELECT id,secret FROM credentials WHERE user=?");
                $get_secret_stmt->bind_param("s", $user);
                $result = $get_secret_stmt->get_result();
                $row = $result->fetch_assoc();
                $token = hash("sha512", $row["id"].$row["secret"], true);
                $pubkey = openssl_get_publickey(file_get_contents(".secrets/pubkey.pem"));
                if($pubkey){
                    if(openssl_verify($token, $signature, $pubkey, openssl_get_md_methods()[14]) == 1){
                        $json_response["success"] = true;
                        $http_response = 200;
                    } else {
                        $http_response = 403;
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
    exit();
?>