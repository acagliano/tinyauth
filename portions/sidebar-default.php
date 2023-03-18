
<form id="login" method="post" action="">
	<p>Log in or Register to generate, download, or revoke server access tokens.</p>
	<input type="text" name="user" placeholder="Username" required /><br />
	<input type="password" name="password" placeholder="Password" required /><br />
	
	<input type="submit" name="login" value="Login" />
	<hr />
	<input type="submit" name="register" value="Register" />
	<hr />
	<?php
		echo "<span class=\"errors\">";
		foreach($errors as $e){
			echo $e;
			echo "<br />";
		}
		echo "</span>";
	?>
</form>

