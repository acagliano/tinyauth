<?php

    require '../vendor/autoload.php';
    use Sendgrid\Mail\Mail;
    use OTPHP\TOTP;
    use ParagonIE\ConstantTime\Base32;
    $email_string = "";

    $http_response = 401;
    $json_response = array(
        "success"=>"false",
        "error"=>""
    );
    $timestamp = date("Y-m-d H:i:s");
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
    $logline = "Authentication request from $remote_ip. Unknown error.";
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $env = parse_ini_file('../.env');
        $log_item = array(
            "querying-ip"=>getRealIPAddr(),
            "origin-ip"=>"",
            "timestamp"=>$timestamp,
            "result"=>"error",
            "error"=>""
        );
        $participant_email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
        $origin_ip = filter_input(INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE);
        if(($origin_ip == null) || ($origin_ip == false)){
            $origin_ip = filter_input(INPUT_POST, "origin", FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE);
        }
        if(($origin_ip !== null) && ($origin_ip !== false)){
            $log_item["origin-ip"] = $origin_ip;
            $user = filter_input(INPUT_POST, "user", FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if(($user !== null) && ($user !== false)){
                $log_item["user-id"] = $user;
                $signature = filter_input(INPUT_POST, "token", FILTER_DEFAULT);
                if(($signature !== null) && ($signature !== false)){
                    $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
                    $get_blacklist_stmt = $conn->prepare("SELECT * FROM greylist WHERE ip=? and assoc_id=?");
                    $get_blacklist_stmt->bind_param("si", $log_item["origin-ip"], $user);
                    $get_blacklist_stmt->execute();
                    $existing_result = $get_blacklist_stmt->get_result();
                    $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
                    if(count($rows)==0){
                        $get_secret_stmt = $conn->prepare("SELECT secret,email,notify_flags,2fa_flags,2fa_secret FROM credentials WHERE id=48");
                        $get_secret_stmt->execute();
                        $get_secret_stmt->bind_result($secret, $email, $notify_flags, $twof_flags, $twof_secret);
                        $get_secret_stmt->fetch();
                        $get_secret_stmt->close();
                        $token = hash("sha512", $user.$secret, true);
                        $pubkey = openssl_get_publickey(file_get_contents("../.secrets/pubkey.pem"));
                        if($pubkey){
                            $auth_success = openssl_verify($token, $signature, $pubkey, openssl_get_md_methods()[14]);
                            if($twof_flags>>0&1){
                                $otp = TOTP::createFromSecret($twof_secret);
                                $otp_input = filter_input(INPUT_POST, "otp", FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
                                if($otp_input === null || $otp_input === false) {
                                    $auth_success &= false; 
                                }
                                else {
                                    $auth_success &= $otp->verify($otp_input);
                                }
                            }
                            if($auth_success){
                                $json_response["success"] = "true";
                                $http_response = 200;
                                $log_item["result"] = "success";
                                $email_string = '<!DOCTYPE html>
<html lang="en" style="width:100vw; margin:0; padding:0; outline:0;">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width">
<title>TInyAuth Account Alert</title>
<style>
</style>
</head>
<body>
<table width="100%">
<tr>
<td>
Congratulations! You have succeeded in breaching the dummy account and have captured the flag.<br />
<br />
</td>
</tr>
<tr>
<td>
Please post the following code into the Cemetech thread where this challenge was posted or contact me at Discord at NefariousArcher with this code: <span style="color:blue;font-size:18px;font-weight:900;">'.trim(Base32::encodeUpper(random_bytes(6)), "=").'</span><br />
<br />
</td>
</tr>
<tr style="font-style:italic;">
<td>
<hr />
The content of this email is confidential and intended for the recipient specified in message only. It is strictly forbidden to share any part of this message with any third party, without a written consent of the sender. If you received this message by mistake, please reply to this message and follow with its deletion, so that we can ensure such a mistake does not occur in the future.
</td>
</tr>
</table>
</body>
</html>
';
                            } else {
                                $http_response = 403;
                                $log_item["result"] = "fail";
                                $log_item["error"] = "Invalid credentials";
                            }
                        } else {
                            $http_response = 500;
                            $log_item["error"] = "OpenSSL error. This is a bug. Contact developers.";
                        }
                    }
                    else {
                        $http_response = 403;
                        $log_item["result"] = "fail";
                        $log_item["error"] = "Blacklisted by user config";
                    }
                    $conn->close();
                } else {
                    $http_response = 400;
                    $log_item["error"] = "Invalid token in request";
                }
            } else {
                $http_response = 400;
                $log_item["error"] = "Invalid user in request";
            }
        }
        else {
            $http_response = 400;
            $log_item["error"] = "No origin in request";
        }
    }
    else {
        $http_response = 400;
        $log_item["error"] = "Request invalid";
    }

    $json_response["error"] = $log_item["error"];
    if($log_item["origin-ip"] != ""){
        $log_ip = $log_item["origin-ip"];
    } else {
        $log_ip = $log_item["querying-ip"];
    }

    if($email_string != ""){
        $sendgrid = new SendGrid($env['SENDGRID_API_KEY']);
        $email_obj    = new SendGrid\Mail\Mail();
        $email_obj->setFrom($env["NOTIFY_EMAIL_FROM"]);
        $email_obj->setSubject("TInyAuth CTF Success Alert");
        $email_obj->addTo($email);
        $email_obj->addTo($participant_email);
        $email_obj->addContent("text/html", $email_string);
        $sendgrid->send($email_obj);
    }

    $logline = "Authentication request from ".$log_item["querying-ip"]." for ".$log_item["origin-ip"].".";
    if($log_item["error"] != ""){ $logline .= " ".$log_item["error"]."."; }
    $fp = fopen("../logs/auth.log", "a+");
    fwrite($fp, $timestamp.": ".$logline."\n");
    fclose($fp);

    $json_logdata = json_decode(file_get_contents('logs/auth.json'), true);
    $json_logdata[] = $log_item;
    file_put_contents('../logs/auth.json', json_encode($json_logdata));

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