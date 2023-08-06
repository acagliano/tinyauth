<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | API Documentation</title>
        <style>
            #content {font-family:Arial; width:70%; margin:0 auto; border-left:5px solid black; border-right:5px solid black; padding:10px;}
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
        <a href="/">Home</a>&emsp;|&emsp;<a href="<?php echo filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL); ?>">Back</a>
        <div id="content">
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
            <h2>Client-Side</h2>
            <ol>
                <li>
                    <span class="heading">Locating TInyAuth Keys</span><br />
                    TInyAuth keyfiles are always prefixed by the 6-byte string &quot;TIAUTH&quot; which can be paired with the use of the <span style="font-family:monospace;">ti_Detect</span> function from the <span style="font-family:monospace;">fileioc</span> toolchain standard library to get a list of all Application Variables on the device with that prefix. The user can then navigate a GUI to select the appropriate keyfile from that list.
                </li><br />
                <li>
                    <span class="heading">Extracting Credentials</span><br />
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
                    <span class="heading">Serializing for Transfer</span><br />
                    Once the keyfile data has been processed, the client then needs to transfer it to the server in an understandable format. The recommended means of doing so involve encoding the credentials as zero-terminated strings or as length-prepended strings. You are not restricted to this, just make sure that whatever serialization method you use, you de-serialize using an appropriate algorithm on the server.
                </li><br />
                <li>
                    <span class="heading">The TInyAuth Static Library</span><br />
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
// Allow user to return empty string if no password needed

struct tinyauth_key k;
tinyauth_open(&k, key_selected, password);
// ^ Note that should the integrity checks included in the key fail,
// an error will be returned and if the key is encrypted, the key will
// not decrypt.

if(!k.error){
    uint8_t buf[k.credentials_len]; // this field can approximate needed output size
    size_t olen = tinyauth_serialize_for_transfer(&k, buf, TA_SERIALIZE_0TERM);
    network_send(buf, olen);
    memset(buf, 0, olen);   // wipe this after use
}

tinyauth_close(&k);
// ^ This frees allocated memory and destroys the copy of the key
// Do not forget this or you may leave decrypted keyfile in memory.</pre>
                </li>
            </ol><br />
            <h2>Server-Side</h2>
            <p>Use of TInyAuth on the server-side is even simpler. Once you deserialize the credentials you can send a POST request to TInyAuth with the credentials supplied as parameters. There are three parameters required:</p>
            <ul>
                <li>user:&emsp;should contain the numerical ID of the user who owns the token to follow</li>
                <li>token:&emsp;should contain the raw data of the user&apos;s authentication token</li>
                <li>origin:&emsp;should contain the IP address of the host supplying the credentials</li>
            </ul>
            <p>As an alternative to the &quot;origin&quot; field specified above you may set the &quot;X-Forwarded-For&quot; header in your request. If this is specified, it will override the origin parameter of the request (which may in this case be omitted). Note that if you want to allow our Service to perform rate limiting against clients, the X-Forwarded-For header is required.</p>
            <p>Note that both the user and token fields, as well as either the X-Forwarded-For header or origin field are required for the query to be accepted. Also be advised that all queries are sanitized and validated via appropriate input filters. Here is some code demonstrating how to pack user credentials into a valid POST request, in Python.</p>
            <pre>
import requests

// assume received data is in 'data' and serialization method is TA_SERIALIZE_OTERM

# deserialize credentials packet
creds = data.split("\0", maxsplit=1)
# ^ maxsplit prevents token from mistakenly being split if a byte happens to be 0

# send POST request
uri = "https://tinyauth.cagstech.com/auth.php"
response = requests.post(
    uri,
    headers={"X-Forwarded-For":self.addr[0]},
    params={'user': creds[0], 'token': creds[1], 'origin': self.addr[0]},
    # self.addr is assumed to be the addr tuple belonging to the client&apos;s socket
)

# check response
if response.json["success"] == True:
    // user authenticated successfully
else:
    // user did not authenticate
    // error string in response.json["error"]
    // if error string unset, credentials were incorrect
    // else, internal error most likely</pre>
    <p>It&apos;s really that simple.</p>
        </div>
    </body>
</html>