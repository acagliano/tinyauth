<?php
    if(isset($_POST["logout"])){
        session_destroy();
        unset($_SESSION);
        header("Refresh:0");
    }
?>
<div id="dashboard">
    <h2>Welcome <?php echo $_SESSION["user"]; ?></h2>
    <form id="logout" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
        <input type="submit" name="logout" value="Log Out" />
    </form>
</div>