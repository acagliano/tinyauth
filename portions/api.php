<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | API Documentation</title>
        <style>
            html,body {margin:0; padding:0;}
            #content {font-family:Arial; width:70%; margin:0 auto; border-left:5px solid black; border-right:5px solid black; padding:10px;}
            #header {display:flex; flex-direction:row; justify-content:space-between; border-bottom:3px solid black; background:rgba(105,105,105); margin:0;}
            #header>div {padding:5px; color:white; text-decoration:none;}
            #header>div>a {color:inherit;}
            #title {font-size:130%; font-weight:bold;}
            .heading {font-weight:bold;}
            pre {font-family:monospace; background:rgba(0,0,0,.2); padding:2%; width:90%; white-space:pre-wrap;}
            @media only screen and (max-width: 600px) {
                #content{width:99%; margin:auto; border:none;}
                #content>*{margin:0 2%;}
                pre {width:99%;}
            }
        </style>
    </head>
    <body>
        <?php include_once("header-min.php"); ?>
        <div id="content">
            <a name="top"></a>
            <h2>Table of Contents</h2>
            <table width="60%">
                <col width="80%" />
                <col widith="20%" />
                <tr><td>A.&emsp;Client-Side</td><td><a href="#clientside">goto</a></td></tr>
                <tr><td>&emsp;&emsp;1.&emsp;Locating TInyAuth Keys</td><td><a href="#locatingkeys">goto</a></td></tr>
                <tr><td>&emsp;&emsp;2.&emsp;Extracting Credentials</td><td><a href="#extractingcreds">goto</a></td></tr>
                <tr><td>&emsp;&emsp;3.&emsp;Prompting for 2FA</td><td><a href="#prompting2fa">goto</a></td></tr>
                <tr><td>&emsp;&emsp;4.&emsp;Serializing for Transfer</td><td><a href="#serializingxfer">goto</a></td></tr>
                <tr><td>&emsp;&emsp;5.&emsp;The TInyAuth Static Library</td><td><a href="#staticlib">goto</a></td></tr>
                <tr><td>B.&emsp;Server-Side</td><td><a href="#serverside">goto</a></td></tr>
                <tr><td>&emsp;&emsp;1.&emsp;De-serializing Credentials</td><td><a href="#deserializing">goto</a></td></tr>
                <tr><td>&emsp;&emsp;2.&emsp;Constructing the POST Request</td><td><a href="#constructingpost">goto</a></td></tr>
                <tr><td>&emsp;&emsp;3.&emsp;Processing the Response</td><td><a href="#processingresp">goto</a></td></tr>
            </table>

            <h1>API Documentation</h1>
            <p>Utilizing TInyAuth requires two layers of handling--client-side and server-side. The client-side deals with finding TInyAuth keyfiles, decoding the data within, performing decryption if the file is encrypted, and serializing that data into something that the server-side can process. The server-side deals with abstracting the data sent by the client and issuing a properly-formatted request to TInyAuth for authentication using that information.</p>
            <p>It is also highly recommended that developers encrypt the transfer of credentials between the client and their own service. The API for TInyAuth is served over SSL. The workflow for processing user credentials should look something like this:
                <ol>
                    <li>Search for TInyAuth keyfiles by prefix string.</li>
                    <li>Load keyfile selected by user.</li>
                    <li>Decode ASN.1-encoded key data.</li>
                    <li>Determine if keyfile uses encryption, if so decrypt.</li>
                    <li>Serialize credentials into packet for sending to bridge<sup>1</sup>.</li>
                    <li>Bridge<sup>1</sup> should relay packet over secure socket (SSL) to target server.</li>
                    <li>Target server extracts serialized credentials, constructs an API request, and forwards that to TInyAuth.</li>
                    <li>Target server checks response json for the &quot;success&quot; and optionally &quot;error&quot; keys. Handle user authorization accordingly.</li>
                </ol>
            </p>
            <hr width="80%;" />
            <p style="font-style:italic; font-size:90%; width:90%; margin:auto;"><sup>1</sup>The use of a bridge is required because there currently exists no way to connect the client device directly to a router. The device can, however, open a usb/serial connection with a computer running a bridge that can convert between serial packets and TCP packets to facilitate a network.</p>
            <a name="clientside"></a><h2>Client-Side</h2>
            <ol>
                <li>
                    <span class="heading"><a name="locatingkeys"></a>Locating TInyAuth Keys</span>&emsp;&emsp;<a href="#top">top</a><br />
                    TInyAuth keyfiles are always prefixed by the 6-byte string &quot;TIAUTH&quot; which can be paired with the use of the <span style="font-family:monospace;">ti_Detect</span> function from the <span style="font-family:monospace;">fileioc</span> toolchain standard library to get a list of all Application Variables on the device with that prefix. The user can then navigate a GUI to select the appropriate keyfile from that list.
                </li><br />
                <li>
                    <span class="heading"><a name="extractingcreds"></a>Extracting Credentials</span>&emsp;&emsp;<a href="#top">top</a><br />
                    Once selected, the file can be opened using the <span style="font-family:monospace;">ti_Open</span> function from the <span style="font-family:monospace;">fileioc</span> toolchain standard library or using the <span style="font-family:monospace;">fopen</span> API from the C standard also implemented within the toolchain. The file can be read in-place but it is recommended to make a copy and perform decoding on that both to ensure scope and to allow for decryption without altering the file.<br /><br />
                    The ASN.1-encoded structure begins at the end of the 6-byte prefix string meaning you will need to begin decoding at that point. The ASN.1 structure of the keyfile is as follows:<br />
                    <pre>
KeyNormal     :: SEQUENCE {
    Encrypted   BOOLEAN,
    Credentials :: SEQUENCE {
        UserID      INTEGER,
        Token       OCTET STRING
    },
    Hash    OCTET STRING
}

KeyEncrypted    :: SEQUENCE {
    Encrypted   BOOLEAN
    Salt        OCTET STRING
    Credentials OCTET STRING
    Tag         OCTET STRING
}
where: Credentials, Tag = Cipher(AES-256-GCM,
        key     = PBKDF2(password = user-input, salt = Salt, rounds = 100)[:32]
        iv      = PBKDF2(password = user-input, salt = Salt, rounds = 100)[32:48]
        data    = Credentials :: SEQUENCE {UserId INTEGER, Token OCTET STRING})
        // Note that of the 48-byte PBKDF2 returned, the first 32 bytes are the key,
        // and the last 16 are the iv/nonce
        // the user should supply the password as input during key load, or the password
        // can be read from some arbitrary on-device password manager</pre>
                </li><br />
                <li>
                    <span class="heading"><a name="prompting2fa"></a>Prompting for 2FA</span>&emsp;&emsp;<a href="#top">top</a><br />
                    The TInyAuth API currently does not communicate the necessity of 2FA to the client prior to the authentication attempt. This means that the client application developer will need to handle prompting the user for a TOTP optionally and handling the user returning an empty string (handling that case as NULL). This also means the user will need to know if their account is configured to use 2FA for keyfile authentication. Prompt for a six-digit TOTP code and pass the pointer to that string (or NULL) to the serialization. Be aware (as both developer and end-user) that failing to provide a TOTP code during authentication with 2FA enabled will simply fail for <span style="font-style:italic;">invalid credentials</span>.
                </li><br />

                 <li>
                    <span class="heading"><a name="serializingxfer"></a>Serializing for Transfer</span>&emsp;&emsp;<a href="#top">top</a><br />
                    Once the keyfile data has been processed, the client then needs to transfer it to the server in an understandable format. The recommended means of doing so involves encoding the credentials as length-prepended strings. Zero-termination is not reliable since you cannot guarantee that the token string will not contain a zero byte somewhere. You are not restricted to this serialization format, just make sure that whatever serialization method you use, you de-serialize using an appropriate algorithm on the server.
                </li><br />
                <li>
                    <span class="heading"><a name="staticlib"></a>The TInyAuth Static Library</span>&emsp;&emsp;<a href="#top">top</a><br />
                    We have made available a static library to handle all of the aforementioned keyfile decoding and serialization. This leaves the application developer to handle only keyfile selection and actual transmission of data. The library is available at the <a href="https://github.com/acagliano/tinyauth/tree/ccbe57e3f7f2c0c0cde1baea2d3f2b1c51b617c3/client-library" target="_blank">TInyAuth Github</a>. NOTE: Requires <a href="https://github.com/acagliano/cryptx">Cryptx</a> library. Use of the library API is quite simple:
                    <pre>
#include &lt;fileioc.h&gt;
#include "tinyauth.h"

char *var_name;
void *vat_ptr = NULL;
char keyfiles[128][9];   // support up to 128 keyfiles
size_t keyfile_count = 0;

while ((var_name = ti_Detect(&vat_ptr, "TIAUTH"))){
    strcpy(keyfiles[keyfile_count], var_name);
    keyfile_count++;
}
char* key_selected = user_select_key(keyfiles, keyfile_count);
// ^ this would be an API that lets the user select the key
char* password = prompt_for_user_password();
// ^ this would be an API that prompts for password
// return NULL if no password entered

struct tinyauth_key k;
tinyauth_open(&k, key_selected, password);
// ^ Note that should the integrity checks included in the key fail,
// an error will be returned and if the key is encrypted, the key will
// not decrypt.

if(!k.error){
    uint8_t buf[k.credentials_len + 9]; // this field can approximate needed output size
    char otp[7];
    prompt_for_otp(otp);
    // ^ allow user to supply OTP for 2FA
    // return NULL if no OTP entered
    size_t olen = tinyauth_serialize_for_transfer(&k, otp, buf);
    network_send(buf, olen);
    memset(buf, 0, olen);   // wipe this after use
}

tinyauth_close(&k);
// ^ This frees allocated memory and destroys the copy of the key
// Do not forget this or you may leave decrypted keyfile in memory.</pre>
                </li>
            </ol><br />
            <a name="serverside"></a><h2>Server-Side</h2>
            <p>Use of TInyAuth on the server-side is even simpler. 
            <ol>
                <li><span class="heading"><a name="deserializing"></a>De-serializing Credentials</span>&emsp;&emsp;<a href="#top">top</a><br />
                    The first step to this part of the process is to reverse the serialization applied on the client side. This guide assumes you are length-prepending each field of the credentials payload. The first two fields--user id and signature--will always exists. The first field, the TOTP code, may not always be present. The following code written in Python should properly de-serialize the payload.
                    <pre>
# assume received data is in 'data' and serialization method is length-prepended
# deserialize credentials packet (length-prepended)

# get user id
if len(data) > 3:
    # data len should be &gt; size word len
    segment_len = data[0:3]
    data = data[3:]     # trim size word
    if len(data) > segment_len:
        # data len should be &gt; segment len
        userid = data[:segment_len]
        data = data[segment_len:]       # trim segment
    else: raise Exception("serialization error")
else raise Exception("serialization error")

# get token
if len(data) > 3:
    # data len should be &gt; size word len
    segment_len = data[0:3]
    data = data[3:]     # trim size word
    if len(data) >= segment_len:
        data len should be &gt; segment len
        token = data[:segment_len]
        data = data[segment_len:]       # trim segment
    else: raise Exception("serialization error")
else: raise Exception("serialization error")

# conditional get otp
otp = ""
if len(data):
    # this segment is optional
    if len(data) > 3:
        # data len should be &gt; size word len
        segment_len = data[0:3]
        if segment_len != 6: raise Exception("serialization error")
        # ^ TOTP code should be 6 digits
        data = data[3:]     # trim size word
        if len(data) == segment_len:
            # data len should be == segment len
            otp = data[:segment_len]
            # no need to trim, we&apos;s done with data
        else: raise Exception("serialization error")
    else: raise Exception("serialization error")</pre>
                </li>
            
                <li><span class="heading"><a name="construstingpost"></a>Constructing the POST Request</span>&emsp;&emsp;<a href="#top">top</a><br />
                Once you deserialize the credentials you can send a POST request to TInyAuth with the credentials supplied as parameters. There are four valid parameters:</p>
                <ul>
                    <li>user (required):&emsp;should contain the numerical ID of the user who owns the token to follow</li>
                    <li>token (required):&emsp;should contain the raw data of the user&apos;s authentication token</li>
                    <li>otp (conditional):&emsp;should contain a one-time passcode provided by a configured TOTP application</li>
                    <li>origin (conditional):&emsp;should contain the IP address of the host supplying the credentials</li>
                </ul>
                <p>NOTE: OTP: This field is required if 2FA for keyfile authentication is turned on in your account 2FA configuration. If it is disabled, this field can be omitted without consequence.</p>
                <p>NOTE: ORIGIN: As an alternative to the &quot;origin&quot; field specified above you may set the &quot;X-Forwarded-For&quot; header in your request. If this is specified, it will override the origin parameter of the request (which may in this case be omitted). Note that if you want to allow our Service to perform rate limiting against clients, the X-Forwarded-For header is required.</p>
                <p>Note that both the user and token fields, as well as either the X-Forwarded-For header or origin field are required for the query to be accepted. Also be advised that all queries are sanitized and validated via appropriate input filters. Here is some code demonstrating how to pack user credentials into a valid POST request, in Python.</p>
                <pre>
import requests
# send POST request
uri = "https://tinyauth.cagstech.com/auth.php"
response = requests.post(
    uri,
    headers={"X-Forwarded-For":self.addr[0]},
    params={'user': userid, 'token': token, 'otp': otp},
    # self.addr is assumed to be the addr tuple belonging to the client&apos;s socket
)</pre>

                </li>

                <li><span class="heading"><a name="processingresp"></a>Processing the Response</span>&emsp;&emsp;<a href="#top">top</a><br />
                The response is an HTTP status code as well as a JSON object. To make matters simple you can interpret a 200 status code as success, a 4XX as an authentication failure, and a 5XX as an error. Alternatively, you can interpret the JSON response to determine exactly what error took place. This is recommended. See the Python code excerpt below.
                <pre>
# check response
if response.json["success"] == True:
    // user authenticated successfully
else:
    // user did not authenticate
    // error string in response.json["error"]
    // if error string unset, credentials were incorrect
    // else, internal error most likely</pre>
                </li>
</ol>
    <p>It&apos;s really that simple.</p>
        </div>
    </body>
</html>