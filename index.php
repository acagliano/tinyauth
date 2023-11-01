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
                    $_SESSION["otp"] = TOTP::createFromSecret($row["secret_2fa"]);
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
                $_SESSION["otp"] = TOTP::generate();
                $_SESSION["otp"]->setPeriod(120);
                $_SESSION["otp"]->setLabel($email);
                $_SESSION["otp"]->setIssuer('TInyAuth');
                $_SESSION["email"] = $email;
                $_SESSION["time"] = time();
                $_SESSION["mode"] = "register";
                $_SESSION["password"] = password_hash($_POST["password"], PASSWORD_DEFAULT);
                $email_content = '<table width="100%;"><col width="100%" /><td>Welcome to TInyAuth! We are glad you have decided to use this Service!<br /></td><td>You will need to validate your email address before you can complete sign-in. Please use the code below to complete two-factor authentication.<br /></td><td style="color:darkblue; font-size:150%;">'.$_SESSION["otp"]->now().'<br /></td><td>You will need two-factor authentication to access this service in the future as well. You may continue to use your email or you may configure a TOTP application using the information in your dashboard.</td></table>';
                send_email($email, "Welcome to TInyAuth!", $email_content, $isHTML=true);
            }
        }
        else {$r_errors[] = "Database connection failed.";}
    }


if(isset($_POST["submit-otp"])){
    $otp_code = filter_input(INPUT_POST, "otp", FILTER_SANITIZE_STRING);
    if($_SESSION["otp"]->verify($otp_code)){
        if($_SESSION["mode"] == "register"){
            // initialize user information
            $insert_user_stmt = $conn->prepare("INSERT INTO credentials (email,password,secret_keygen,secret_2fa,secret_creation_ts,administrator,notify_flags) VALUES (?,?,?,?,?,?,?)");
            $secret_keygen = random_bytes(32);
            $secret_otp = $SESSION["otp"]->getSecret();
            $admin = false;
            $notify = 0;
            $insert_user_stmt->bind_param("ssssiii", $_SESSION["email"], $_SESSION["password"], $secret_keygen, $secret_otp, $_SESSION["time"], $admin, $notify);
            $insert_user_stmt->execute();
        }
    }
    load_user($_SESSION["email"]);
    unset($_SESSION["time"]);
}

    function load_user($email){
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error){
            $load_user_stmt = $conn->prepare("SELECT * FROM credentials WHERE email=?");
            $load_user_stmt->bind_param("s", $email);
            $load_user_stmt->execute();
            $result = $load_user_stmt->get_result();
            $row = $result->fetch_assoc();
            foreach($row as $key=>$value){
                $_SESSION[$key] = $value;
            }
            $conn->close();
        }

    }
    if(isset($_SESSION["id"])){
        include_once $_SERVER["DOCUMENT_ROOT"]."/scripts/generate-keyfile.php";
        //$style_file = "dashboard.css";
        $content_file = "user.php";
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
            <p>Have you ever been playing a game on your calculator and thought to yourself &quot;If only I had a secure way to authenticate myself online with this thing&quot;? No? That&apos;s a shame, because that&apos;s what TInyAuth does.</p><br />
            <h3>Secure Keyfiles</h3>
            <p>Credentialing keyfiles are digitally-signed by TInyAuth to prevent forgery. Keys are valid for 1 year. Users may issue multiple keys against their account secret if needed.</p><br />
            <h3>Oauth2 Backend</h3>
            <p>When completed, TInyAuth will implement an Oauth2.0 backend. Users will download a credentialing keyfile from their dashboard which lets their calculator authenticate with TInyAuth on request from a third party application.</p><br />
        </div>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/compliance.php"); ?>
    </body>
</html>