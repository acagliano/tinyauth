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

            html, body {width:100vw; height:100vh; margin:0; padding:0; outline:0;}
            html {background:white;}
            body {
                background: rgb(86,60,92);
                background: radial-gradient(circle, rgba(80,64,77, .80) 0%, rgba(80,64,77, 1) 100%);
                background: -moz-radial-gradient(circle, rgba(80,64,77, .80) 0%, rgba(80,64,77, 1) 100%);
                background: -webkit-radial-gradient(circle, rgba(80,64,77, .80) 0%, rgba(80,64,77, 1) 100%);
            }
            #header {
                position:relative;
                background:rgba(255,255,255,1);
                width:100%; height:13%;
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
                text-stroke:2px black;
                -webkit-text-stroke:2px black;
            }
            #infoline-nav {flex:0 1 50%; display:flex; flex-direction:column; height:100%; width:60%;}
            #infoline-nav>* {display:flex; align-items:center; justify-content:center;}
            #infoline {color:blue; width:100%; height:50%; font-size:20px; font-family:"Share Tech";}
            #navigation {
                width:100%;
                height:40%;
                display:flex;
            }
            .navitem {margin:0 2%; flex:1; justify-content:center; height:100%; display:flex; align-items:center; font-family:"Share Tech";}
           // .navitem:hover {background:rgba(80,64,77,.25); cursor:pointer; cursor:hand;}
           .navitem:not(#login-button):hover {background: linear-gradient(to top, rgba(0,0,255,.5) 0%, rgba(255,255,255,1) 100%);}

            #login-button {
                height:80%;
                position:relative;
                display:flex; flex-direction:row;
                justify-content: space-between;
                background:blue;
                border:3px solid silver;
                color:white;
                font-weight:bold;
                border-radius:10px;
            }
            #login-button:hover {}
            #login-button>#login-button-padlock {position:absolute; left:-20px; flex:0; font-size:26px; border-radius:50%; padding:5px 10px; background:blue; border:3px solid silver;}
            #login-button>#login-button-text {width:100%; text-align:center; font-family:"Share Tech";}
        </style>
    </head>
    <body>
        <div id="header">
            <div id="ta-icon"><img id="ta-img" src="ta-logo.png" /></div>
            <div id="title">TInyAuth</div>
            <div id="infoline-nav">
                <div id="infoline">• TI-84+ CE AuthKey Issuing &amp; Authentication Service •</div>
                <div id="navigation">
                    <div class="navitem">Terms of Service</div>
                    <div class="navitem">Privacy Policy</div>
                    <div id="login-button" class="navitem">
                        <div id="login-button-padlock">&#x1f512;</div>
                        <div id="login-button-text">My Account</div>
                    </div>
                </div>
            </div>
        <div id="content">
        </div>
    </body>
</html>