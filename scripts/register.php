 
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
    ?>