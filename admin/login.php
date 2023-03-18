<?php
	if(isset($_SERVER["HTTP_REFERER"]) && ($_SERVER["HTTP_REFERER"] == $_SERVER["PHP_SELF"])){
		die("This should not be accessed in this manner!");
	}
?>

<form id="login" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>" method="post">
	<input name="user" type="text" /><br />
	<input name="passwd" type="password" /><br />
	<div class="g-recaptcha" data-sitekey="6LeKceEbAAAAAJ6hbuZp7pXQlUbI5aVb4Spfr_be"></div>
	<input type="submit" id="submit-login" name="login" value="Log In" />
</form>
