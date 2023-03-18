
<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	session_start();
	$servername = "localhost";
	$username = "services";
	$password = "ticalcdevs01!";
	$db = "ti_services";
	$errors = array();
	if(isset($_POST["login"])){
        $recaptcha = urlencode($_POST["g-recaptcha-response"]);
        if(htmlentities(stripslashes($_POST["user"] == ""))){ die("Invalid user"); }
        $response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6LeKceEbAAAAAEFcsFpAn99vQjtF7g_EG_lICVgR&response=".$recaptcha."&remoteip=".$_SERVER['REMOTE_ADDR']), true);
        if($response['success'] == true){
			$conn = new mysqli($servername, $username, $password, $db);
			if ($conn->connect_error) {
				$errors[] = "Error connecting to SQL database!";
			}
			else {
				$query = sprintf("SELECT * FROM pkg_devs WHERE user='%s'", $conn->real_escape_string($_POST["user"]));
				$result = $conn->query($query);
				if($result->num_rows > 0){
					if($result->num_rows > 1) {$errors[] = "Internal SQL error occured!";}
					else {
						$row = $result->fetch_assoc();
						if(password_verify($_POST["passwd"], $row["pass"])){
							$_SESSION["user"] = $row["user"];
						}
					}
				}
				else {
					$query = sprintf("INSERT INTO pkg_devs (user, pass) VALUES ('%s', '%s')", $conn->real_escape_string($_POST["user"]), password_hash($_POST["passwd"], PASSWORD_DEFAULT));
					if($conn->query($query) === TRUE) {
						$_SESSION["user"] = $conn->real_escape_string($_POST["user"]);
					}
					else {$errors[] = "Error creating new user!";}
				}
				$conn->close();
			}
		}
		else {
			$errors[] = "Recaptcha failed to validate!";
		}
	}
?>
<html>
	<head>
		<title>VPM Developer Portal</title>
		<style>
			table#pkg-listing {width:96%; margin:auto; text-align:left;}
			#pkg-listing textarea {display:block; width:100%; height:100px;}
			label {curor:pointer; cursor:hand;}
			.upload-button {position:relative; display:inline-block; margin:5px 0; padding:0 5px; width:auto; height:auto; background:silver; border:3px outset silver; color:black;}
			.upload-button:after {position:absolute; top:100%; width:100%; left:0;}
			.upload-button:hover {border:2px inset silver;}
			.lighter {background:rgba(0, 0, 0, .1);}
			.darker {background:rgba(0, 0, 0, .2);}
		</style>
		<script src="https://www.google.com/recaptcha/api.js"></script>
		<script
  src="https://code.jquery.com/jquery-3.6.0.slim.min.js"
  integrity="sha256-u7e5khyithlIdTpu22PHhENmPcRdFiHRjhAuHcs05RI="
  crossorigin="anonymous"></script>


		 <script>
		 $(document).ready(function(){
			$("input[type=file]").change(function() {
				$(this).prev("label").html("File(s) Selected");
			});
		 
		 
			document.getElementByTagName('textarea').innerHTML = document.getElementByTagName('textarea').innerHTML.trim();
						   });
			
            function recaptchaSubmit(token) {
                document.getElementById("register-form").submit();
            }
		</script>
		
	</head>
	<body>
	<?php
		if(isset($_SESSION["user"])){
			echo "Welcome ".$_SESSION["user"];
			include_once("pkg-listings.php");
		}
		else {
			include_once("login.php");
		}
	?>
		
	</body>
</html>
