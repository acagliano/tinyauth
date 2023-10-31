<div id="header">
        <!--<div id="ta-icon"><img id="ta-img" src="/ta-logo.png" /></div>-->
        <div id="title">TInyAuth</div>
        <div id="infoline">Key-Based Authentication<br />for the TI-84+ CE</div>
        <?php
        if(isset($_SESSION["email"])){
                echo "<form id=\"logout\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                echo "<input type=\"submit\" name=\"logout\" value=\"Log Out\" />";
                if (isset($_SESSION["time"])){
                        echo "<p>This service requires two-factor authentication!</p>";
                        echo "<input type=\"text\" name=\"otp\" placeholder=\"OTP\" required />";
                }
                else {
                        echo "Logged In";
                }
                echo "</form>";
        }
        else {
                echo "<form id=\"login\" action=\"".filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL)."\" method=\"post\">";
                echo "<p>Login to access Account Dashboard</p>";
                echo "<input type=\"email\" name=\"email\" placeholder=\"Email\" required />";
                echo "<input type=\"password\" name=\"password\" placeholder=\"Password\" required />";
                echo "<input type=\"submit\" name=\"login\" value=\"Login\" />";
                echo "<input type=\"submit\" name=\"login\" value=\"Register\" />";
                foreach($l_errors as $l){
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
        <div style="flex:1;"></div>
</div>