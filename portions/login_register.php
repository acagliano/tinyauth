<?php

    use OTPHP\TOTP;
    use ParagonIE\ConstantTime\Base64;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    require $_SERVER["DOCUMENT_ROOT"]."/vendor/autoload.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/scripts/send-email.php";
    $env = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/.env");

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
            $passwd = password_hash($_POST["r_password"], PASSWORD_DEFAULT);
            $email = filter_input(INPUT_POST, "r_email", FILTER_VALIDATE_EMAIL);
            $api_secret = random_bytes(32);
            $timestamp = date("Y-m-d H:i:s");
            $admin = false;
            $flags_default = 0;
            $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
            if (!$conn->connect_error) {
                $check_user_stmt = $conn->prepare("SELECT user FROM credentials WHERE email=?");
                $check_user_stmt->bind_param("s", $email);
                $check_user_stmt->execute();
                $existing_result = $check_user_stmt->get_result();
                $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
                if(count($rows)==0){
                    $register_user_stmt = $conn->prepare("INSERT INTO credentials (`email`, `password`, `api_secret`, `secret_creation_ts`, `administrator`, `notify_flags`) VALUES (?, ?, ?, ?, ?, ?)");
                    $register_user_stmt->bind_param("ssssii", $email, $passwd, $api_secret, $timestamp, $admin, $flags_default);
                    if($register_user_stmt->execute()){
                        $r_errors[] = "User successfully registered";
                        load_user($conn, $newuser);
                        send_email($email, "Welcome to TInyAuth", "../emails/welcome-msg.dat", true);
                        echo "<meta http-equiv='refresh' content='0'>";
                    } else {
                        $r_errors[] = "Error writing to database.";
                    }
                } else {
                    $r_errors[] = "Account already exists for this email address.";
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
        $user = filter_input(INPUT_POST, "l_username", FILTER_SANITIZE_EMAIL);
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT id,password FROM credentials WHERE email=?");
            $check_user_stmt->bind_param("s", $user);
            $check_user_stmt->execute();
            $result = $check_user_stmt->get_result();
            $row = $result->fetch_assoc();
            if(password_verify($_POST["l_password"], $row["password"])){
                load_user($conn, $row["id"]);
                header("Refresh:0");
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
            $email_obj->setFrom($env["NOTIFY_EMAIL_FROM"]);
            $email_obj->setSubject("TInyAuth - Account Recovery Requested");
            $email_obj->addTo($email);
            $email_obj->addContent("text/html", '<table><tr><td><span style=\"font-weight:bold;\">A password reset has been requested for the TInyAuth account linked to this email. If this was not you, it is advised that you verify that your email account is secure.</span><br /><br /></td></tr><tr><td>Recover your account by pasting the following link into your browser&apos;s address bar: https://tinyauth.cagstech.com/scripts/reset_password.php?email='.$email.'<br />Your Email Validation Code is: <span style="font-weight:bold; font-size:18px; color:blue;">'.$email_otp.'</span><br /><br /></td></tr><tr><td>This link will remain valid for 5 minutes. If you have configured 2FA for password recovery on your account, a OTP provided by your TOTP client will be required.</td></tr></table>');
            $sendgrid->send($email_obj);
        } else {
            $pwr_errors[] = "No account found for provided email.";
        }
    }
?>
</div>
</div>
