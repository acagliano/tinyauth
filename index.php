<?php
session_start();
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base64;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;
require $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";

use tivars\TIModel;
use tivars\TIVarFile;
use tivars\TIVarType;
use tivars\TIVarTypes;
require_once($_SERVER["DOCUMENT_ROOT"]."/ti_vars_lib/src/autoloader.php");

$env = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/.env");


include_once $_SERVER["DOCUMENT_ROOT"]."/scripts/send-email.php";

if(isset($_POST["logout"])){
    unset($_SESSION);
    session_destroy();
}

if(isset($_POST["generate-keyfile"])){
    $kf_errors = array();
    $privkey_file = $_SERVER["DOCUMENT_ROOT"]."/.secrets/privkey.pem";
    $privkey = openssl_get_privatekey(file_get_contents($privkey_file), $env["SSL_PASS"]);
    if($privkey){
        openssl_sign($_SESSION["id"].$_SESSION["secret_keygen"], $signature, $privkey, openssl_get_md_methods()[14]);
        $asn1_userid = new Integer($_SESSION["id"]);
        $asn1_usertoken = new OctetString(bin2hex($signature));
        $asn1_keydata = new Sequence($asn1_userid, $asn1_usertoken);
        $asn1_raw = $asn1_keydata->getBinary();
        $tfile = "/tmp/". uniqid(rand(), true);
        $binfile = $tfile.".bin";
        $appvfile = $tfile.".8xv";
        $fname = "TInyKF";
        $f = fopen($binfile, 'wb+');
        if($f){
            fwrite($f, "\x54\x49\x41\x55\x54\x48".$asn1_raw);
            fclose($f);
        } else {
            $kf_errors[] = "Binary IO error.";
            exit();
        }
        $output = shell_exec("../convbin/bin/convbin -i $binfile -j bin -o $appvfile -k 8xv -n $fname 2>&1");
        error_log($output);
        if(file_exists($appvfile)){
            ob_clean();
            header_remove();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$fname.'.8xv"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($appvfile));
            ob_clean();
            flush();
            echo file_get_contents($appvfile);
            unlink($binfile);
            unlink($appvfile);
            exit();
        } else {
            $kf_errors[] = "Error creating download.";
        }
    } else {
        $kf_errors[] = "OpenSSL: Error opening signing key.";
    }
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
            <p>TInyAuth is an application providing a secure means for public-facing services for the TI-84+ CE to authorize users without requiring that users authenticate repeatedly or input credentials. Users authorize their calculators to access their TInyAuth account by downloading a keyfile from their Dashboard which contains a signed access token for their account and sending it to their device. The rest is managed via a secure API that the end user doesn&apos;t need to worry about.</p><br />
            <h3>Secure Keyfiles</h3>
            <p>Keyfiles are digitally signed by TInyAuth to prevent forgery. The Service&apos;s signing key renews on the 1st of every year, expiring any keys issued the previous year. Users may issue multiple keys against their account if they desire and may optionally supply an encryption passphrase for additional security.</p><br />
            <h3>OAuth2 Backend</h3>
            <p>Why reinvent the wheel, right? When complete, TInyAuth will use the OAuth2 framework to manage authorization requests from third parties.</p><br />
        </div>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/compliance.php"); ?>
        <div id="navigation">
                <div class="navitem"><a href="/portions/api.php">Documentation</a></div>
                <div class="navitem"><a href="/portions/legal.php">Legal Notices</a></div>
                <div class="navitem"><a href="/portions/legal.php">Support</a></div>
                <div class="navitem" onclick="show_compliances()">Compliance</div>
                <div id="copyright">&copy; 2023 - <?php echo date("Y");?> Cags Tech Designs</div>
        </div>
    </body>
</html>