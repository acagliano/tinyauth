<?php
    $env = parse_ini_file('.env');
    session_start();
    if(isset($_SESSION["user"])){
        include_once "scripts/keygen.php";
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | TI-84+ CE Credentials Grant and Authentication</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/template.css" />
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
        <?php
            if(isset($_SESSION["user"])){
                echo "<link rel=\"stylesheet\" href=\"styles/dashboard.css\" />";
            } else {
                echo "<link rel=\"stylesheet\" href=\"styles/login_register.css\" />";
            }
        ?>
        <style>

        </style>
        <script src="scripts/toggle_compliances.js"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    </head>
    <body>
        <?php include_once("portions/header.php"); ?>
        <div id="content">
            <?php
                if(isset($_SESSION["id"])){
                    include_once("portions/dashboard.php");
                } else {
                    include_once("portions/login_register.php");
                }
            ?>
        </div>
        <?php include_once("portions/compliance.php"); ?>
    </body>
</html>