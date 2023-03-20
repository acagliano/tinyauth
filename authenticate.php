<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$http_status = 200;

$response = array("success" => false, "error" => false);

$pubkey = openssl_get_publickey(file_get_contents("tools/pubkey.ec.pem"));
if(array_key_exists("user", $_GET) && array_key_exists("token", $_GET)) {
	$SQL_HOST="localhost";
	$SQL_USER=$_ENV['SQL_USER'];
	$SQL_DB=$_ENV['SQL_DB'];
    $SQL_PW=$_ENV['SQL_PASSWD'];
	
	$auth_log_file = "logs/keychecks.log";
	$auth_json_obj = json_decode(file_get_contents($auth_log_file));
	$auth_log_object = array(
							 "timestamp"=>date("Y-m-d H:i"),
							 "remote-ip"=>$_SERVER['HTTP_X_FORWARDED_FOR'],
							 "user"=>$_GET["user"],
							 );
	$auth_status_string = "error";


    $conn = mysqli_connect($SQL_HOST, $SQL_USER, $SQL_PW, $SQL_DB);
    if(!$conn->connect_errno) {
        $stmt_find_user = $conn->prepare('select * from cred where username = ?');
        $stmt_find_user->bind_param('s', $_GET["user"]);
        $stmt_find_user->execute();
        $sql_response = $stmt_find_user->get_result();
        if($sql_response->num_rows) {
            $row = $sql_response->fetch_assoc();
            if($pubkey) {
                $token = hash_pbkdf2("sha512", $row["password"], $row["pretoken"], 1000, 64, true);
                $match = openssl_verify($_GET["user"].$token, $_GET["token"], $pubkey, openssl_get_md_methods()[14]);
                if($match == 1) {
					$response["success"] = true;
					$auth_status_string = "success";
                }
                if($match == 0) {
					$http_status = 403;
					$auth_status_string = "failure";
                }
                if($match == -1) { $response["error"] = "openssl verification error"; $http_status = 500;
                }
                openssl_free_key($pubkey);
            }
            else { $response["error"] = "invalid pubkey"; $http_status = 500;
            }
            $conn->close();
        }
        else { $response["error"] = "no profile for user found"; $http_status = 403;
        }
    } else { $response["error"] = "sql connection error"; $http_status = 500;
    }
	$auth_log_object["status"] = $auth_status_string;
	$auth_json_obj[] = $auth_log_object;
	file_put_contents($auth_log_file, json_encode($auth_json_obj));
}
else { $response["error"] = "request empty"; $http_status = 400;
}
ob_clean();
header_remove();
header('Content-Type: application/json; charset=utf-8');
http_response_code($http_status);
echo json_encode($response);
exit();


/*function is_conn_secure(){
	return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
			(isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
			(isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] == 443) ||
			(isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
			);
}*/
?>
