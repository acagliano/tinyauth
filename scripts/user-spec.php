<?php

if(isset($_POST["gen_key"])){
	
	if($privkey = openssl_get_privatekey(file_get_contents("tools/privkey.ec.pem"), $_ENV["PRIVKEY_PASSPHRASE"])){
	
		$token = hash_pbkdf2("sha512", $_SESSION["password"], $_SESSION["pretoken"], 1000, 64, true);
		$success = openssl_sign($_SESSION["username"].$token, $signature, $privkey, openssl_get_md_methods()[14]);
		openssl_free_key($privkey);
		if($success){
			$pubkey = openssl_get_publickey(file_get_contents("tools/pubkey.ec.pem"));
			$success = openssl_verify($_SESSION["username"].$token, $signature, $pubkey, openssl_get_md_methods()[14]);
			openssl_free_key($pubkey);
			if($success==1){
				$d_out = "TInyAuthKF".$_SESSION["username"]."\0".$signature;
				$binname = tempnam("/tmp", "kfbin_");
				$tifname = tempnam("/tmp", "kfti_");
				error_log("File Names: ".$binname.", ".$tifname);
				$tf = fopen($binname, "wb");
				fwrite($tf, $d_out);
				fclose($tf);
				$cmd = "tools/convbin -i ".$binname." -j bin -o ".$tifname." -k 8xv -n TIAuthKF";
				shell_exec($cmd);
				header('Content-Type: application/octetstream; name="TInyAuthKF.8xv"');
				header('Content-Type: application/octet-stream; name="TInyAuthKF.8xv"');
				header('Content-Disposition: attachment; filename="TInyAuthKF.8xv"');
				echo file_get_contents($tifname);
				unlink($binname);
				unlink($tifname);
				exit();
			}
			else { $errors[] = "Signature not valid."; }
		}
		else { $errors[] = "Error generating downloadable keyfile."; }
	}
	else { $errors[] = "Error loading server private key."; }
}

if(isset($_POST["delete_account"])){
	$conn = mysqli_connect($SQL_HOST, $SQL_USER, $SQL_PW, $SQL_DB);
	$stmt_delete_row = $conn->prepare('delete from cred where username = ?');
	$stmt_delete_row->bind_param('s', $_SESSION["username"]);
	$stmt_delete_row->execute();
	session_destroy();
	unset($_SESSION["username"]);
	header("Refresh:0");
}

if(isset($_POST["logout"])){
	session_destroy();
	unset($_SESSION["username"]);
}
?>
