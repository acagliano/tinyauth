<?php
    session_start();
    if(isset($_POST["logout"])){
        unset($_SESSION["srvc"]);
        session_destroy();
    }
    if(isset($_POST["service-submit"])){
        $service=$_POST["service-name"];
        $login_string=$_POST["service-login"];
        $errors=array();
        $service_file = "/home/services/".$service."/service.conf";
        $service_json=json_decode(file_get_contents($service_file), true);
        if(password_verify($login_string , $service_json["login-string"])){
            $_SESSION["srvc"]=$service;
            $_SESSION["path"]="services/".$service."/service.conf";
            $_SESSION["data"]=$service_json;
        }
        else { $errors[] = "Invalid password"; }
    }
    if(isset($_POST["upd-serv"])){
        $description=$_POST["srvc-desc"];
        $host=$_POST["srvc-host"];
        $link=$_POST["srvc-link"];
        $ads = $_POST["enable-ad"];
        
        $_SESSION["data"]["about"]=$description;
        $_SESSION["data"]["link"]=$link;
        $_SESSION["data"]["host"]=$host;
        $_SESSION["data"]["show-ad"] = $ads;
        file_put_contents($_SESSION["path"], json_encode($_SESSION["data"]));
    }
    
    if(isset($_POST["srvc-depend-submit"])){
        $deps=$_POST["deps"];
        $ct=0;
        foreach($_SESSION["data"]["pkg"] as $pkg){
            if($pkg["type"]=="libs"){
                unset($_SESSION["data"]["pkg"][intval($ct)]);
            }
            $ct++;
        }
        foreach($deps as $lib){
            $_SESSION["data"]["pkg"][]=array("name"=>$lib, "type"=>"libs");
        }
        $_SESSION["data"]["pkg"]=array_values(array_filter($_SESSION["data"]["pkg"]));
    }
    
    if(isset($_POST["srvc-files-upload"])){
        $deps=$_FILES["srvc-deps"];
        $total = count($deps["name"]);
        for( $i=0 ; $i < $total ; $i++ ) {
            $tmpfile = $deps["tmp_name"][$i];
            $filename = $deps["name"][$i];
            $fileinfo = explode(".", $filename);
            $file_basename = $fileinfo[0];
            $file_ext = $fileinfo[1];
            if(($file_ext == "8xp") or ($file_ext == "8xv")){
                $owned_by_srvc = file_in_pkglist($file_basename, $file_ext);
                $target_dir = "software/usr/";
                $target_file_bin = $target_dir.$filename.".bin";
                $target_file = $target_dir.$filename;
                if((!file_exists($target_file)) or $owned_by_srvc){
                    $cmd = "/home/servers/bin/convbin/bin/convbin -j 8x -k bin -i ".$tmpfile." -o ".$target_file_bin;
                    $convbin_out = shell_exec($cmd);
                    
                    move_uploaded_file($tmpfile, $target_file);
                    touch("logs/uploads.flag");
                   
                    if(strpos($convbin_out, "[success]") !== false){
                        $res="File successfully uploaded";
                    }
                    else {
                        $res="File upload failed";
                    }
                    if(!file_exists($target_file)){
                        file_add_to_pkglist($file_basename, $file_ext);
                    }
                }
            }
        }
    }
    
    function file_in_pkglist($file, $ext){
        $type = ($ext == "8xp") ? "prgm" : "appv";
        foreach($_SESSION["data"]["pkg"] as $pkg){
            if(($file==$pkg["name"]) and ($type==$pkg["type"])){
                return True;
            }
        }
        return False;
    }
    
    function file_add_to_pkglist($file, $ext){
        $_SESSION["data"]["pkg"][] = array("name"=>$file, "type"=>$type);
    }
    
    function file_get_targetdir($file, $ext){
        $dir = ($ext == "8xp") ? "prgm" : "appv";
        return "software/".$dir;
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>VAPOR | Homepage</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="template/main.css" rel="stylesheet" />
        <link href='https://fonts.googleapis.com/css?family=Nova Flat' rel='stylesheet'>
        <style>
            #content {flex-direction:row; justify-content:space-around;}
            #content>* {margin:0 2%;}
            #form {flex:0 0 30%; margin-top:2%;}
            #srvc-config {flex:5;}
            #srvc-fileupd {flex:3;}
            input[type=text],input[type=password], textarea {
                width:95%;
                height:30px;
                outline:0;
                border-color:rgba(255, 255, 255, .3);
                margin-bottom:10px;
                background:rgba(255, 255, 255, .1);
                color:white;}
            textarea {height:150px; display:block;}
            .button {display:block; width:95%; border:3px outset cyan; background:cyan; color:black; text-align:center; margin:10px 0; cursor:pointer; cursor:hand;}
            .button:hover {background:rgba(0, 255, 255, .6);}
            label[for=logout] {display:inline; margin-left:10%; padding:5px; font-size:70%;}
            input[type=submit],input[type=file] {display:none;}
            label[for=srvc-client], label[for=srvc-deps], label[for=srvc-files-upload] {display:block; margin:3% 0; width:60%; border:3px outset cyan; background:cyan; color:black; text-align:center; padding:2px; cursor:pointer; cursor:hand;}
            label[for=srvc-files-upload] {padding:10px 0;}
        </style>
        <script data-ad-client="ca-pub-6268292462233201" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="template/serial.js"></script>
        <script>
            $(document).ready(function(){
                $("#srvc-deps").change(function(){
                    var input = document.getElementById('srvc-deps');
                    var output = document.getElementById('files-uploaded-list');
                    var children = "";
                    for (var i = 0; i < input.files.length; ++i) {
                        children += "&emsp;" + input.files.item(i).name + "<br />";
                    }
                    output.innerHTML = children;
                });
            });
        </script>
    </head>
	<body>
		<?php include("template/navbar.php"); ?>
        <div id="content">
            <?php
                if(isset($_SESSION["srvc"])){
                    include "template/cpanel-srvc.php";
                }
                else { include "template/cpanel-gen.html"; }
            ?>
        </div>
	</body>

</html>
