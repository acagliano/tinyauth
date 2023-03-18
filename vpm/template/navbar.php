<div id="navigation">
    <div class="nav-item-icon">
        <a href="/">
            <img id="icon" alt="main-icon" src="/template/vapor_icon.png" />
        </a>
    </div>
    <details>
        <summary class="nav-item"><a href="pkgs.php">&nbsp;PACKAGES</a></summary>
    </details>
    <hr class="decor" />
    <details>
        <summary class="nav-item">&nbsp;SERVERS</summary>
        <?php
            foreach(glob("/home/services/*") as $server){
                if(is_dir($server)){
                    $service=basename($server);
                    echo "<div class=\"nav-item\"><a href=\"srv.php?s=".$service."\">&emsp;".$service."</a></div>";
                    }
                }
            ?>
    </details>
   <hr class="decor" />
    <details>
        <summary class="nav-item" id="login">&nbsp;HOST SERVICE</summary>
        <div class="nav-item"><a href="/cpanel.php">&emsp;VAPOR Portal</a></div>
    </details>
    <hr class="decor" />
    <div style="flex-grow:1;"></div>
    <div id="socials">
        <div class="social-item" style="background:blue; font-family:Tahoma, Arial;"><a href="https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Fvapor.cagstech.com%2F&amp;src=sdkpreparse">f</a></div>
        <div class="social-item" style="background:#00acee; font-family: Helvetica Neue, Helvetica, Arial, sans-serif;"><a href="https://twitter.com/share?ref_src=twsrc%5Etfw" data-url="http://vapor.cagstech.com" data-show-count="false">t</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script></div>
        <div class="social-item" style="background:rgba(0,0,0,.5);"><a href="mailto:acagliano97@gmail.com?Subject=VAPOR%20Inquiry"><img src="/template/email-icon.png" alt="email-icon" style="width:60%; height:auto!important; vertical-align:middle" /></a></div>
    </div>
</div>
