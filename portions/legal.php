<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | Terms of Service, License, and Privacy Policy</title>
        <style>
            html,body {margin:0; padding:0;}
            #content {font-family:Arial; width:70%; margin:0 auto; border-left:5px solid black; border-right:5px solid black; padding:10px;}
            #header {display:flex; flex-direction:row; justify-content:space-between; border-bottom:3px solid black; background:rgba(105,105,105); margin:0;}
            #header>div {padding:5px; color:white; text-decoration:none;}
            #header>div>a {color:inherit;}
            #title {font-size:130%; font-weight:bold;}
        </style>
    </head>
    <body>
        <?php include_once("header-min.php"); ?>
        <div id="content">
            <h2>Terms of Service</h2>
            <ol>
                <li>SERVICE DESCRIPTION:<br />TInyAuth offers a credential-less keyfile-based authentication API for third party services targeting the TI-84+ CE development platform. It allows service developers to handle authenticating users without having to worry about storing user credentials themselves. This Service is offered completely free of charge via a public API that boasts modest security.</li><br />
                <li>LIABILITY:<br />The Service assumes no liability or fault for errors in content, including but not limited to visual content errors, service downtime, API errors, and more. The Service will attempt to give advance notice of planned downtime, maintainence, and depreciation of server resources when able, but you understand that sometimes issues in services, network or server failure, and other technical issues can arise that impact your ability to use the Service. You may report issues on the project Github, should you wish, accessible <a href="https://github.com/acagliano/tinyauth">here</a>.</li><br />
                <li>PRIVACY POLICY:<br />The Service abides by all applicable general and regional standards regarding privacy and consent. By registering to use the Service, you implicitly agree to these Terms of Service. Should you no longer wish to use the Service, you may delete your account via the Dashboard at any time; doing so immediately signs you out and destroys records pertaining to your account.<br /><br />The Service collects minimal personally-identifiable information: an email address used for communicating important information such as updates, depreciation or removal of server resources, key rotation, and planned downtime. Additionally you provide a password when registering for the service. It is recommended this password be unique to the service. Your account also maintains a secret (a random string), a timestamp for the last time the secret was generated, and a unique account identifier. This information is cryptographically transformed to generate keyfile resources made available to end users via their Dashboard. At no point is this information shared with other users, sold, transferred, or used in any manner other than what is indicated herein.<br /><br />As this Service targets a platform commonly used by individuals in high school and college, it is possible that minors may make use of this Service. There are no special age-specific constraints on how the Service uses personal information, but the Service is minimal enough to comply with all relevant legislation anyway.</li><br />
                <li>ACCEPTABLE USE:<br />It is acceptable to use the Service in a manner consistent with the intended usage detailed in SERVICE DESCRIPTION: the generation of keyfiles that can be used to authenticate your TI-84+ CE graphing calculator(s) with online services other developers may create. Attempting to use the Service in any other way, especially for the intent of gaining unauthorized access to Service resources or to authenticate with an account you do not own is a violation of our Terms of Service and may result in termination of your ability to use the Service as well as possible legal action. This applies to both end users and service developers. Additionally, developing a service designed to be abusive, either by flooding our API with requests or for sniffing user credentials for the intent of cracking user secrets or the server&apos;s signing key will be treated as an attempted intrusion and will result in legal action.</li><br />
                <li>MISCELLANEOUS:<br />Due to the nature of the Service, there are a few things that don&apos;t belong anywhere else. Users have a right to privacy and can opt out of sharing their personal information or using our service by deleting their accounts. The API is free to use and therefore the Service does not collect payment. There are no fees associated with Service cancellation, using the Service as a developer, generating keyfiles, etc. We believe in the importance of free, open-source resources to help make the Internet a better, safer place. However, please be aware that this is a passion project developed on the owner&apos;s free time, using lower-end hardware (a Raspberry Pi, actually) on a residential Internet connection. Therefore, the Server may struggle to keep up with high activity. Bear this in mind before expecting top-notch performance at all times.

            </li>
            </ol>
        </div>
    </body>
</html>