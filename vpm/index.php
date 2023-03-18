
<!DOCTYPE html>
<html>
    <head>
        <title>VAPOR | Homepage</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="template/main.css" rel="stylesheet" />
        <link href='https://fonts.googleapis.com/css?family=Nova Flat' rel='stylesheet'>
        <style>
           #content {color:white;}
           .code {padding:2px 5px; font:monospace; background:rgba(192, 192, 192, .75); color:black;}
           .no-color-change {color:inherit;}
        </style>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/ghead.html"); ?>
        <script data-ad-client="ca-pub-6268292462233201" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="template/serial.js"></script>
        <script>
            $(document).ready(function(){
                <?php
                    $dl_dirs = array_reverse(glob("downloads/*"));
                    $string="$('#client-download').hover(function(){ $('#client-download').html('NO DOWNLOAD AVAILABLE'); }, function(){ $('#client-download').html('DOWNLOAD VAPOR CLIENT'); });";
                    $file_link = False;
                    foreach($dl_dirs as $dir){
                        if(file_exists($dir."/VAPOR.8xp")){
                            $file_link = $dir."/VAPOR.8xp";
                            $string="$('#client-download').hover(function(){ $('#client-download').html('<a href=\"".$file_link."\" download>".$dir."</a>'); }, function(){ $('#client-download').html('DOWNLOAD VAPOR CLIENT'); });";
                        }
                    }
                    echo $string;
                    
                ?>
            });
        </script>
    </head>
	<body>
        <?php include($_SERVER["DOCUMENT_ROOT"]."/template/gtags.html"); ?>
		<?php include("template/navbar.php"); ?>
        <div id="content">
            <br />
            <p><span class="emph">Vapor Package Manager</span> (VPM) is a an <span class="code">apt</span>-like software download mirror for the TI-84+ CE graphing calculator, specific to TI-OS. For BOS Package Manager, which is a similar library for BOS by beckadamtheinventor, <a class="no-color-change" href="http://bpm.icycraft-ce.ca" target="_blank">click here</a>.</p>
            <p>For <span class="emph">Vapor Proxy Service</span> (VPS), <a class="no-color-change" href="http://vapor.cagstech.com/vps.php" target="_blank">click here</a>. VPS is a hosting/proxying network I am offering for servers for calculator games and other utilities. Usage of this network is free, but some ads may be placed on explainer pages to help with hosting costs.</p>
            <p>VPM allows calculator users to request downloads or updates of new or existing programs from within other programs simply by calling a function, and also can reload a running program after update. Its features are provided via a Libload-compatible library that works similarly to the other toolchain libraries like GRAPHX or FILEIOC. See the User&apos;s Guide for details. VPM depends on the serial packetization library <span class="emph">INSERT_NAME HERE</span> for packetization, so be sure to send that library to your device as well.</p>
            
            <ul>
                <h3>Vapor Package Manager Features</h3>
                <li>You will need to create an account in order to upload and manage your packages.</li>
                <li>Registering said account and hosting a package on our mirror server is free.</li>
                <li>File integrity protection for every hosted software package.</li>
                <li>Encrypted file transfers supported (requires HASHLIB).</li>
                <br />
            </ul>
        </div>
            <div id="buttons">
				<div id="space"></div>
				<div id="copyright">
					Copyright &copy; 2021-<?php echo date("Y");?> Anthony Cagliano
				</div>
				<div id="space"></div>
                <div id="client-download">
                    DOWNLOAD VAPOR CLIENT
                </div>
                <div id="docs-button"><a href="downloads/docs.php">
                    USER GUIDE
                </a></div>
            </div>
	</body>

</html>
