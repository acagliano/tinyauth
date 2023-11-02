<?php
session_start();
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base64;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
$env = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/.env");
require $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";
include_once $_SERVER["DOCUMENT_ROOT"]."/scripts/send-email.php";

if(isset($_POST["logout"])){
    unset($_SESSION);
    session_destroy();
}

if(isset($_POST["login"])){
        $l_errors = array();
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT id,email,password,secret_2fa FROM credentials WHERE email=?");
            $check_user_stmt->bind_param("s", $email);
            $check_user_stmt->execute();
            $existing_result = $check_user_stmt->get_result();
            $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)){
                $row = $rows[0];
                if(password_verify($_POST["password"], $row["password"])){
                    $_SESSION["email"] = $row["email"];
                    $_SESSION["time"] = time();
                    $_SESSION["mode"] = "login";
                    $otp = TOTP::createFromSecret($row["secret_2fa"]);
                    $otp->setPeriod(120);
                    $otp->setLabel($email);
                    $otp->setIssuer('TInyAuth');
                    $_SESSION["otp"] = $otp->getSecret();
                }
                else {
                    $l_errors[] = "Invalid password.";
                }
            }
            else { $l_errors[] = "Account not found."; }
            $conn->close();
        } else {$l_errors[] = "Database connection failed.";}
    }

    if(isset($_POST["register"])){
        $r_errors = array();
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT id FROM credentials WHERE email=?");
            $check_user_stmt->bind_param("s", $email);
            $check_user_stmt->execute();
            $existing_result = $check_user_stmt->get_result();
            $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)){
                $r_errors[] = "An account already exists for this email.";
            }
            else {
                $otp = TOTP::generate();
                $otp->setPeriod(120);
                $otp->setLabel($email);
                $otp->setIssuer('TInyAuth');
                $_SESSION["otp"] = $otp->getSecret();
                $_SESSION["email"] = $email;
                $_SESSION["time"] = time();
                $_SESSION["mode"] = "register";
                $_SESSION["password"] = password_hash($_POST["password"], PASSWORD_DEFAULT);
                $email_content = '<table width="100%;"><col width="100%" /><tr><td>Welcome to TInyAuth! We are glad you have decided to use this Service!<br /><br /></td></tr><tr><td>You will need to validate your email address before you can complete sign-in. Please use the code below to complete two-factor authentication.<br /><br /></td></tr><tr><td style="color:darkblue; font-size:150%;">'.$otp->now().'<br /><br /></td></tr><tr><td>You will need two-factor authentication to access this service in the future as well. You may continue to use your email or you may configure a TOTP application using the QR code visible after completing sign-in.</td></tr></table>';
                send_email($email, "Welcome to TInyAuth!", $email_content, $isHTML=true);
            }
            $conn->close();
        }
        else {$r_errors[] = "Database connection failed.";}
    }


if(isset($_POST["submit-otp"])){
    $otp_errors = array();
    $otp_code = filter_input(INPUT_POST, "otp", FILTER_SANITIZE_STRING);
    $otp = TOTP::createFromSecret($_SESSION["otp"]);
    $otp->setPeriod(120);
    $otp->setLabel($_SESSION["email"]);
    $otp->setIssuer('TInyAuth');
    $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
    if (!$conn->connect_error) {
        if($otp->verify($otp_code)){
            if($_SESSION["mode"] == "register"){
                // initialize user information
                $insert_user_stmt = $conn->prepare("INSERT INTO credentials (email,password,secret_keygen,secret_2fa,secret_creation_ts,administrator,notify_flags) VALUES (?,?,?,?,?,?,?)");
                $secret_keygen = random_bytes(32);
                $admin = false;
                $notify = 0;
                $timestamp = date("Y-m-d H:i:s");
                $insert_user_stmt->bind_param("sssssii", $_SESSION["email"], $_SESSION["password"], $secret_keygen, $_SESSION["otp"], $timestamp, $admin, $notify);
                $insert_user_stmt->execute();
            }
            load_user($_SESSION["email"], $conn);
            $_SESSION["otp-qr"] = $otp->getQrCodeUri(
                'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=100x100&ecc=M',
                '[DATA]'
            );
            unset($_SESSION["time"]);
        }
        else { $otp_errors[] = "OTP invalid!"; }
        $conn->close();
    }
    else {$otp_errors[] = "Error connecting to database!"; }
}

if(isset($_POST["email-otp"])){
    $otp = TOTP::createFromSecret($_SESSION["otp"]);
    $otp->setPeriod(120);
    $otp->setLabel($_SESSION["email"]);
    $otp->setIssuer('TInyAuth');
    $email_content = '<table width="100%;"><col width="100%" /><tr><td></tr><tr><td>You will need to validate your email address before you can complete sign-in. Please use the code below to complete two-factor authentication.<br /><br /></td></tr><tr><td style="color:darkblue; font-size:150%;">'.$otp->now().'<br /><br /></td></tr></table>';
    send_email($_SESSION["email"], "TInyAuth 2FA Code Requested", $email_content, $isHTML=true);
}

    function load_user($email, $conn){
        $load_user_stmt = $conn->prepare("SELECT * FROM credentials WHERE email=?");
        $load_user_stmt->bind_param("s", $email);
        $load_user_stmt->execute();
        $result = $load_user_stmt->get_result();
        $row = $result->fetch_assoc();
        foreach($row as $key=>$value){
            $_SESSION[$key] = $value;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | TI-84+ CE Credentials Grant and Authentication</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="/styles/template.css" />
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
        <style>
            h3 {color:goldenrod; font-weight:bold; font-size:120%; margin-bottom:5px; border-bottom:1px solid goldenrod;}
            #content #content-demo {width:100%; height:auto!important; display:block; margin:auto;}
            @media only screen and (max-width: 600px) {
                #content {display:block;}
                #content-exp {display:block; width:100%;}
                #content-exp>div {position:static; width:100%; margin:1% 0;}
                #content-demo-container {display:none;}
            }
        </style>
        <script src="/scripts/toggle_compliances.js"></script>
    </head>
    <body>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/header.php"); ?>
        <div id="content">
            <br />
            <p>TInyAuth is an application providing a secure means for public-facing services for the TI-84+ CE to authorize users without requiring that users authenticate repeatedly. If this sounds a lot like OAuth, that&apos;s because that&apos;s what it is.</p><br />
            <h3>Secure Keyfiles</h3>
            <p>Credentialing keyfiles are digitally-signed by TInyAuth to prevent forgery. Keys are valid for 1 year. Users may issue multiple keys against their account secret if needed.</p><br />
            <h3>Oauth2 Backend</h3>
            <p>When completed, TInyAuth will implement an Oauth2.0 backend. Users will download a keyfile from their dashboard which lets their calculator authenticate with TInyAuth on request from a third party application. Upon successful login to TInyAuth, a token will be provided to the third-party application granting access to specified account information.</p><br />
        </div>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/compliance.php"); ?>
    </body>
</html>