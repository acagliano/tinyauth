<div id="header">
        <!--<div id="ta-icon"><img id="ta-img" src="/ta-logo.png" /></div>-->
        <div id="title">TInyAuth</div>
        <div id="infoline">Key-Based Authentication<br />for the TI-84+ CE</div>
        <form id="login" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF", FILTER_SANITIZE_URL); ?>" method="post">
                <p>Login to access Account Dashboard</p>
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <input type="submit" name="login" value="Login" />
                <input type="submit" name="login" value="Register" />
                <?php
                foreach($l_errors as $l){
                        echo $l."<br />";
                }
                ?>
        </form>
        <div style="flex:3;"></div>
        <div id="navigation">
                <div class="navitem"><a href="/portions/api.php">Documentation</a></div>
                <div class="navitem"><a href="/portions/legal.php">Legal Notices</a></div>
                <div class="navitem" onclick="show_compliances()">Compliance</div>
        </div>
</div>