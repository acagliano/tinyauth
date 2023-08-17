<?php
    require 'vendor/autoload.php';
    use Sendgrid\Mail\Mail;
    use OTPHP\TOTP;
    use ParagonIE\ConstantTime\Base32;
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
            $newuser = filter_input(INPUT_POST, "r_username", FILTER_SANITIZE_STRING);
            $passwd = password_hash($_POST["r_password"], PASSWORD_DEFAULT);
            $email = filter_input(INPUT_POST, "r_email", FILTER_SANITIZE_EMAIL);
            $secret = random_bytes(64);
            $timestamp = date("Y-m-d H:i:s");
            $admin = false;
            $flags_default = 0;
            $secret_2fa="";
            $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
            if (!$conn->connect_error) {
                $check_user_stmt = $conn->prepare("SELECT user FROM credentials WHERE user=?");
                $check_user_stmt->bind_param("s", $newuser);
                $check_user_stmt->execute();
                $existing_result = $check_user_stmt->get_result();
                $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
                if(count($rows)==0){
                    $register_user_stmt = $conn->prepare("INSERT INTO credentials (`user`, `password`, `email`, `secret`, `secret_creation_ts`, `administrator`, `notify_flags`, `2fa_flags`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $register_user_stmt->bind_param("sssssiii", $newuser, $passwd, $email, $secret, $timestamp, $admin, $flags_default, $flags_default);
                    if($register_user_stmt->execute()){
                        $r_errors[] = "User successfully registered";
                        load_user($conn, $newuser);
                        $sendgrid = new SendGrid($env['SENDGRID_API_KEY']);
                        $email_obj    = new SendGrid\Mail\Mail();
                        $email_obj->setFrom("admin@cagstech.com");
                        $email_obj->setSubject("Welcome to TInyAuth");
                        $email_obj->addTo($email);
                        $email_obj->addContent("text/html", file_get_contents("emails/welcome-msg.dat"));
                        $sendgrid->send($email_obj);
                        echo "<meta http-equiv='refresh' content='0'>";
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
            $check_user_stmt = $conn->prepare("SELECT password,2fa_flags,2fa_secret FROM credentials WHERE user=?");
            $check_user_stmt->bind_param("s", $user);
            $check_user_stmt->execute();
            $result = $check_user_stmt->get_result();
            $row = $result->fetch_assoc();
            if(password_verify($_POST["l_password"], $row["password"])){
                if($row["2fa_flags"]>>1&1){
                    if($_POST["l_otp"] != ""){
                        $otp = TOTP::createFromSecret($row["2fa_secret"]);
                        if($otp->verify($_POST["l_otp"])){
                            load_user($conn, $user);
                            header("Refresh:0");
                        }
                        else $l_errors[] = "OTP invalid.";
                    }
                    else $l_errors[] = "OTP for dashboard enabled but not provided.";
                }
                else {
                    load_user($conn, $user);
                    header("Refresh:0");
                }
            }
            else {
                $l_errors[] = "Invalid password.";
            }
        } else {
            $l_errors[] = "Database connection failed.";
        }
    }

    if(isset($_POST["pwr_email_otp"])){
        $pwr_errors = array();
        $email = filter_input(INPUT_POST, "pwr_email", FILTER_SANITIZE_EMAIL);
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        $check_email_stmt = $conn->prepare("SELECT email FROM credentials WHERE email=?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $existing_result = $check_email_stmt->get_result();
        $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
        if(count($rows)){
            $email_otp = trim(Base32::encodeUpper(random_bytes(8)), "=");
            $_SESSION["pass-reset-session"] = array(
                "rotp"=>$email_otp,
                "email"=>$email
            );
            session_set_cookie_params(300,"/");
            $sendgrid = new SendGrid($env['SENDGRID_API_KEY']);
            $email_obj    = new SendGrid\Mail\Mail();
            $email_obj->setFrom("admin@cagstech.com");
            $email_obj->setSubject("TInyAuth - Account Recovery Requested");
            $email_obj->addTo($email);
            $email_obj->addContent("text/html", '<table><tr><td><span style=\"font-weight:bold;\">A password reset has been requested for the TInyAuth account linked to this email. If this was not you, it is advised that you verify that your email account is secure.</span><br /><br /></td></tr><tr><td>Recover your account by pasting the following link into your browser&apos;s address bar: https://tinyauth.cagstech.com/scripts/reset_password.php?email='.$email.'<br />Your Email Validation Code is: <span style="font-weight:bold; font-size:18px; color:blue;">'.$email_otp.'</span><br /><br /></td></tr><tr><td>This link will remain valid for 5 minutes. If you have configured 2FA for password recovery on your account, a OTP provided by your TOTP client will be required.</td></tr></table>');
            $sendgrid->send($email_obj);
        } else {
            $pwr_errors[] = "No account found for provided email.";
        }
    }
?>

<div id="forms">

    <form id="form-register" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <p>New to TInyAuth? Register here to get started!</p>
        <input type="text" name="r_username" required />
        <input type="password" name="r_password" required />
        <input type="email" name="r_email" required />
        <div class="g-recaptcha" data-sitekey="<?php echo $env['RECAPTCHA_SITEKEY'];?>"></div>
        <input type="submit" value="Register" name="r_submit" />
        <br />
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
        <input type="text" name="l_otp" placeholder="OTP (if enabled)" />
        <input type="submit" value="Login" name="l_submit" />
        <br />
        <?php
            foreach($l_errors as $error){
                echo $error."<br />";
            }
        ?>
        <hr />
        <p>Forgot your password? Don&apos;t worry.<br /><a style="text-decoration:underline; cursor:pointer; cursor:hand;" onclick="document.getElementById('reset-pass').style.display='block';">Reset Password</a></p>
    </form>

    <div id="reset-pass">
    <div id="reset-pass-close" onclick="document.getElementById('reset-pass').style.display='none';">X</div>
    <form action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <br />
        <input type="text" name="pwr_email" placeholder="Account Email Address" required />
        <input type="submit" name="pwr_email_otp" value="Send Account Recovery Link" />
        <br />
    </form>
</div>
</div>