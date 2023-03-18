<?php

?>
<form id="register" action="" method="post">
	<p>Register here to use the TInyAuth service.</p>
	<input type="text" name="user-reg" placeholder="Username" required /><br />
	<input type="password" name="password-reg" placeholder="Password" required /><br />
	<input type="text" name="email-reg" placeholder="Email" required /><br /><br />
	<div class="g-recaptcha" data-sitekey="<?php echo $_ENV['GRECAPTCHA_SITE_KEY']; ?>"></div><br />
	<input type="submit" name="register-reg" value="Register" />
</form>
