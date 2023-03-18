<?php
require 'vendor/autoload.php';

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PragmaRX\Google2FA\Google2FA;
$google2fa = new Google2FA();


$SQL_HOST="localhost";
$SQL_USER=$_ENV['SQL_USER'];
$SQL_DB=$_ENV['SQL_DB'];
$SQL_PW=$_ENV['SQL_PASSWD'];
$errors = array();

if (isset($_SESSION["username"])) {
    include_once "scripts/user-spec.php";
}

if (isset($_POST["login"])) {
    
    // open connection to mysqli
    $conn = mysqli_connect($SQL_HOST, $SQL_USER, $SQL_PW, $SQL_DB);
    if (!$conn->connect_errno) {
        $user = $_POST["user"];
        $password = $_POST["password"];
    
        $stmt_validate_user = $conn->prepare('select password from cred where username = ?');
        $stmt_validate_user->bind_param('s', $user);
        $stmt_validate_user->execute();
        $response = $stmt_validate_user->get_result();
        if ($response->num_rows) {
            $row = $response->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                refresh_session_data($user, $conn);
            }
            else { $errors[] = "Invalid password for user."; }
        }
        else {$errors[] = "No profile for user ".$user." exists."; }
        $conn->close();
    }
    else { $errors[] = "MySQL connect error."; }
}

if (isset($_POST["register"])) {
    include_once "reg.php";
}


if (isset($_POST["register-reg"])) {
	
	$url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
		'secret' => $_ENV['GRECAPTCHA_SECRET_KEY'],
		'response' => $_POST['g-recaptcha-response'],
	]);
    
    $gre_api_call = file_get_contents(filter_var($url, FILTER_SANITIZE_URL));
    $gre_api_response = json_decode($gre_api_call);
    if ($gre_api_response->success) {
        
        $conn = mysqli_connect($SQL_HOST, $SQL_USER, $SQL_PW, $SQL_DB);
        if (!$conn->connect_errno) {
            $user = $_POST["user-reg"];
            $password = password_hash($_POST["password-reg"], PASSWORD_DEFAULT);
            $email = $_POST["email-reg"];
            $token = generate_token($password);
            
            $stmt_validate_user = $conn->prepare('select * from cred where username = ?');
            $stmt_validate_user->bind_param('s', $user);
            $stmt_validate_user->execute();
            $response = $stmt_validate_user->get_result();
            if (!$response->num_rows) {
                $g2fa_secret = $google2fa->generateSecretKey();
                $g2fa_enable = false;
                $stmt_add_user = $conn->prepare('insert into cred (username,password,email,pretoken) values (?,?,?,?)');
                $stmt_add_user->bind_param('ssss', $user, $password, $email, $token);
                $stmt_add_user->execute();
                refresh_session_data($user, $conn);
            }
            else {$errors[] = "User ".$user." exists."; 
            }
            $conn->close();
        }
        else {
            $errors[] = "MySQL connect error.";
        }
    }
    else {
        $errors[] = "Recaptcha verification failed.";
    }
}

function generate_token($passwd)
{
    return random_bytes(32);
}

function refresh_session_data($user, $conn)
{
    $stmt_update_session = $conn->prepare('select * from cred where username = ?');
    $stmt_update_session->bind_param('s', $user);
    $stmt_update_session->execute();
    $response = $stmt_update_session->get_result();
    if ($response->num_rows) {
        $row = $response->fetch_assoc();
        foreach(array_keys($row) as $k){
            $_SESSION[$k] = $row[$k];
        }
    }
}



?>
<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | An Open Authentication Service for the TI-84+ CE</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width; intial-scale=1" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
        <style>
            html,body {width:100vw; height:100vh; margin:0; padding:0; outline:0;}
            html {background:black;}
            body {}
            body {display:flex; flex-direction:row;}
            #nav {
                width:30%; height:auto; margin:0; padding:0;
                background-color:rgba(64,64,64,.4); color:white;
                display:flex; flex-direction:column;
                justify-content:flex-start;
                align-items:flex-start;
            }

            #title {
                font-family:'Share Tech Mono', monospace;
                font-size:62px;
                color: rgb(73,151,208);
                -webkit-text-stroke-color: rgba(255,255,255,.5);
                -webkit-text-stroke-width: 7px;
                margin:15% auto;
                flex:0;
            }
            #login, #logout, #config {width:70%; margin:auto; flex:0;}
#login input[type=text],#login input[type=password], #login input[type=submit] {
    -webkit-border-radius:10px; -moz-border-radius:10px; border-radius:10px;
    width:100%; height:30px;
    margin:2% 0;
            }
#logout input[type=text],#logout input[type=password], #logout input[type=submit] {
    -webkit-border-radius:10px; -moz-border-radius:10px; border-radius:10px;
    width:100%; height:30px;
    margin:2% 0;
}
#config input[type=text],#config input[type=password], #config input[type=submit] {
-webkit-border-radius:10px; -moz-border-radius:10px; border-radius:10px;
width:100%; height:30px;
margin:2% 0;
}

            #login input[type=text],#login input[type=password]{
                background:rgba(255,255,255,.25);
                border:2px solid rgba(255,255,255,.5);
                font-size:18px;
                color:rgb(188,212,230);
            }
#logout input[type=text],#logout input[type=password]{
background:rgba(255,255,255,.25);
border:2px solid rgba(255,255,255,.5);
font-size:18px;
color:rgb(188,212,230);
}
#config input[type=text],#config input[type=password]{
background:rgba(255,255,255,.25);
border:2px solid rgba(255,255,255,.5);
font-size:18px;
color:rgb(188,212,230);
}
            #login input[type=submit]{
                background:rgba(0,122,165,.9);
cursor:pointer; cursor:hand;
                border:none;
                color:white;
                font-weight:900;
                font-size:24px;
            }
#logout input[type=submit]{
background:rgba(0,122,165,.9);
cursor:pointer; cursor:hand;
border:none;
color:white;
font-weight:900;
font-size:24px;
}
#config input[type=submit]{
background:rgba(0,122,165,.9);
cursor:pointer; cursor:hand;
border:none;
color:white;
font-weight:900;
font-size:18px;
height:30px;
border-radius:5px;
}
            #login p {color:rgb(188,212,230); font-size:20px; margin:5px 0; text-align:center;}
#logout p {color:rgb(188,212,230); font-size:20px; margin:5px 0; text-align:center;}
#register p {color:rgb(188,212,230); font-size:20px; margin:5px 0; text-align:center;}

#register {z-index:100; position:absolute; top:20%; height:auto; left:35%; width:30%; border:5px solid rgba(0,122,165,.5); background:rgb(32,32,32); padding:5%; border-radius:5px;}
#register input[type=text],#register input[type=password], #register input[type=submit] {
-webkit-border-radius:10px; -moz-border-radius:10px; border-radius:10px;
width:100%; height:40px;
margin:2% 0;
}
#register input[type=text],#register input[type=password]{
background:rgba(255,255,255,.25);
border:2px solid rgba(255,255,255,.5);
font-size:18px;
color:rgb(188,212,230);
}
#register input[type=submit]{
background:rgba(0,122,165,.9);
border:none;
color:white;
font-weight:900;
font-size:22px;
height:40px;
}
.g-recaptcha{width:100%; margin:5px 0;}
input[type=submit]:hover {opacity:0.7;}

            #api {
                flex:3;
                width:100%;
            }
            #api>.dl-link {
                position:relative;
                font-size: 22px;
                width:80%;
                margin:5% auto;
                padding:5px 0;
                background:linear-gradient(to right, rgba(188,212,230,0) 0%, rgba(188,212,230,.25) 50%, rgba(188,212,230,0) 100%);
                text-align:center;
                color:rgb(188,212,230); font-weight:700;
                border-top:1px solid rgba(188,212,230,1);
                border-bottom:1px solid rgba(188,212,230,1);
            }

                
            #explainer {
                text-align:center;
                margin:5%;
                color:lightgray;
                font-size:24px;
                width:40%;
            }
        
            #main {
                width:70%;
                height:auto;
                background-image:radial-gradient(rgba(70,130,180,.3) 0%, rgba(70,130,180,0) 100%);
                font-size:22px; color:white;
                display:flex;
                flex-wrap:wrap;
                justify-content:space-around;
                flex:1;
            }
            #main>.exp-item {height:auto; min-height:40vh; flex:0 0 45%; margin:2%; border:2px solid rgba(0,122,165,.5); border-radius:10px; display:flex; flex-direction:column;}
            #main>.exp-item>.title {font-family:'Share Tech Mono', monospace; width:100%; background:rgba(0,122,165,.4); font-weight:bold; padding:10px 0; text-align:center; flex:0;}

            #main>.red {border:2px solid rgba(255,0,0,.5);}
            #main>.red>.title {background:rgba(255,0,0,.4);}
            #main>.exp-item>p {flex:1; margin:10px; display:flex; align-items:center;}
            #main p {margin:10px;}
            #banner {position:absolute; bottom:0; left:0; background:darkred; color:white; width:100%; padding:5px 0;}
            .note, .errors {color:red; font-weight:bold;}
        </style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

        <script data-ad-client="ca-pub-6268292462233201" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    </head>
    <body>
        <div id="nav">
            <div id="title">TIny<br />Auth</div>
            <?php
            if(!isset($_SESSION["username"])) {
                include_once "portions/sidebar-default.php";
            }
            else {
                include_once "portions/sidebar-user.php";
            }
            ?>
            <br />
            <p style="width:100%; text-align:center; color:rgba(255,255,255,.8);">
                <span style="font-weight:bold; font-size:120%;">Legal Info</span><br />
                &copy; Anthony Cagliano, 2023-<?php echo htmlspecialchars(date("Y")); ?><br />
                <a href="portions/privacy.php" style="color:inherit;">Privacy Policy</a>
            </p>
        </div>
        <div id="main">
<?php
if(!isset($_SESSION["username"])) {
    include_once "portions/content-default.php";
}
else {
    include_once "portions/content-user.php";
}
?>
        </div>
    </body>
</html>
