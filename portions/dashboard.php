<?php
   
    $then = new DateTime($_SESSION["secret_creation_ts"]);
    $now = new DateTime(date("Y-m-d H:i:s"));
    $secret_time_elapsed = $then->diff( $now );

    if(isset($_POST["logout"])){
        session_destroy();
        unset($_SESSION);
        header("Refresh:0");
    }
?>
<div id="dashboard">
    <div id="details">
        <h3 id="welcome-msg">Welcome, <?php echo $_SESSION["user"]; ?>!
            <form id="logout" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
                <input type="submit" name="logout" value="Log Out" />
            </form>
        </h3>
        <p>This page is your account management panel. You can change your email or password, reset your account secret, generate new keyfiles for your devices, monitor authentication attempts using your keyfiles, and even delete your account.</p>
        <form id="update" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
            <h4>Update Account Information</h4>
            New Email:<br />
            <input type="text" name="n_email" /><br />
            New Password:<br />
            <input type="password" name="n_password" /><br />
            Verify Current Password:<br />
            <input type="password" name="n_verify" required /><br /><br />
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
            Last Secret Refresh: <span style="background:rgba(0,0,0,.5);font-weight:bold;padding:5px 10px;color:<?php
            if($secret_time_elapsed->m < 6){
                echo "green";
            }
            elseif($secret_time_elapsed->m < 12){
                echo "orange";
            }
            else { echo "red"; }
            ?>"><?php echo $_SESSION["secret_creation_ts"]; ?></span><span id="secret-ts-hover" style="position:relative; border:1px solid red; margin:0 5px; cursor:pointer; cursor:hand;">&#10067;<span id="secret-ts-exp">The coloring of the timestamp indicates the lifespan/security grading of the active secret, including the age of the secret and the number of keyfiles issued under it.<br /><span style="color:green;">Green indicates that your account secret can still safely be used.</span><br /><span style="color:orange;">Orange indicates that your account secret is aging and should be refreshed soon.</span><br /><span style="color:red;">Red indicates that your account secret has been in use longer than is recommended.</span></span></span>
            <br />
            Keyfile Encryption Passphrase (omit for no encryption):<br />
            <input type="password" name="kf_passphrase" placeholder="passphrase" /><br />
            On Device Name:<br />
            <input type="text" name="kf_name" placeholder="AppVar Name" required /><br />
            <input type="submit" name="kf_emit" value="Generate Keyfile" /><br />
            <?php
                foreach($kf_errors as $error){
                    echo $error."<br />";
                }
            ?>
        </form>
    </div>
</div>