<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | TI-84+ CE Credentials Grant and Authentication</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            @font-face {
                font-family: "Share Tech";
                src:
                    url("fonts/sharetech/ShareTechMono-Regular.ttf") format("truetype")
            }
            a {color:goldenrod;}
            html, body {width:100vw; height:100vh; margin:0; padding:0; outline:0; overflow:hidden;}
            html {background:white;}
            body {
                background: rgb(105,105,105);
                background: radial-gradient(circle, rgba(105,105,105, .80) 0%, rgba(105,105,105, 1) 100%);
                background: -moz-radial-gradient(circle, rgba(105,105,105, .80) 0%, rgba(105,105,105, 1) 100%);
                background: -webkit-radial-gradient(circle, rgba(48,48,48, .80) 0%, rgba(48,48,48, 1) 100%);
            }
            #header {
                position:relative;
                background:rgba(255,255,255,1);
                width:100%; height:14%;
                color:black;
                box-shadow:2px 2px 10px black;
                display:flex;
                flex-direction:row;
                justify-content:space-between;
            }
            #header>* {display:flex; align-items:center; justify-content:center;}
            #ta-icon {width:auto; flex:0;}
            #title {
                width:auto;
                font-size:64px;
                font-family:"Share Tech";
                font-weight:900;
                flex:0; height:100%;
                text-stroke:3px black;
                -webkit-text-stroke:3px black;
            }
            #infoline-nav {flex:0 1 50%; display:flex; flex-direction:column; height:100%; width:60%;}
            #infoline-nav>* {display:flex; align-items:center; justify-content:center;}
            #infoline {color:blue; font-weight:bold; width:100%; height:40%; font-size:20px; font-family:"Share Tech";}
            #navigation {
                width:100%;
                height:40%;
                display:flex;
            }
            .navitem {margin:0 2%; flex:1; justify-content:center; height:100%; display:flex; align-items:center; font-family:"Share Tech"; cursor:pointer; cursor:hand;}
            .navitem>a {text-decoration:none; color:inherit;}
           .navitem:not(#login-button):hover {background: linear-gradient(to top, rgba(0,0,255,.5) 0%, rgba(255,255,255,1) 12%);}

            #login-button img {width:100%; height:auto!important;}
            #login-button:hover {opacity:0.7;}
            #content{position:relative; width:100%; height:86%;}
            #content>div{
                background:rgba(0,0,0,.15);
                font-size:14px;
                padding:5px;
                color:white;
            }
            #content>div>.title{color:goldenrod; font-weight:bold; font-size:120%; margin-bottom:5px;}
            #content>div>p {margin:0 10px;}
            #content>#content-demo {width:60%; height:auto!important; display:block; margin:auto;}
            #content>#content-exp-keygen {position:absolute; right:45%; top:10%; width:40%;}
            #content>#content-exp-easy {position:absolute; right:50%; bottom:10%; width:45%;}
            #content>#content-exp-devs {position:absolute; right:5%; bottom:10%; width:25%;}
            #content>#content-exp-analytics {position:absolute; right:2%; top:5%; width:18%;}
        </style>
    </head>
    <body>
        <div id="header">
            <div id="ta-icon"><img id="ta-img" src="ta-logo.png" /></div>
            <div id="title">TInyAuth</div>
            <div id="infoline-nav">
                <div id="infoline">• TI-84+ CE AuthKey Issuing &amp; Authentication Service •</div>
                <div id="navigation">
                    <div class="navitem"><a href="portions/tos.php">API Documentation</a></div>
                    <div class="navitem"><a href="portions/tos.php">Legal Notices</a></div>
                    <div id="login-button" class="navitem"><a href="account.php">
                        <img src="myaccount-button.png" alt="my account" />
                    </a></div>
                </div>
            </div>
        </div>
        <div id="content">
            <img id="content-demo" src="demo-img.png" alt="demo" />
            <div id="content-exp-keygen">
                <div class="title">• Secure Keygen</div>
                <p>Generate a keyfile signed using a secure elliptic curve algorithm to send to your TI-84+ CE. Any service that supports TInyAuth will extract credentials from the key silently. Keyfiles may be passphrase-encrypted for additional security.</p>
            </div>
            <div id="content-exp-easy">
                <div class="title">• Easy to Use</div>
                <p>Connect to any game (or other) service for the TI-84+ CE that supports TInyAuth and your credentials are extracted from your keyfile with no user input required.</p>
            </div>
            <div id="content-exp-devs">
                <div class="title">• Devs</div>
                <p>Devs can use <a href="https://acagliano.github.io/cryptx/">CryptX</a> on the client and a simple GET request on the server to authenticate user credentials with TInyAuth.</p>
            </div>
            <div id="content-exp-analytics">
                <div class="title">• Key Usage Analytics</div>
                <p>From the My Account page, view a comprehensive log of all sign-in attempts involving your key(s) including querying host, queries per host, and return status of each request. You can also block a host from using your keys completely, which causes authentication from that host to always fail whether the key is valid or not.</p>
            </div>
        </div>
    </body>
</html>