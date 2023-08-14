<?php
require 'vendor/autoload.php';
use OTPHP\TOTP;
   $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
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
        $jsondata = json_decode(file_get_contents('logs/auth.json'), true);
        foreach($jsondata as $key=>$item){
            if($item["origin-ip"] != ""){
                $listing_ip = $item["origin-ip"];
            } else { $listing_ip = $item["querying-ip"]; }
            foreach($_POST["iplist"] as $ip){
                if($listing_ip == $ip){
                   unset($jsondata[$key]);
                   break;
                }
            }
        }
        file_put_contents('logs/auth.json', json_encode($jsondata));
    }

    if(isset($_POST["block_sel"])){
        foreach($_POST["iplist"] as $ip){
            $add_to_blacklist_stmt = $conn->prepare("select * from greylist where ip=? and assoc_id=?");
            $add_to_blacklist_stmt->bind_param("si", $ip, $_SESSION["id"]);
            $add_to_blacklist_stmt->execute();
            $existing_result = $add_to_blacklist_stmt->get_result();
            $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)==0){
                $add_to_blacklist_stmt = $conn->prepare("insert into greylist (`ip`, `assoc_id`) values (?, ?)");
                $add_to_blacklist_stmt->bind_param("si", $ip, $_SESSION["id"]);
                $add_to_blacklist_stmt->execute();
            }
        }
    }

    if(isset($_POST["unblock_sel"])){
        foreach($_POST["iplist"] as $ip){
            $add_to_blacklist_stmt = $conn->prepare("delete from greylist where ip=? and assoc_id=?");
            $add_to_blacklist_stmt->bind_param("si", $ip, $_SESSION["id"]);
            $add_to_blacklist_stmt->execute();
        }
    }

    if(isset($_POST["notify-opts"])){
        $notify_val = 1;
        if($_POST["notify-secret-update"] == "true"){
            $notify_val |= (1<<1);
        }
        if($_POST["notify-auth-fail"] == "true"){
            $notify_val |= (1<<2);
        }
        if($_POST["notify-auth-success"] == "true"){
            $notify_val |= (1<<3);
        }
        $update_notify_stmt = $conn->prepare("UPDATE credentials SET notify_flags=? WHERE id=?");
        $update_notify_stmt->bind_param("ii", $notify_val, $_SESSION["id"]);
        $update_notify_stmt->execute();
        load_user($conn, $_SESSION["user"]);
    }

    if(isset($_POST["2fa-opts"])){
        $notify_val = 0;
        if($_POST["2fa-keyfile-login"] == "true"){
            $notify_val |= (1<<0);
        }
        if($_POST["2fa-dashboard-login"] == "true"){
            $notify_val |= (1<<1);
        }
        if($_POST["2fa-password-reset"] == "true"){
            $notify_val |= (1<<2);
        }
        if($_POST["2fa-account-unlock"] == "true"){
            $notify_val |= (1<<3);
        }
        $update_notify_stmt = $conn->prepare("UPDATE credentials SET 2fa_flags=? WHERE id=?");
        $update_notify_stmt->bind_param("ii", $notify_val, $_SESSION["id"]);
        $update_notify_stmt->execute();
        load_user($conn, $_SESSION["user"]);
    }

    if(isset($_POST["goto_admin"]) && $_SESSION["administrator"] == true){
        header("Location:portions/admin.php");
    }

    $jsondata = json_decode(file_get_contents('logs/auth.json'), true);
    $hitsarray = array();
    foreach($jsondata as $json){
        if($json["user-id"] == $_SESSION["id"]){
            if($json["origin-ip"] != ""){
                $ip = $json["origin-ip"];
            } else {
                $ip = $json["querying-ip"];
            }
            $hitsarray[$ip] = array(
                "hits"=>0,
                "success"=>0,
                "fail"=>0,
                "blocked"=>"NO"
            );
        }
    }
    foreach($jsondata as $json){
        if($json["user-id"] == $_SESSION["id"]){
            if($json["origin-ip"] != ""){
                $ip = $json["origin-ip"];
            } else {
                $ip = $json["querying-ip"];
            }
            $hitsarray[$ip]["hits"]+=1;
            if($json["result"] == "success"){
                $hitsarray[$ip]["success"] += 1;
            } else {
                $hitsarray[$ip]["fail"] += 1;
            }
            $check_greylist_stmt = $conn->prepare("select * from greylist where ip=? and assoc_id=?");
            $check_greylist_stmt->bind_param("si", $json["ip"], $_SESSION["id"]);
            $status = $check_greylist_stmt->execute();
            $result = $check_greylist_stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            if(count($rows)==0){
                $hitsarray[$ip]["blocked"] = "NO";
            }
            else {
                $hitsarray[$ip]["blocked"] = "YES";
            }
        }
    }
    $conn->close();
?>
<div id="dashboard">
    <div id="details">
        <h3 id="welcome-msg">Welcome, <?php echo $_SESSION["user"]; ?>!
            <form style="font-size:16px;" id="logout" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
                <input type="submit" name="logout" value="Log Out" />
            </form>
        </h3>
        <p>Welcome to your Dashboard.<br />
        Here you can change your account info, manage account secrets, generate keyfiles for your devices, track key usage analytics, and even delete your account.</p>
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
            <input type="submit" value="Regenerate Keyfile Secret" name="refresh_token" onclick="confirm('This will revoke all keyfiles issued under this secret. Are you sure?');" /><br /><br />
            <input type="submit" value="Regenerate 2FA Secret" name="refresh_token" onclick="confirm('You will need to reconfigure your TOTP client application. Are you sure?');" /><br /><br />
            <input type="submit" value="Delete Account" name="delete_account" onclick="confirm('This will permanently delete your account and revoke all keys. Are you sure?');" />
        </form>
        <br />
    </div>
    <div id="more-details">
        <form id="issue-key" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <details id="keyfile-issue" open>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Issue Keyfile</span></summary>
            <p>You can issue pretty much as many keyfiles as you want under an account secret. Those keys will remain valid until either the account secret or the server signing key is changed.</p>
            <p>Last Secret Refresh: <span style="background:rgba(0,0,0,1);font-weight:bold;padding:5px 10px;color:<?php
            if($secret_time_elapsed->m < 5){
                echo "green";
            }
            elseif($secret_time_elapsed->m < 6){
                echo "orange";
            }
            else { echo "red"; }
            ?>"><?php echo $_SESSION["secret_creation_ts"]; ?></span><span id="secret-ts-hover" style="position:relative; border:1px solid red; margin:0 5px; cursor:pointer; cursor:hand;">&#10067;<span id="secret-ts-exp">Color approximates secret lifespan elapsed.<br /><span style="color:green;">Green indicates significant lifespan remaining.</span><br /><span style="color:orange;">Orange indicates secret aging.</span><br /><span style="color:red;">Red indicates secret should be expired.</span></span></span>
            </p>
            <p style="margin:10px 5px;"><input type="password" name="kf_passphrase" placeholder="passphrase (optional)" autocomplete="new-password" />&emsp;
            <input type="text" name="kf_name" placeholder="AppVar Name" maxlength="8" required />&emsp;
            <input type="submit" name="kf_emit" value="Generate Keyfile" /></p>
            <?php
                if(isset($kf_errors)){
                    foreach($kf_errors as $error){
                        echo $error."<br />";
                    }
                }
            ?>
        </details>
        </form>
        <form id="auth-log" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <details open>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Authentication Attempts</span>
                <span style="float:right;">
                    <input type="submit" name="block_sel" value="Block Selected" onclick="confirm('This will prevent the selected host(s)\' attempts to authenticate with your keys. Are you sure?');" />&emsp;
                    <input type="submit" name="unblock_sel" value="Unblock Selected" onclick="confirm('This will allow the selected host(s)\' attempts to authenticate with your keys. Are you sure?');" />&emsp;
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
                    <th title="attempts">&#128272;</th>
                    <th title="success">&#x2705;</th>
                    <th title="fail">&#x274c;</th>
                    <th title="is blacklisted?">BLOCK</th>
                    <th></th>
                </tr>
                <?php
                    foreach($hitsarray as $ip=>$data){
                        echo "<tr>";
                        echo "<td>".gethostbyaddr($ip)."</td>";
                        echo "<td class=\"center\">".$data["hits"]."</td>";
                        echo "<td class=\"center\">".$data["success"]."</td>";
                        echo "<td class=\"center\">".$data["fail"]."</td>";
                        echo "<td class=\"center\">".$data["blocked"]."</td>";
                        echo "<td><input type='checkbox' name='iplist[]' value='$ip' /></td>";
                    }
                ?>
            </table>
        </details></form>
        <form id="notify-opts" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post" style="font-size:14px;">
        <details>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Notification Settings</span>
            <input type="submit" name="notify-opts" value="Update" style="float:right; margin-right:5%;" />
            </summary>
                &emsp;&emsp;<input type="checkbox" name="notify-key-renew" value="true" disabled="disabled" checked />&emsp;&emsp;server signing-key renewal<br />
                &emsp;&emsp;<input type="checkbox" name="notify-secret-update" value="true" <?php if($_SESSION["notify_flags"]>>1&1){echo "checked";}?> />&emsp;&emsp;account secret update<br />
                &emsp;&emsp;<input type="checkbox" name="notify-auth-fail" value="true" <?php if($_SESSION["notify_flags"]>>2&1){echo "checked";}?> />&emsp;&emsp;failed authentication attempts<br />
                &emsp;&emsp;<input type="checkbox" name="notify-auth-success" value="true" <?php if($_SESSION["notify_flags"]>>3&1){echo "checked";}?> />&emsp;&emsp;successful authentication attempts<br />
        </details></form>
        <form id="2fa-opts" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post" style="font-size:14px;">
        <details>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;2FA Settings</span>
            <input type="submit" name="2fa-opts" value="Update" style="float:right; margin-right:5%;" />
            </summary>
                    <?php
                        $otp = TOTP::createFromSecret($_SESSION["2fa_secret"]);
                        $otp->setLabel($_SESSION["user"]."@TInyAuth");
                        $otp->setIssuer("TInyAuth");
                        $goqr_me = $otp->getQrCodeUri(
                            'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&qzone=2&margin=0&size=300x300&ecc=M', '[DATA]');
                        echo "<img id='qr-2fa' src='{$goqr_me}' alt='QR Code'>";
                    ?>
                &emsp;&emsp;<span style="font-weight:bold; font-size:105%;">Enable Two-Factor Authentication For:</span><br />
                &emsp;&emsp;<input type="checkbox" name="2fa-keyfile-login" value="true" <?php if($_SESSION["2fa_flags"]>>0&1){echo "checked";}?> />&emsp;&emsp;keyfile authentication<br />
                &emsp;&emsp;<input type="checkbox" name="2fa-dashboard-login" value="true" <?php if($_SESSION["2fa_flags"]>>1&1){echo "checked";}?> />&emsp;&emsp;dashboard login<br />
                &emsp;&emsp;<input type="checkbox" name="2fa-password-reset" value="true" <?php if($_SESSION["2fa_flags"]>>2&1){echo "checked";}?> />&emsp;&emsp;password reset<br />
                &emsp;&emsp;<input type="checkbox" name="2fa-account-unlock" value="true" <?php if($_SESSION["2fa_flags"]>>3&1){echo "checked";}?> />&emsp;&emsp;account unlock<br />
                <hr />
                &emsp;&emsp;Scan the QR Code to the right to add to your TOTP application of choice.<br />
                &emsp;&emsp;Alternatively, you can manually enter the secret below.<br />
                &emsp;&emsp;<span style="color:red; font-weight:bold;">Do not share your secret or QR code with others.</span><br />
                &emsp;&emsp;SECRET: <span style="font-family:monospace; font-size:90%;"><?php echo $_SESSION["2fa_secret"]; ?></span>
        </details></form>
    </div>
</div>