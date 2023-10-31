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
if(isset($_POST["login"])){
        $l_errors = array();
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT * FROM credentials WHERE email=?");
            $check_user_stmt->bind_param("s", $email);
            $check_user_stmt->execute();
            $existing_result = $check_user_stmt->get_result();
            $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)==0){
                echo "<script>confirm('Account not found for the given information. Press OK to create it or Cancel to try again.');</script>";
            }
            else {
                if(password_verify($_POST["password"], $row["password"])){
                    load_user($conn, $row["id"]);
                    header("Refresh:0");
                }
                else {
                $l_errors[] = "Invalid password.";
                }
            }
            
        } else {$l_errors[] = "Database connection failed.";}
    }

    function load_user($conn, $id){
        $load_user_stmt = $conn->prepare("SELECT * FROM credentials WHERE id=?");
        $load_user_stmt->bind_param("s", $id);
        $load_user_stmt->execute();
        $result = $load_user_stmt->get_result();
        $row = $result->fetch_assoc();
        foreach($row as $key=>$value){
            $_SESSION[$key] = $value;
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
            h3 {color:goldenrod; font-weight:bold; font-size:120%; margin-bottom:5px;}
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
            <br /><br />
            <p>Have you ever been playing a game on your calculator and thought to yourself &quot;If only I had a secure way to authenticate myself online with this thing&quot;? No? That&apos;s a shame, because that&apos;s what TInyAuth does.</p>
            <h3>Oauth2 Backend</h3>
            <p>When completed, TInyAuth will implement an Oauth2.0 backend. Users will download a credentialing keyfile from their dashboard which lets their calculator authenticate with TInyAuth on request from a third party application.</p>
        </div>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/compliance.php"); ?>
    </body>
</html>