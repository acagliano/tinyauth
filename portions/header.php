<div id="header">
        <!--<div id="ta-icon"><img id="ta-img" src="/ta-logo.png" /></div>-->
        <div id="title">TInyAuth</div>
        <div id="infoline">Key-Based Authentication<br />for the TI-84+ CE</div>
        <?php
        use OTPHP\TOTP;
        if(isset($_SESSION["email"])){
                if (isset($_SESSION["time"])){
                         echo "<form id=\"totp-form\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                        echo "<p style=\"margin:0 auto; margin-top:20px;\">2FA Required!</p>";
                        echo "<input maxlength=\"6\" style=\"width:60%\" type=\"text\" name=\"otp\" placeholder=\"OTP\" />";
                        echo "<input style=\"width:60%\" type=\"submit\" name=\"submit-otp\" value=\"Submit OTP\" />";
                        echo "<p style=\"margin:0 auto; margin-top:10px;\">Don&apos;t have TOTP set up?</p>";
                        echo "<input style=\"width:60%\" type=\"submit\" name=\"email-otp\" value=\"Email It Instead\" /><br />";
                        foreach($otp_errors as $l){
                                echo $l."<br />";
                        }
                        echo "</form>";
                }
                else {
                        echo "<div style=\"width:80%; margin:auto; text-align:left;\">";
                        echo "<form id=\"logout\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                        echo "<input type=\"email\" value=\"".$_SESSION["email"]."\" readonly />";
                        echo "<input type=\"submit\" name=\"logout\" value=\"Logout\" />";
                        echo "</form>";
                        echo "<form id=\"newpass\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                        echo "<p>Edit your account details:</p>";
                        echo "<input type=\"password\" name=\"newpassword\" placeholder=\"New Password\" autocomplete =\"new-password\" required />";
                        echo "<input type=\"submit\" name=\"update-password\" value=\"Update\" />";
                        echo "</form>";
                        echo "<form id=\"getkey\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                        echo "<p>Generate access tokens for your calculator:</p>";
                        echo "<input type=\"password\" name=\"newpassword\" placeholder=\"Keyfile Passphrase\" autocomplete =\"new-password\" />";
                        echo "<input type=\"submit\" name=\"generate-keyfile\" value=\"Generate Keyfile\" />";
                        echo "</form>";
                        echo "<form id=\"danger\">";
                        echo "<input type=\"submit\" name=\"renew-keygen-secret\" value=\"Reset Keygen Secret\" /><br />";
                        echo "<input type=\"submit\" name=\"renew-2fa-secret\" value=\"Reset 2FA Secret\" /><br />";
                        echo "<input type=\"submit\" name=\"delete-account\" value=\"Delete Account\" />";
                        echo "</form>";
                        if(isset($_SESSION["otp-qr"])){
                                $otp = TOTP::createFromSecret($_SESSION["secret_2fa"]);
                                echo "<hr />";
                                echo "<p>Use this QR code to configure TOTP-Based 2FA:<br />
                                <span style=\"font-style:italic; font-size:85%;\">(compatible with Google2FA, FreeOTP, and others)</span></p>";
                                echo "<img src='{$_SESSION["otp-qr"]}' />";
                        }
                        echo "</div>";
                }
        }
        else {
                echo "<form id=\"login\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                echo "<p>Login to access Account Dashboard</p>";
                echo "<input type=\"email\" name=\"email\" placeholder=\"Email\" required />";
                echo "<input type=\"password\" name=\"password\" placeholder=\"Password\" required />";
                echo "<input type=\"submit\" name=\"login\" value=\"Login\" />";
                echo "<input type=\"submit\" name=\"register\" value=\"Register\" /><br />";
                foreach($l_errors as $l){
                        echo $l."<br />";
                }
                foreach($r_errors as $l){
                        echo $l."<br />";
                }
                echo "</form>";
        }
        ?>
        <div style="flex:2;"></div>
        <div id="navigation">
                <div class="navitem"><a href="/portions/api.php">Documentation</a></div>
                <div class="navitem"><a href="/portions/legal.php">Legal Notices</a></div>
                <div class="navitem" onclick="show_compliances()">Compliance</div>
        </div>
</div>