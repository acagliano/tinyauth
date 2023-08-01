<?php
   
    $then = new DateTime($_SESSION["secret_creation_ts"]);
    $now = new DateTime(date("Y-m-d H:i:s"));
    $secret_time_elapsed = $then->diff( $now );

    if(isset($_POST["logout"])){
        session_destroy();
        unset($_SESSION);
        header("Refresh:0");
    }

    if(isset($_POST["update_info"])){
        $n_errors = array();
        if(password_verify($_POST["n_verify"], $_SESSION["password"])){
            $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
            if(isset($_POST["n_email"]) && $_POST["n_email"] != ""){
                $email = filter_var($_POST["n_email"], FILTER_VALIDATE_EMAIL);
                if($email){
                    $email_update_stmt = $conn->prepare("UPDATE credentials SET email=? WHERE id=?");
                    $email_update_stmt->bind_param("si", $email, $_SESSION["id"]);
                    $status = $email_update_stmt->execute();
                    if ($status === false) {
                        $n_errors[] = "Error updating email address.";
                    } else {
                        $n_errors[] = "Email successfully updated!";
                    }
                } else {
                    $n_errors[] = "Input email invalid.";
                }

            }
            if(isset($_POST["n_password"]) && $_POST["n_password"] != ""){
                $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
                $passwd = password_hash($_POST["n_password"], PASSWORD_DEFAULT);
                $passwd_update_stmt = $conn->prepare("UPDATE credentials SET password=? WHERE id=?");
                $passwd_update_stmt->bind_param("si", $passwd, $_SESSION["id"]);
                $status = $passwd_update_stmt->execute();
                if ($status === false) {
                    $n_errors[] = "Error updating email address.";
                } else {
                    $n_errors[] = "Email successfully updated!";
                }
            }
        } else {
            $n_errors[] = "Invalid password provided.";
        }
        load_user($conn, $_SESSION["user"]);
        $conn->close();
    }

    if(isset($_POST["refresh_token"])){
        $secret = random_bytes(64);
        $timestamp = date("Y-m-d H:i:s");
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        $secret_update_stmt = $conn->prepare("UPDATE credentials SET secret=?,secret_creation_ts=? WHERE id=?");
        $secret_update_stmt->bind_param("ssi", $secret, $timestamp, $_SESSION["id"]);
        $status = $secret_update_stmt->execute();
        if ($status === false) {
            echo "<script>alert(\"Error writing new secret. If this issue persists, please contact system administrator.\");</script>";
        } else {
            load_user($conn, $_SESSION["user"]);
        }
        $conn->close();
    }
    if(isset($_POST["delete_account"])){
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        $delete_stmt = $conn->prepare("DELETE FROM credentials WHERE id=?");
        $delete_stmt->bind_param("i", $_SESSION["id"]);
        $status = $delete_stmt->execute();
        if ($status===false) {
            echo "<script>alert(\"Error deleting account. If this issue persists, please contact system administrator.\");</script>";
        } else {
            session_destroy();
            $_SESSION["user"] = "";
            header("Refresh:0");
        }
        $conn->close();
    }

    if(isset($_POST["purge_sel"])){
        $jsondata = json_decode(file_get_contents('logs/auth.log'), true);
        foreach($jsondata as $key=>$item){
            foreach($_POST["iplist"] as $ip){
                if($item["ip"] == $ip){
                   unset($jsondata[$key]);
                   break;
                }
            }
        }
        var_dump($jsondata);
        file_put_contents('logs/auth.log', json_encode($jsondata));
    }

    if(isset($_POST["graylist_sel"])){
        $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
        if (!$conn->connect_error) {
            $check_user_stmt = $conn->prepare("SELECT user FROM credentials WHERE user=?");
            $check_user_stmt->bind_param("s", $newuser);
            $check_user_stmt->execute();
            $existing_result = $check_user_stmt->get_result();
            $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)==0){
                $register_user_stmt = $conn->prepare("INSERT INTO credentials (`user`, `password`, `email`, `secret`, `secret_creation_ts`) VALUES (?, ?, ?, ?, ?)");
            }
        }
    }
    if(isset($_POST["goto_admin"]) && $_SESSION["administrator"] == true){
        header("Location:portions/admin.php");
    }

    $jsondata = json_decode(file_get_contents('logs/auth.log'), true);
    $hitsarray = array();
    foreach($jsondata as $json){
        if($json["user-id"] == $_SESSION["id"]){
            $hitsarray[$json["ip"]]["hits"]+=1;
            if($json["result"] == "success"){
                $hitsarray[$json["ip"]]["success"] += 1;
            } elseif($json["result"] == "fail"){
                $hitsarray[$json["ip"]]["fail"] += 1;
            } else {
                $hitsarray[$json["ip"]]["error"] += 1;
            }
        }
    }
?>
<div id="dashboard">
    <div id="details">
        <h3 id="welcome-msg">Welcome, <?php echo $_SESSION["user"]; ?>!
            <form style="font-size:16px;" id="logout" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
                <input type="submit" name="logout" value="Log Out" />
                <?php
                    if($_SESSION["administrator"] == true){
                        echo "<input type=\"submit\" name=\"goto_admin\" value=\"Admin View\" />";
                    }
                ?>
            </form>
        </h3>
        <p>This page is your account management panel. You can change your email or password, reset your account secret, generate new keyfiles for your devices, monitor authentication attempts using your keyfiles, and even delete your account.</p>
        <form id="update" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <h4>Update Account Information</h4>
            New Email:<br />
            <input type="text" name="n_email" value="<?php echo $_SESSION['email'];?>" /><br />
            New Password:<br />
            <input type="password" name="n_password" placeholder="new password" autocomplete="new-password" /><br />
            Verify Current Password:<br />
            <input type="password" name="n_verify" placeholder="current password" autocomplete="new-password" required /><br />
             <?php
                if(isset($n_errors)){
                    foreach($n_errors as $error){
                        echo $error."<br />";
                    }
                }
            ?><br />
            <input type="submit" value="Update Account" name="update_info" />
        </form>
        <br />
        <form id="danger" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <h4>Danger Zone</h4>
            <input type="submit" value="Refresh Account Secret" name="refresh_token" onclick="confirm('This will revoke all keyfiles issued under this secret. Are you sure?');" /><br /><br />
            <input type="submit" value="Delete Account" name="delete_account" onclick="confirm('This will permanently delete your account and revoke all keys. Are you sure?');" />
        </form>
        <br />
    </div>
    <div id="more-details">
        <form id="issue-key" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <h4>Issue Keyfile</h4>
            <p>You can issue pretty much as many keyfiles as you want under an account secret. Those keys will remain valid until either the account secret or the server signing key is changed.</p>
            Last Secret Refresh: <span style="background:rgba(0,0,0,1);font-weight:bold;padding:5px 10px;color:<?php
            if($secret_time_elapsed->m < 5){
                echo "green";
            }
            elseif($secret_time_elapsed->m < 6){
                echo "orange";
            }
            else { echo "red"; }
            ?>"><?php echo $_SESSION["secret_creation_ts"]; ?></span><span id="secret-ts-hover" style="position:relative; border:1px solid red; margin:0 5px; cursor:pointer; cursor:hand;">&#10067;<span id="secret-ts-exp">The coloring of the timestamp indicates the lifespan/security grading of the active secret, including the age of the secret and the number of keyfiles issued under it.<br /><span style="color:green;">Green indicates that your account secret can still safely be used.</span><br /><span style="color:orange;">Orange indicates that your account secret is aging and should be refreshed soon.</span><br /><span style="color:red;">Red indicates that your account secret has been in use longer than is recommended.</span></span></span>
            <br /><br />
            <input type="password" name="kf_passphrase" placeholder="passphrase (optional)" autocomplete="new-password" />&emsp;
            <input type="text" name="kf_name" placeholder="AppVar Name" maxlength="8" required />&emsp;
            <input type="submit" name="kf_emit" value="Generate Keyfile" /><br />
            <?php
                if(isset($kf_errors)){
                    foreach($kf_errors as $error){
                        echo $error."<br />";
                    }
                }
            ?>
        </form><br />
        <form id="auth-log" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <details open>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Authentication Attempts</span>
                <span style="float:right;">
                    <input type="submit" name="purge_sel" value="Clear Selected" onclick="confirm('This will remove all logs of the selected host(s)\' attempts to authenticate with your keys. This action is not reversible. Are you sure?');" />&emsp;
                </span>
            </summary>
            <table width="100%">
                <col width="50%" />
                <col width="10%" />
                <col width="10%" />
                <col width="10%" />
                <col width="10%" />
                <col width="10%" />
                <tr>
                    <th>IP/Hostname</th>
                    <th>&#128272;</th>
                    <th>&#x2705;</th>
                    <th>&#x274c;</th>
                    <th>&#x2757;</th>
                    <th>Select</th>
                </tr>
                <?php
                    foreach($hitsarray as $ip=>$data){
                        echo "<tr>";
                        echo "<td>".gethostbyaddr($ip)."</td>";
                        echo "<td class=\"center\">".$data["hits"]."</td>";
                        echo "<td class=\"center\">".$data["success"]."</td>";
                        echo "<td class=\"center\">".$data["fail"]."</td>";
                        echo "<td class=\"center\">".$data["error"]."</td>";
                        echo "<td><input type='checkbox' name='iplist[]' value='$ip' /></td>";
                    }
                ?>
            </table>
        </details></form>
        <form id="security-opts" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post" style="font-size:14px;">
        <details open>
            <summary ><span style="font-weight:900; font-size:18px;">&emsp;Rate Limiting Policy</span>
                <span style="float:right;">
                    <input type="submit" name="update_security_opts" value="Save Changes" />
                </span>
            </summary>
            <p style="margin:5px 10px;">TInyAuth implements a server-wide policy for rate-limiting keyfile authentication attempts. A single host can run up to <span style="font-weight:bold;">50 authentication attempts within 1 minute</span>; anything in excess of this rate will be rejected until the rate falls below the threshold. This serves to protect users against brute force attacks against their keys and to protect the Service against request floods that might affect performance for legitimate users.</p>
            <hr />
        </details></form>
    </div>
</div>