<?php
	if(isset($_SERVER["HTTP_REFERER"]) && ($_SERVER["HTTP_REFERER"] == $_SERVER["PHP_SELF"])){
		die("This should not be accessed in this manner!");
	}
	$pkg_root = "/home/services/vapor/server/packages/";
	$my_pkgs = array();
	foreach(["vpm","bpm"] as $target){
		$target_root = $pkg_root . $target . "/";
		$pkg_list = glob($target_root."*");
		foreach($pkg_list as $pkg){
			$pkg_name = basename($pkg);
			$pkg_manifest= file_get_contents($_SERVER["DOCUMENT_ROOT"]."packages/".$target . "/".$pkg_name."/manifest.json");
			$pkg_json = json_decode($pkg_manifest, true);
			if($pkg_json["author"] == $_SESSION["user"]){
				$my_pkgs[$pkg_name] = array(
					"target"=>$target,
					"manifest"=>$pkg_manifest
				);
			}
		}
	}
	if(isset($_POST["logout"])){
		session_destroy();
		unset($_SESSION["user"]);
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
	if(isset($_POST["delete-file"])){
		$delete_this = $_POST["delete-file"];
		$delete_components = explode("-", $delete_this);
		$delete_target = $delete_components[0];
		$delete_package = $delete_components[1];
		$delete_file = $delete_components[2];
		if(array_key_exists($delete_package, $my_pkgs)){
			unlink($_SERVER["DOCUMENT_ROOT"]."packages/".$delete_target."/".$delete_package."/".$delete_file);
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
	if(isset($_POST["add-new-pkg"])){
		$pkg_name = stripslashes(htmlentities($_POST["new-pkg-name"]));
		$pkg_target = stripslashes(htmlentities($_POST["new-pkg-target"]));
		$pkg_dir = $_SERVER["DOCUMENT_ROOT"]."packages/".$pkg_target."/".$pkg_name;
		$manifest = array();
		$manifest["category"] = "general";
		$manifest["author"] = $_SESSION["user"];
		$manifest["description"] = "";
		$manifest["pkg-depends"] = [];
		if(!file_exists($pkg_dir)){
			mkdir($pkg_dir);
			file_put_contents($pkg_dir."/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
	if(isset($_POST["pkg-delete"])){
		$pkg_name = stripslashes(htmlentities($_POST["pkg"]));
		$pkg_target = stripslashes(htmlentities($_POST["pkg-target"]));
		if(array_key_exists($pkg_name, $my_pkgs)){
			$pkg_dir = $_SERVER["DOCUMENT_ROOT"]."packages/".$pkg_target . "/" .$pkg_name;
			delTree($pkg_dir);
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
	
	if(isset($_POST["pkg-update"])){
		ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
		$pkg_name = stripslashes(htmlentities($_POST["pkg"]));
		$pkg_target = stripslashes(htmlentities($_POST["pkg-target"]));
		$manifest_new = json_decode(strval($_POST["manifest"]), true);
		if($manifest_new===null) { echo "Error"; }
            echo $pkg_name."<br />";
            echo $pkg_target."<br />";
		// First we edit the manifest file as requested
		if(array_key_exists($pkg_name, $my_pkgs)){
			$pkg_dir = $_SERVER["DOCUMENT_ROOT"]."packages/".$pkg_target . "/" .$pkg_name;
			$manifest_file = $pkg_dir."/manifest.json";
			$manifest_json = json_decode(file_get_contents($manifest_file), true);
			$manifest_changed = false;
			foreach($manifest_new as $key=>$value){
				if($manifest_new[$key] != ""){
					if((!array_key_exists($key, $manifest_json)) ||
						($manifest_new[$key] != $manifest_json[$key])){
						$manifest_json[$key] = $manifest_new[$key];
						$manifest_changed = true;
					}
				}
			}
			if($manifest_changed == true){
				file_put_contents($manifest_file, json_encode($manifest_json, JSON_PRETTY_PRINT));
			}
			
			// Now to handle file updates/uploads
			$upload_dir = $pkg_dir;
			$num_files = count($_FILES['fileupload']['name']);
			for( $i=0 ; $i < $num_files ; $i++ ) {
				$tmpFilePath = $_FILES['fileupload']['tmp_name'][$i];
				if ($tmpFilePath != ""){
					$file_meta = explode(".", $_FILES["fileupload"]["name"][$i]);
					if((strcasecmp($file_meta[count($file_meta)-1], "8xp") == 0) || (strcasecmp($file_meta[count($file_meta)-1], "8xv") == 0) || (strcasecmp($file_meta[count($file_meta)-1], "bin") == 0)){
						//Setup our new file path
						$newFilePath = $upload_dir . "/" . $_FILES["fileupload"]["name"][$i];
						if(file_exists($newFilePath)){ unlink($newFilePath); }
						if(move_uploaded_file($tmpFilePath, $newFilePath)) {
                            echo "Success";
						}
                        else { echo "Error"; }
					}
				}

			}
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
	
	function delTree($dir) {
		$files = glob( $dir . '*', GLOB_MARK );
		foreach( $files as $file ){
			if( substr( $file, -1 ) == '/' )
				delTree( $file );
			else
				unlink( $file );
		}
   
		if (is_dir($dir)) rmdir( $dir );
	}
?>
<form id="logout" action="<?php echo htmlentities($_SERVER['PHP_SELF']);?>" method="post">
	<input type="submit" id="submit-logout" name="logout" value="Log Out" />
</form>

<table id="pkg-listing">
<col width="10%" />
<col width="35%" />
<col width="20%" />
<col width="5%" />
<tr><th>Pkg Name</th><th>Pkg Manifest</th><th>Files</th><th>Actions</th><th></th></tr>
<?php
	$color="lighter";
	
	foreach($my_pkgs as $pkg_name=>$pkg_data){
		$pkg_target = $pkg_data["target"];
		$pkg_manifest = $pkg_data["manifest"];
		echo "<tr class=\"".$color."\">";
		echo "<form method=\"post\" action=\"\" enctype=\"multipart/form-data\">";
		echo "<input name=\"pkg\" value=\"".$pkg_name."\" hidden />";
		echo "<input name=\"pkg-target\" value=\"".$pkg_target."\" hidden />";
		echo "<td>".$pkg_target. "/".$pkg_name."</td>";
		echo "<td><textarea name=\"manifest\">".$pkg_manifest."</textarea></td>";
		echo "<td>";
		foreach(glob($_SERVER["DOCUMENT_ROOT"]."packages/".$pkg_target."/".$pkg_name."/*.{8xp,8xv}", GLOB_BRACE) as $f){
			$file = basename($f);
			echo $file."&emsp;<label style=\"color:red; font-size:120%;\" for=\"pkg-".$pkg_target."-".$pkg_name."-".$file."-delete\">&otimes;</label><input id=\"pkg-".$pkg_target."-".$pkg_name."-".$file."-delete\" type=\"submit\" name=\"delete-file\" value=\"".$pkg_target."-".$pkg_name."-".$file."\" hidden /><br />";
		}
		echo "<label class=\"upload-button\" for=\"pkg-".$pkg_name."-uploader\">Add or Update Files</label><input id=\"pkg-".$pkg_name."-uploader\" type=\"file\" name=\"fileupload[]\" ";
            if($pkg_target == "vpm"){
                echo "accept=\"application/x-ti83plus-variables,application/x-ti83plus-program\" multiple hidden />";
            }
            else {
                echo "accept=\"application/octet-stream\" multiple hidden />"; }
		echo "</td>";
		echo "<td><input type=\"submit\" value=\"Update Package\" name=\"pkg-update\" /><br /><br /><input type=\"submit\" value=\"Delete Package\" name=\"pkg-delete\" /></td>";
		echo "</form>";
		echo "</tr>";
		if($color=="lighter"){$color="darker";}
		else {$color="lighter";}
	}
	
?>
<tr style="height:10px;"></tr>
<tr>
	<form method="post" action="">
		<td><input type="text" name="new-pkg-name" placeholder="Package Name" /></td>
		<td>
			<select name="new-pkg-target">
				<option selected disabled>Package Target</option>
				<option value="vpm">ti-os</option>
				<option value="bpm">bos</option>
			</select>
			<input type="submit" name="add-new-pkg" value="Create New Package" />
		</td>
		<td></td>
		
		<td></td>
		<td></td>
	</form>
</tr>
</table>
