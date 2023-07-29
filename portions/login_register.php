<?php
    if(isset($_POST["r_submit"])){
        $r_errors = array();
        $fields = [
            'secret' => $env["RECAPTCHA_SECRET"],
            'response' => $_POST["g-recaptcha-response"]
        ];
        $postdata = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        $recaptcha_response = json_decode(curl_exec($ch), true);
        if($recaptcha_response["success"]){
            $newuser = $_POST["r_username"];
            $passwd = password_hash($_POST["r_password"], PASSWORD_DEFAULT);
            $email = $_POST["r_email"];
            $secret = random_bytes(64);
            $timestamp = date("Y-m-d H:i:s");
            $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
            if (!$conn->connect_error) {
                $check_user_stmt = $conn->prepare("SELECT user FROM credentials WHERE user=?");
                $check_user_stmt->bind_param("s", $newuser);
                $check_user_stmt->execute();
                $existing_result = $check_user_stmt->get_result();
                $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
                if(count($rows)==0){
                    $register_user_stmt = $conn->prepare("INSERT INTO credentials (`user`, `password`, `email`, `secret`, `secret_creation_ts`) VALUES (?, ?, ?, ?, ?)");
                    $register_user_stmt->bind_param("sssss", $newuser, $passwd, $email, $secret, $timestamp);
                    if($register_user_stmt->execute()){
                        $r_errors[] = "User successfully registered";
                        load_user($conn, $newuser);
                        header("Refresh:0");
                    } else {
                        $r_errors[] = "Error writing to database.";
                    }
                } else {
                    $r_errors[] = "User already exists.";
                }
                $conn->close();
            }
            else {
                $r_errors[] = "Database connection failed.";
            }
        } else {
            $r_errors[] = "Recaptcha validation error.";
        }
    }
    if(isset($_POST["l_submit"])){
        $l_errors = array();
        $user = $_POST["l_username"];
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT password FROM credentials WHERE user=?");
            $check_user_stmt->bind_param("s", $user);
            $check_user_stmt->execute();
            $result = $check_user_stmt->get_result();
            $row = $result->fetch_assoc();
            if(password_verify($_POST["l_password"], $row["password"])){
                load_user($conn, $user);
                header("Refresh:0");
            }
            else {
                $l_errors[] = "Invalid password.";
            }
        } else {
            $l_errors[] = "Database connection failed.";
        }
    }

    function load_user($conn, $user){
        $load_user_stmt = $conn->prepare("SELECT * FROM credentials WHERE user=?");
        $load_user_stmt->bind_param("s", $user);
        $load_user_stmt->execute();
        $result = $load_user_stmt->get_result();
        $row = $result->fetch_assoc();
        foreach($row as $key=>$value){
            $_SESSION[$key] = $value;
        }
    }
?>

<div id="forms">

    <form id="form-register" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <p>New to TInyAuth? Register here to get started!</p>
        <input type="text" name="r_username" required />
        <input type="password" name="r_password" required />
        <input type="email" name="r_email" required />
        <div class="g-recaptcha" data-sitekey="6Le-9pAkAAAAAMIK25zYXLP9-f2ade_c3Zg_XeNz"></div>
        <input type="submit" value="Register" name="r_submit" />
        <?php
            foreach($r_errors as $error){
                echo $error."<br />";
            }
        ?>
    </form>

     <form id="form-login" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <p>Already have an account? Sign in here to access your dashboard!</p>
        <input type="text" name="l_username" required />
        <input type="password" name="l_password" required />
        <input type="submit" value="Login" name="l_submit" />
        <?php
            foreach($l_errors as $error){
                echo $error."<br />";
            }
        ?>
    </form>
</div>