
<?php
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

    $env = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/.env");
    session_start();
    $style_file = "login_register.css";
    $content_file = "login_register.php";
    if(isset($_SESSION["id"])){
        include_once $_SERVER["DOCUMENT_ROOT"]."/scripts/generate-keyfile.php";
        $style_file = "dashboard.css";
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
            #content {display:flex;
                flex-direction:row; justify-content:space-around;}

            #content-exp {
                width:45%; height:100%; display:flex; flex-direction:column; justify-content:flex-start; overflow:auto;
            }
            #content-demo-container {display:flex; align-items:center; justify-content:center;}
            #content-exp>div{
                background:rgba(0,0,0,.15);
                font-size:calc(8px + 0.5vw);
                padding:5px;
                color:white;
                margin:4% auto;
            }
            #content div .title{color:goldenrod; font-weight:bold; font-size:120%; margin-bottom:5px;}
            #content>div>p {margin:0 10px;}
            #content #content-demo {width:100%; height:auto!important; display:block; margin:auto;}
            @media only screen and (max-width: 600px) {
                #content {display:block;}
                #content-exp {display:block; width:100%;}
                #content-exp>div {position:static; width:100%; margin:1% 0;}
                #content-demo-container {display:none;}
            }
        </style>
        <script src="/scripts/toggle_compliances.js"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    </head>
    <body>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/header.php"); ?>
        <div id="content">
            This is some content
        </div>
        <?php include_once($_SERVER["DOCUMENT_ROOT"]."/portions/compliance.php"); ?>
    </body>
</html>