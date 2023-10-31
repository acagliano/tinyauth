<?php
if(isset($_POST["l_submit"])){
        $l_errors = array();
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
            } else {$l_errors[] = "Database connection failed.";}
        } else {$l_errors[] = "Recaptcha validation error."; }
    }

?>