<!DOCTYPE html>
<html>
    <head>
        <title>TInyAuth | Terms of Service, License, and Privacy Policy</title>
        <style>
            #content {font-family:Arial; width:70%; margin:0 auto; border-left:5px solid black; border-right:5px solid black; padding:10px;}
            .heading {font-weight:bold;}
        </style>
    </head>
    <body>
        <a href="/">Home</a>&emsp;|&emsp;<a href="<?php echo filter_input(INPUT_SERVER, "HTTP_REFERER", FILTER_SANITIZE_URL); ?>">Back</a>
        <div id="content">
            <h1>API Documentation</h1>
            <p>Utilizing TInyAuth requires two layers of handling--client-side and server-side. The client-side deals with finding TInyAuth keyfiles, decoding the data within, performing decryption if the file is encrypted, and serializing that data into something that the server-side can process. The server-side deals with abstracting the data sent by the client and issuing a properly-formatted request to TInyAuth for authentication using that information.</p>
            <p>It is also highly recommended that developers encrypt the transfer of credentials between the client and their service. As the API for TInyAuth is served over SSL no further encryption is needed. The workflow for processing user credentials should look something like this:
                <ol>
                    <li>Search for TInyAuth keyfiles by prefix string.</li>
                    <li>Load keyfile selected by user.</li>
                    <li>Decode ASN.1-encoded key data.</li>
                    <li>Determine if keyfile uses encryption, if so decrypt.</li>
                    <li>Serialize credentials into packet for sending to bridge<sup>1</sup>.</li>
                    <li>Bridge<sup>1</sup> should relay packet over secure socket (SSL) to target server.</li>
                    <li>Target server extracts serialized credentials, constructs and API request, and forwards that to TInyAuth.</li>
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
                    <pre style="background:rgba(0,0,0,.2); padding:2%; width:90%;">
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
                </li>
                 <li>
                    <span class="heading">Serializing for Transfer</span><br />
                    Once the keyfile data has been processed, the client then needs to transfer it to the server in an understandable format. The recommended means of doing so involve encoding the credentials as zero-terminated strings or as length-prepended strings. You are not restricted to this, just make sure that whatever serialization method you use, you de-serialize using an appropriate algorithm on the server.
                </li>
                <li>
                    <span class="heading">The TInyAuth Static Library</span><br />
                    We have made available a static library to handle all of the aforementioned keyfile decoding and serialization. This leaves the application developer to handle only keyfile selection and actual transmission of data. The library is available at the TInyAuth github <a href="">
                </li>
            </ol>
        </div>
    </body>
</html>