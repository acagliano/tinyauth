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

    $env = parse_ini_file("../.env");
    session_start();
    $style_file = "login_register.css";
    $content_file = "login_register.php";
    if(isset($_SESSION["id"])){
        include_once "../scripts/generate-keyfile.php";
        $style_file = "dashboard.css";
        $content_file = "dashboard.php";
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | TI-84+ CE Credentials Grant and Authentication</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="../styles/template.css" />
        <link rel="stylesheet" href="../styles/<?php echo $style_file;?>" />
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
        <style>

        </style>
        <script src="scripts/toggle_compliances.js"></script>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    </head>
    <body>
        <?php include_once "portions/header.php"; ?>
        <div id="content">
            <?php include_once "portions/".$content_file; ?>
        </div>
        <?php include_once "portions/compliance.php"; ?>
    </body>
</html>