<?php
	$prop = "/var/lib/rkhunter/db/rkhunter.dat";
	$prop_content = file_get_contents($prop);
	$pkg_root_path = "/home/services/vapor/server/packages/vpm/";
?>

<!DOCTYPE html>
<html>
    <head>
        <title>VAPOR | Homepage</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="template/main.css" rel="stylesheet" />
        <link href='https://fonts.googleapis.com/css?family=Nova Flat' rel='stylesheet'>
        <style>
            #content {display:flex; flex-direction:column; margin-top:1%;}
           #content>div:first-child>* {margin:5px 5%; color:white;}
           #srv_name {}
           #srv_about {}
           #srv_port {}
           .default-text-color {color:inherit;}
           table {color:white; width:96%; margin:0 2%; background:rgba(0, 0, 0, .5);}
           #pkg-list tr:not(:first-child) {font-size:80%;}
           #srv_link a {color:inherit;}
           .dl-icon {margin:5px; background:black; color:white; padding:3px 5px; border:3px outset darkgray; border-radius:5px;}
           .dl-icon:hover {border:3px inset darkgray;}
           .slight-blacker {background:rgba(0, 0, 0, .3);}
           .white {background:rgba(255, 255, 255, .5); color:black;}
           .black {background:rgba(0, 0, 0, .5); color:white;}
        </style>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/ghead.html"); ?>
        <script data-ad-client="ca-pub-6268292462233201" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script>
          
        </script>
    </head>
	<body>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/gtags.html"); ?>
		<?php include("template/navbar.php"); ?>
        <div id="content">
            <table >
            <th>Packages Available</th>
            </table>
            <table id="pkg-list">
            <col width="15%" />
            <col width="25%" />
            <col width="45%" />
            <col width="15%" />
            <tr>
				<th>Pkg Name</th>
				<th>Pkg Author</th>
				<th>Description</th>
				<th>View Pkg</th>
			</tr>
            <?php
                $contents = glob($pkg_root_path."*");
                $color = "white";
                foreach($contents as $file){
					if(is_dir($file)){
						$pkg_name = basename($file);
						$pkg_manifest = json_decode(file_get_contents($file."/manifest.json"), true);
						echo "<tr class=\"".$color."\">";
						echo "<td>".$pkg_name."</td>";
						echo "<td>".$pkg_manifest["author"]."</td>";
						echo "<td>".$pkg_manifest["description"]."</td>";
						echo "<td><a class=\"default-text-color\" href=\"packages/".$pkg_name."\">click here</a></td>";
						echo "</tr>";
						if($color=="white"){ $color="black"; }
						else { $color = "white"; }
						
					}
				}
                
            ?>
            </table>
        </div>
	</body>

</html>


