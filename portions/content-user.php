
	<div id="recent-logins" class="user-widget">
		<div>Recent Keyfile Authentication Attempts [Last 30 days]</div>
		<?php
			$json_all = json_decode(file_get_contents("logs/keychecks.log"), true);
			foreach($json_all as $line){
				if ($line["user"]==$_SESSION["username"]){
					$logdate = new \DateTime($line["timestamp"]);
					$now = new \DateTime();
					if($logdate->diff($now)->days <= 30) {
						echo $line["timestamp"]."] User: ".$line["user"].", Host: ".$line["remote-ip"].", Status: ".$line["status"]."<br />";
					}
				}
			}
		?>
	</div>
<!--
<h3>For End Users</h3>
	<p>TInyAuth allows for online calculator games or other resources to facilitate credential-less authentication by querying our API to validate a token issued by this service. End users may download a keyfile containing their account ID (username) and a digitally-signed authentication token that both identifies the user and authenticates the issuing host.</p>
	<p><span style="background:silver; color:black;">Refresh User Token</span> in the sidebar can be used to generate a new authentication token for your account, which also revokes all keyfiles issued for this account.<br />
		<span style="background:silver; color:black;">Download Keyfile</span> creates an (automatic) download of a file &quot;TInyAuthKF.8xv&quot; that can be used as a single-sign-on key for any calculator services that support TInyAuth.</p>

	<h3>For Service Developers</h3>
	<p>Authentication with TInyAuth is simple. You will need to have the calculator ship its keyfile to you. I recommend doing so using the end-to-end encryption faciliated by the <a href="https://acagliano.github.io/cryptx/index.html" style="color:inherit;" target="_blank">CryptX</a> library. The keyfile is a concatentation of the following data:<br />
	<pre style="background:silver; color:black; font-family:monospace; font-size:14px;">
TInyAuthKF		# prefix string, file identifier
&lt;username&gt;\0		# zero-terminated account id (user)
&lt;signed_token&gt;		# ECDSA-signed account token</pre>
	To parse the key easily, strip the prefix string and then split once the remaining data at the &quot;\0&quot; character. Once you have the account id and token, you send a GET request to the TInyAuth service. The authentication service runs on SSL which facilitates end-to-end encryption between individual authenticating services and TInyAuth. Here is an example, in Python.
	<pre style="background:silver; color:black; font-family:monospace; font-size:14px;">
import requests

...
uri = "https://tinyauth.cagstech.com/authenticate.php"
response = requests.get(
	uri,
	params={'user':&lt;user&gt;, 'token':&lt;token&gt;},
)
print(f"{response.json}")
</pre></p>
-->

