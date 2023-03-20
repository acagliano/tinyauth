
<?php

if(isset($_POST["regen_token"])){
	$conn = mysqli_connect($SQL_HOST, $SQL_USER, $SQL_PW, $SQL_DB);
	$new_token = generate_token($passwd);
	if(!$conn->connect_errno) {
		$stmt_get_user_pw = $conn->prepare('select password from cred where username = ?');
		$stmt_get_user_pw->bind_param('s', $_SESSION["username"]);
		$stmt_get_user_pw->execute();
		$response = $stmt_get_user_pw->get_result();
		if($response->num_rows){
			$row = $response->fetch_assoc();
			$new_token = generate_token($row["password"]);
			$curr_time = date("Y-m-d H:i:s");
			$stmt_update_user_token = $conn->prepare('update cred set pretoken = ?, token_creation_date = ? where username = ?');
			$stmt_update_user_token->bind_param('sss', $new_token, $curr_time, $_SESSION["username"]);
			$stmt_update_user_token->execute();
			refresh_session_data($_SESSION["username"], $conn);
		}
		else {
			$errors[] = "Error retrieving account password.";
		}
		$conn->close();
	}
	else {
		$errors[] = "MySQL connect error.";
	}
}


?>

<form id="logout" method="post" action="">
	<p>Welcome <?php echo $_SESSION["username"]; ?></p>
	<input type="submit" name="logout" value="Log Out" />
</form>
<form id="config" method="post" action="">
	<input type="text" name="email" placeholder="email address" value="<?php echo $_SESSION['email'];?>" />
	<input type="checkbox" name="en2fa" /> Enable 2FA<br />
	<input type="submit" name="save_config" value="Save Settings" />
	<hr />
	<span style="font-size:18px;">Current Token Generated:</span><br /> <?php echo $_SESSION["token_creation_date"]; ?><br />
	<input type="submit" name="regen_token" value="Refresh Token" onclick="return  confirm('This action will immediately revoke all TI-84+ CE keyfiles issued to this point. Are you sure you want to proceed?')" />
	<input type="submit" name="gen_key" value="Generate Keyfile" />
<?php
echo "<span class=\"errors\">";
foreach($errors as $e){
	echo $e;
	echo "<br />";
}
echo "</span>";
?>
	<hr />
	<input type="submit" style="background:darkred;" name="delete_account" value="Delete Account" onclick="return  confirm('This action cannot be reversed. Are you sure you want to do this?')" />
</form>

