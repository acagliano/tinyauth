
<form id="srvc-config" method="post" action="">
    <h2>service config for <?php echo $_SESSION["data"]["name"]; ?>
        <label for="logout" class="button">LOG OUT</label>
        <input id="logout" type="submit" value="LOG OUT" name="logout" />
    </h2>
    <label for="srvc-port">Service Port:</label>&emsp;
    <input style="border:0; background:transparent; outline:0; color:inherit; font-size:150%;" type="text" id="srvc-port" name="srvc-port" value="<?php echo $_SESSION['data']['port']; ?>" readonly /><br />
    <label for="srvc-desc">Description</label><br />
    <textarea id="srvc-desc" name="srvc-desc"><?php echo $_SESSION["data"]["about"]; ?></textarea><br />
    <label for="srvc-host">Service Host:</label><br />
    <input type="text" id="srvc-host" name="srvc-host" value="<?php echo $_SESSION['data']['host']; ?>" /><br />
    <label for="srvc-link">Project Page:</label><br />
    <input type="text" id="srvc-link" name="srvc-link" value="<?php echo $_SESSION['data']['link']; ?>" /><br />
    <input type="checkbox" id="enable-ad" name="enable-ad" value="enable" <?php if($_SESSION["data"]["show-ad"]==true){echo "checked";} ?> />
    &emsp;<label for="enable-ad">Enable Ad<br /><span style="font-size:70%; font-style:italic;">Turning on an ad unit on the public service listing will help with hosting overhead.</span></label><br />
   <br />
    <label for="upd-serv" class="button">UPDATE SERVICE CONFIG</label>
    <input id="upd-serv" type="submit" value="UPDATE SERVICE" name="upd-serv" />
</form>
<div style="flex:3;">
<form id="srvc-fileupd" method="post" action="" enctype="multipart/form-data">
    <p style="font-weight:bold; text-decoration:underline;">Update Service Files</p>
    <p>Use this form to upload updates to the service dependencies.</p>
  <!--  <label for="srvc-client" class="button">CLIENT BIN</label>
    <input id="srvc-client" type="file" name="srcv-client" /> -->
  <!--  &emsp;<input id="srvc-client-version" type="text" name="srcv-client-version" /> -->
    <label for="srvc-deps" class="button">CHOOSE FILE(S)</label>
    <input id="srvc-deps" type="file" name="srvc-deps[]" multiple />
    <div id="files-uploaded-list"></div>
   <!-- &emsp;<input id="srvc-client-version" type="text" name="srvc-client-version" /> -->
   <label for="srvc-files-upload" class="button">&#11014; UPLOAD FILES &#11014;</label>
   <input id="srvc-files-upload" type="submit" value="UPLOAD ALL FILES" name="srvc-files-upload" />
   <?php echo $res;?>
   </form>
   <hr />
   <form id="srvc-depupd" method="post" action="">
    <label>Set Lib Dependencies</label><br />
<?php
    $libs = glob("software/libs/*.bin");
    $ct=0;
    foreach($libs as $lib){
        $lib = basename($lib, ".bin");
        echo "&emsp;<input type=\"checkbox\" name=\"deps[]\" value=\"".$lib."\" ";
        $str="";
        foreach($_SESSION["data"]["pkg"] as $pkg){
            if($pkg["name"] == $lib){$str="checked";}
        }
        echo $str;
        echo "/>".$lib."<br />";
        $ct++;
    }
?>
<label for="srvc-depend-submit" class="button">UPDATE DEPENDENCIES</label>
   <input id="srvc-depend-submit" type="submit" value="UPDATE DEPENDENCIES" name="srvc-depend-submit" />
</form>
</div>
