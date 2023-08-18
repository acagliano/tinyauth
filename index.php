<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | TI-84+ CE Credentials Grant and Authentication</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/template.css" />
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
        <style>
            #content {display:flex;
                flex-direction:row; justify-content:space-around;}

            #content-exp {
                width:45%; display:flex; flex-direction:column; justify-content:space-around;
            }
            #content-demo-container {display:flex; align-items:center; justify-content:center;}
            #content-exp>div{
                background:rgba(0,0,0,.15);
                font-size:calc(8px + 0.5vw);
                padding:5px;
                color:white;
            }
            #content div .title{color:goldenrod; font-weight:bold; font-size:120%; margin-bottom:5px;}
            #content>div>p {margin:0 10px;}
            #content #content-demo {width:100%; height:auto!important; display:block; margin:auto;}
            @media only screen and (max-width: 600px) {
                #content {display:block;}
                #content-exp {display:block; width:100%;}
                #content-exp>div {position:static; width:100%; margin:1% 0;}
                #content-demo-container {display:none;}
            }
        </style>
        <script src="scripts/toggle_compliances.js"></script>
    </head>
    <body>
        <?php include_once("portions/header.php"); ?>
        <div id="content">
            <div id="content-exp" style="">
            <div id="content-exp-keygen">
                <div class="title">• Secure Keygen</div>
                <p>Generate a keyfile containing an access token derived from a secret specific to your user account and securely signed with an elliptic curve algorithm using a master key to prevent forgery. Keyfiles may be encrypted with a secondary passphrase for additional security.</p>
            </div>
            <div id="content-exp-easy">
                <div class="title">• Easy for End Users</div>
                <p>Connect to any game (or other) service for the TI-84+ CE that supports TInyAuth and your credentials are extracted from your keyfile with no user input required.</p>
            </div>
            <div id="content-exp-devs">
                <div class="title">• Easy for Developers</div>
                <p>Service developers can use <a href="https://acagliano.github.io/cryptx/">CryptX</a> to extract credentials on the client and a simple POST request on the server to authenticate user credentials with TInyAuth.</p>
            </div>
            <div id="content-exp-analytics">
                <div class="title">• Key Usage Analytics</div>
                <p>From the My Account page, view a comprehensive log of all sign-in attempts involving your key(s) including querying host, queries per host, and return status of each request. You can also block a host from using your keys completely, which causes authentication from that host to always fail whether the key is valid or not.</p>
            </div>
        </div>
        <div id="content-demo-container" style="width:50%;">
                <img id="content-demo" src="demo-img.png" alt="demo" />
            </div>
        </div>
        <?php include_once("portions/compliance.php"); ?>
    </body>
</html>