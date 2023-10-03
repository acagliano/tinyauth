<?php
    require $_SERVER["TINYAUTH_ROOT"].'/vendor/autoload.php';
    require $_SERVER["TINYAUTH_ROOT"].'/scripts/send_email.php';
    $env = parse_ini_file($_SERVER["TINYAUTH_ROOT"]."/.env");

    $conn = new mysqli('localhost', $env["SQL_USER"], $env["SQL_PASS"], $env["SQL_DB"]);
    $then = new DateTime($_SESSION["secret_creation_ts"]);
    $now = new DateTime(date("Y-m-d H:i:s"));
    $secret_time_elapsed = $then->diff( $now );

    if(isset($_POST["logout"])){
        unset($_SESSION);
        session_destroy();
        echo "<meta http-equiv='refresh' content='0'>";
    }

    if(isset($_POST["update_info"])){
        $n_errors = array();

        if(password_verify($_POST["n_verify"], $_SESSION["password"])){
            if(isset($_POST["n_email"]) && $_POST["n_email"] != ""){
                $email = filter_var($_POST["n_email"], FILTER_VALIDATE_EMAIL);
                if($email){
                    $email_check_stmt = $conn->prepare("SELECT email FROM credentials WHERE email=?");
                    $email_check_stmt->bind_param("s", $email);
                    $email_check_stmt->execute();
                    $existing_result = $email_check_stmt->get_result();
                    $rows = $existing_result->fetch_all(MYSQLI_ASSOC);
                    if(count($rows)==0){
                        $email_update_stmt = $conn->prepare("UPDATE credentials SET email=? WHERE id=?");
                        $email_update_stmt->bind_param("si", $email, $_SESSION["id"]);
                        $status = $email_update_stmt->execute();
                        if ($status === false) {
                            $n_errors[] = "Error updating email address.";
                        } else {
                            $n_errors[] = "Email successfully updated!";
                        }
                    }
                    else { $n_errors[] = "Email address in use."; }
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
        load_user($conn, $_SESSION["id"]);
    }

    if(isset($_POST["refresh_secret"])){
        $api_secret = random_bytes(32);
        $timestamp = date("Y-m-d H:i:s");
        $secret_update_stmt = $conn->prepare("UPDATE credentials SET api_secret=?,secret_creation_ts=? WHERE id=?");
        $secret_update_stmt->bind_param("ssi", $api_secret, $timestamp, $_SESSION["id"]);
        $status = $secret_update_stmt->execute();
        if ($status === false) {
            echo "<script>alert(\"Error writing new keyfile secret. If this issue persists, please contact system administrator.\");</script>";
        } else {
            load_user($conn, $_SESSION["id"]);
            send_email($_SESSION["email"], "TInyAuth Account Secret Renewal Notice", file_get_contents($_SERVER["TINYAUTH_ROOT"]."/emails/secret-update-msg.dat"), true);
        }
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
    }

    if(isset($_POST["purge_sel"])){
        $jsondata = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"].'/logs/auth.json'), true);
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
        file_put_contents($_SERVER["DOCUMENT_ROOT"].'/logs/auth.json', json_encode($jsondata));
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
        load_user($conn, $_SESSION["id"]);
    }

    $jsondata = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"].'/logs/auth.json'), true);
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
        <form id="logout" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <p style="width:100%; text-align:center; margin:0; margin-bottom:4%; padding:10px 0; background:rgba(0,0,0,.75);">WELCOME TINYAUTH USER!&emsp;&emsp;<input type="submit" name="logout" value="Log Out" /></p>
            <p style="width:96%; margin:auto; text-align:left; width:100%; border-bottom:1px solid white;">DOWNLOADS</p>
            <table id="downloads">
                <col width="65%" />
                <col width="35%" />
                <tr><td>Calc-Auth Keyfile</td><td><input class="dl" type="submit" name="generate-calc-auth" value="generate" /></td></tr>
            </table>
            <?php
            foreach($kf_errors as $e){
                echo $e."<br />";
            }
            ?>
            <p style="margin:0 2%; color:goldenrod; font-weight:bold; font-size:90%;">Your keyfile(s) faciliate authentication with TInyAuth.<br />Do not share them with others.</p>
            <br />
        </form>
         <form id="update_info" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
          <p style="width:96%; margin:auto; text-align:left; width:100%; border-bottom:1px solid white;">UPDATE INFO</p>
        <table id="updateinfo">
            <col width="100%" />
            <tr>
                <td>
                    <input type="text" name="n_email" value="<?php echo $_SESSION['email'];?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="password" name="n_password" placeholder="new password" autocomplete="new-password" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="password" name="n_verify" placeholder="current password" autocomplete="new-password" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="submit" value="Update Account" name="update_info" />
                </td>
            </tr>
        </table>
        </form>
        </form>
        <br />
        <form id="danger" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
             <p style="width:96%; margin:auto; text-align:left; width:100%; color: rgba(255, 0, 0, .8); border-bottom:1px solid rgba(255, 0, 0, .8);">DANGER ZONE</p>
            <table id="dangerzone">
                <tr style="height:15px;"></tr>
                <tr>
                    <td>
                        <input type="submit" value="Revoke Calc-Auth Keys" name="refresh_secret" onclick="return confirm('This will revoke any Calc-Auth Keyfiles issued under the current secret. They will need to be regenerated. Are you sure?');" />
                    </td>
                </tr>
                <tr style="height:15px;"></tr>
                <tr>
                    <td>
                        <input type="submit" value="Delete Account" name="delete_account" onclick="return confirm('This will permanently delete your account and revoke all keys. Are you sure?');" />
                    </td>
                </tr>
            </table>
        </form>
        <br />
    </div>
    <div id="more-details">
        <form id="auth-log" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
        <details open>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Authentication Attempts</span>
                <span style="float:right;">
                    <input type="submit" name="block_sel" value="Block Selected" onclick="return confirm('This will prevent the selected host(s)\' attempts to authenticate with your keys. Are you sure?');" />&emsp;
                    <input type="submit" name="unblock_sel" value="Unblock Selected" onclick="return confirm('This will allow the selected host(s)\' attempts to authenticate with your keys. Are you sure?');" />&emsp;
                    <input type="submit" name="purge_sel" value="Clear Selected" onclick="return confirm('This will remove all logs of the selected host(s)\' attempts to authenticate with your keys. This action is not reversible. Are you sure?');" />&emsp;
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
        <details open>
            <summary><span style="font-weight:900; font-size:18px;">&emsp;Notification Settings</span>
            <input type="submit" name="notify-opts" value="Update" style="float:right; margin-right:5%;" />
            </summary>
                &emsp;&emsp;<input type="checkbox" name="notify-key-renew" value="true" disabled="disabled" checked />&emsp;&emsp;server signing-key renewal<br />
                &emsp;&emsp;<input type="checkbox" name="notify-secret-update" value="true" <?php if($_SESSION["notify_flags"]>>1&1){echo "checked";}?> />&emsp;&emsp;account secret update<br />
                &emsp;&emsp;<input type="checkbox" name="notify-auth-fail" value="true" <?php if($_SESSION["notify_flags"]>>2&1){echo "checked";}?> />&emsp;&emsp;failed authentication attempts<br />
                &emsp;&emsp;<input type="checkbox" name="notify-auth-success" value="true" <?php if($_SESSION["notify_flags"]>>3&1){echo "checked";}?> />&emsp;&emsp;successful authentication attempts<br />
        </details></form>
     </div>
</div>