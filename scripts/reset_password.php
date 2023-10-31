<?php

require '../vendor/autoload.php';
use OTPHP\TOTP;
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    $env = parse_ini_file('../.env');
    session_start();
    $email = filter_input(INPUT_GET, "email", FILTER_VALIDATE_EMAIL);

    if(isset($_POST["submit_reset_pass"])){
        $reset_errors = array();
        $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
        if($email == $_SESSION["pass-reset-session"]["email"]){
            if($_POST["rotp"] == $_SESSION["pass-reset-session"]["rotp"]){
                $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
                $check_user_stmt = $conn->prepare("SELECT id,2fa_flags,2fa_secret FROM credentials WHERE email=?");
                $check_user_stmt->bind_param("s", $email);
                $check_user_stmt->execute();
                $existing_result = $check_user_stmt->get_result();
                $row = $existing_result->fetch_assoc();
                if($row["2fa_flags"]>>2&1){
                    $otp = TOTP::createFromSecret($row["2fa_secret"]);
                    if($otp->verify($_POST["totp"])){
                        password_reset($conn, $row["id"], $_POST["newpass"]);
                        $reset_errors[] = "Password reset successfully!";
                    }
                    else {
                        $reset_errors[] = "TOTP code invalid.";
                    }
                }
                else {
                    password_reset($conn, $row["id"], $_POST["newpass"]);
                    $reset_errors[] = "Password reset successfully!";
                }
                $conn->close();
            }
            else {
                $reset_errors[] = "ROTP code invalid, not provided, or request expired.";
            }
        }
        else {
            $reset_errors[] = "Email field invalid, not provided, or request expired.";
        }
    }

    function password_reset($conn, $id, $passwd){
        $hpass = password_hash($passwd, PASSWORD_DEFAULT);
        $change_pass_stmt = $conn->prepare("UPDATE credentials set password=? WHERE id=?");
        $change_pass_stmt->bind_param("ss", $hpass, $id);
        $change_pass_stmt->execute();
        session_destroy();
        unset($_SESSION);
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | Password Reset Form</title>
    </head>
    <body>
<form action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post" style="width:50%">
    <h2>TInyAuth Password Reset Form</h2>
    <p>This form requires a ROTP (random one-time passcode) generated and included with the email you should have received when you provided your email for password reset. Your ability to provide this ROTP verifies your ownership of this account. The ROTP is valid for 5 minutes.<br /><br />In addition, if you have Two-Factor Authentication for enabled for password recovery, you will need to provide the TOTP (Time-Based One Time Passcode) from your configured TOTP client application.</p>
    <table width="100%">
        <col width="60%" />
        <col width="40%" />
        <tr>
            <td>Email Address:</td>
            <td><input type="text" name="email" placeholder="Email" value="<?php echo $email; ?>" autocomplete="new-password" readonly /></td>
        </tr>
        <tr>
            <td>Recovery Code from Email:</td>
            <td><input type="text" name="rotp" placeholder="ROTP" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td>One-Time Passcode from TOTP Application:<br />(omit if disabled in account 2FA settings)</td>
            <td><input type="text" name="totp" maxlength="6" placeholder="TOTP" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td>New Account Password:</td>
            <td><input type="password" name="newpass" placeholder="New Password" autocomplete="new-password" /></td>
        </tr>
        <tr>
            <td></td><td><input type="submit" name="submit_reset_pass" value="Submit Request" /></td>
        </tr>
    </table>
     <?php
            foreach($reset_errors as $error){
                echo $error."<br />";
            }
        ?>
</form>
</body>
</html>