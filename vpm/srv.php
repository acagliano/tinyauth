<?php
    if(!isset($_GET["s"])){header("Location:http://vapor.cagstech.com");}
    if(isset($_GET["s"])){
        $srv_name=$_GET["s"];
        $dir_to_check="/home/services/".$srv_name;
        if(is_dir($dir_to_check)){
            $service_cfg_file=$dir_to_check."/service.conf";
            $cfg_json=json_decode(file_get_contents($service_cfg_file), true);
            $name = "<div id=\"srv_name\">SERVICE NAME: ".$cfg_json["name"]."</div>";
            $about = "<div id=\"srv_about\">SERVICE DESCRIPTION:<br />".$cfg_json["about"]."</div>";
            $port = "<div id=\"srv_port\">SERVICE PORT: ".$cfg_json["port"]."</div>";
            $up_str = Connect(intval($cfg_json["port"])) ? "ONLINE" : "OFFLINE";
            $up = "<div id=\"srv_status\">SERVICE STATUS: ".$up_str."</div>";
            $show_ad = $cfg_json["show-ad"];
            if($cfg_json["link"]!=""){
                $srv_link = "<div id=\"srv_link\">LINK: <a href=\"".$cfg_json["link"]."\">Project Info</a></div>";
            }
            $author = "<div id=\"srv_author\">AUTHOR: ".parse_var($cfg_json["author"])."</div>";
            $software = "<div id=\"srv_bundle\">";
            $software.="<div id=\"srv_bundle_header\">SOFTWARE REQUIREMENTS</div>";
            $software.="<table>";
            $is_a_white=false;
            foreach($cfg_json["pkg"] as $pkg){
                $software.="<tr";
                if($is_a_white){$software.=" class=\"white\"";}
                $is_a_white=(!$is_a_white);
                $software.="><td>".$pkg["name"]."</td>";
                $software.="<td>".$pkg["type"]."</td>";
                $software.="</tr>";
            }
            $software.="</table></div>";
        }
        else {
            $error="<p>There was an error locating the service configuration file for this server. Contact the VAPOR dev.</p>";
        }
    }
    
    function parse_var($var){
        if(is_array($var)){ return implode(", ", $var); }
        else {return $var;}
    }
    function Connect($port) {
        $serverConn = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr);
        if ($errstr != '') {
            return false;
        }
       fclose($serverConn);
       return true;
      }
?>

<!DOCTYPE html>
<html>
    <head>
        <title>VAPOR | Homepage</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="template/main.css" rel="stylesheet" />
        <link href='https://fonts.googleapis.com/css?family=Nova Flat' rel='stylesheet' />
        <style>
            #content {display:flex; flex-direction:row; margin-top:1%;}
           #content>div:first-child>* {margin:5px 5%; color:white;}
           #srv_name {}
           #srv_about {}
           #srv_port {}
           #srv_bundle {background:rgba(240, 255, 255, .15); width:80%; color:white;}
           #srv_bundle_header {width:100%; background:rgba(0, 0, 0, .3); padding:5px 0; text-align:center;}
           #srv_bundle>table {width:100%; table-layout:fixed;}
           #srv_link a {color:inherit;}
           .white {background:rgba(255, 255, 255, .6); color:black;}
        </style>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/ghead.html"); ?>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script>
          
        </script>
    </head>
	<body>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/gtags.html"); ?>
		<?php include("template/navbar.php"); ?>
        <div id="content">
            <div style="flex:5;">
                <?php echo $name; ?>
                <?php echo $author; ?>
                <?php echo $about; ?>
                <?php echo $srv_link; ?>
                <?php echo $port; ?>
                <?php echo $up; ?>
                <br />
                <?php echo $software; ?>
                <?php echo $error; ?>
            </div>
             <?php
                if($show_ad){
                    include_once("template/ads.html");
                }
            ?>
        </div>
	</body>

</html>

