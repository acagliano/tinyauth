<!DOCTYPE html>
<html>
    <head>
        <title>VAPOR | Documentation</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="../template/main.css" rel="stylesheet" />
        <link href='https://fonts.googleapis.com/css?family=Nova Flat' rel='stylesheet'>
        <style>
            #content {color:white; height:100vh; overflow-y:auto;}
            .bold {font-weight:bold;}
            .italic {font-style:italic;}
            h2 {margin-left:5%;}
            .key {
                padding:3px; border:2px outset silver;
                drop-shadow:2px 2px 3px black;
                background:silver;
                color:black;
                margin:10px 5px;
            }
            .code {background:rgba(255, 255, 255, .15); margin:1% 7%; padding:1%;}
            section {background:rgba(255, 255, 255, .1); padding:1% 5%;}
            .embedded {border:3px outset #333; float:right; width:30%; margin:10px; drop-shadow:3px 3px 4px black;}
            .embedded>div {width:100%; background:#e0ffff; color:black; padding:5px 0; text-align:center; font-weight:bold; border:1px solid #333;}
            .embedded>img {display:block; width:100%; height:auto!important; border:1px solid #333;}
            dt {font-weight:bold; font-size:110%; margin:10px 0;}
            .p-footer {font-size:70%; font-style:italic;}
            
            @media print {
                body, html {height:auto;}
                #content {height:100%;}
                #navigation{content:''; width:5%; flex:0 0 5%;}
                .nav-item {display:none;}
            }
        </style>
        <script data-ad-client="ca-pub-6268292462233201" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    </head>
    <body>
        <?php include("../template/navbar.php"); ?>
        <div id="content">
            <h2>VAPOR Users Guide<br />
            <span style="font-size:50%;">for release dated Feb 25, 2021<sup>*</sup></span></h2>
            <section>
                <p>Welcome to the documentation for <span class="bold">Project VAPOR</span>, a networking infrastructure that allows calculator users to quickly and easily manage the software on their devices, as well as connect to game servers without needing to physically install the dependencies through the usual manual procedure.</p>
            </section>
            <h3>Installation</h3>
            <section>
                <p>To install this program, sadly you will need to do it the old-fashioned way. Download <span class="bold">prgmVAPOR (VAPOR.8xp)</span> from either this website or the Cemetech/ticalc archives, and send it to your device. You will also need an assortment of the C libraries by MateoC present on your device including: FILEIOC, GRAPHX, SRLDRVCE, USBDRVCE, as well as a library of my own design, HASHLIB. Once you have all this software, you should be good to go!</p>
                <p>Run VAPOR for the first time by going to the Catalog menu (<span class="key">2nd</span> + <span class="key">0</span>), scrolling down to the <span class="italic">Asm(</span> token, and then pressing <span class="key">Enter</span>. This will paste that token onto the homescreen. Then press <span class="key">Prgm</span> to enter the Programs menu. Scroll down to VAPOR and then press <span class="key">Enter</span> again. You should now see <span class="italic">Asm(prgmVAPOR</span> on the homescreen. Now press <span class="key">Enter</span> for a third and final time.</p>
                <p><span class="italic">Fair warning: the first time you run VAPOR, it will appear to have frozen--it will seem to be doing nothing for a very long time. Do not be alarmed. It is merely computing an initial hash (SHA-1) for itself. Once this hash is taken, unless you lose the VAPOR library file (appvar VPRLibr), you should not experience such a long wait on launch again.</span></p>
            </section>
            <h3>Usage</h3>
            <section>
                <div class="embedded">
                    <div>Figure 1</div>
                    <img src="/template/gallery/doc_imgs/vapor_mainscreen.png" />
                </div>
                <p>VAPOR has two main menus and a number of hotkeys you can use. I made an effort to intuitively designate the primary interface by using the Arrow keys and the Y= through Graph keys to control primary components of the interface. But in the event the keybindings are needed for reference:</p>
                <div class="code">
                    <p><span class="key">Y=</span> Connect/Reconnect to VAPOR network</p>
                    <p><span class="key">Window</span> Scroll up within selected menu</p>
                    <p><span class="key">Zoom</span> Scroll down within selected menu</p>
                    <p><span class="key">&#8679;</span> Scroll up sidebar menu</p>
                    <p><span class="key">&#8681;</span> Scroll down sidebar menu</p>
                </div>
                <p>And that does it for the primary interface. Now each menu has two secondary options, which change depending on which of the two menus you are on. These are both bound to the <span class="key">Trace</span> and <span class="key">Graph</span> keys, respectively.</p>
                <div class="code">
                    <p><span class="key">Trace</span><br />
                    (LIBRARY TAB) Update all files in library<br />
                    (SERVICES TAB) Retrieve list of active services on VAPOR network</p>
                    <p><span class="key">Graph</span><br />
                    (LIBRARY TAB) Placeholder<br />
                    (SERVICES TAB) Connect to selected service<br />
                    &emsp;&emsp;&emsp;&emsp;<span class="italic">(at present only updates required dependencies)</span></p>
                </div>
            </section>
            <h3>VAPOR Network</h3>
            <section>
                <div class="embedded">
                    <div>Figure 2</div>
                    <img src="/template/gallery/doc_imgs/vapor_servicestab.png" />
                    <div>Figure 3</div>
                    <img src="/template/gallery/doc_imgs/vapor_librarytab.png" />
                </div>
                <p>The VAPOR network serves two main functions. Firstly, it hosts a number of arbitrary servers for calculator games (or other applications). This allows anyone who wants to make a multiplayer game, or provide a networked service for the TI-84+ CE, to do so without much work, and allows calculator users to connect to those services without much work on their part. Secondly, it acts as an update mirror for calculator software, similar to the package sources in apt, Homebrew, or any other flavor of package manager.</p>
                <p><span class="bold">Service Hosting (see Figure 2):</span> The Services hosted can be listed from the calculator by pressing the <span class="key">Trace</span> key while on the <span class="bold">Services</span> tab. Doing so requests that the server report any hosted services: the service name, host name, port number, and online state. Please note that if you are not actually connected to the VAPOR network, pressing the aforementioned key will do nothing. You can see your connection status on on the bottom left side of the screen, to the right of the Connect button. Consult the &quot;Servers&quot; tab in the sidebar to the left to see what Services we are currently hosting.</p>
                <p><span class="bold">Software Mirror (see Figure 3):</span> The VAPOR network also hosts files relevant to the Services that are hosted, as well as a frequently-updated download mirror for the CE C Libraries released by MateoC and licensed under LGPL-3.0. It also enables VAPOR to perform live self-updating if it detects there is a newer version of the VAPOR client. Update tracking is performed via SHA-1 hashes... when the calculator asks for a file update, it sends the hash of the current file. Should the hash of the file stored on the server differ, a file transfer is initiated, which terminates by providing the hash of the new file, which the device will log. It should be noted that all packages mirrored by VAPOR are analyzed periodically by file integrity software. Consult the &quot;Packages&quot; tab in the sidebar to the left to see what software is in our package lists.</p>
            </section>
            <h3>Known Bugs</h3>
            <section>
                <dl>
                    <dt>File Downloads are Slow</dt>
                    <dd>This is a caveat of the hashing that occurs as a file is being downloaded. At present, since HASHLIB was written in C, the SHA-1 and SHA-256 algorithms do take some time to run. A re-write of HASHLIB in full assembly that will likely be faster is underway, so just bear with us for now.</dd>
                    
                    <dt><del>File downloads can sporatically fail to complete<del></dt>
                    <dd>This issue was resolved as of v1.1, where the server now waits for a &quot;DATA_SEND_NEXT&quot; packet before continuing to send the next buffer-worth of file data.<br /><del>This occurs from time to time--the load bar will just stop moving but you won&apos;t get an error message. I have not quite worked out why this happens yet--there is a slight chance it could be a bug in the USB or SRL driver. If this occurs, it won&apos;t cause you any major problem, as we don&apos;t delete the older file until the file transfer succeeds and the hashes match. You will merely wind up with a stray temporary file on your device which will be cleaned right up with the next transfer that occurs. Press any key to remove the Download UI from the screen and re-start the download(s).</del></dd>
                </dl>
            </section>
            <h3>Acknowledgements</h3>
            <section>
                <dl>
                    <dt>Contributing Developers</dt>
                    <dd>
                    beckadamtheinventor - assembly routine to reload VAPOR after update<br />
                    beckadamtheinventor = assembly GetKey routine compatible with SRL driver<br />
                    
                    <dt>C CE Libraries</dt>
                    <dd>https://github.com/CE-Programming/toolchain<br />
                    source &amp; license referenced as per stipulations of LGPL-3.0</dd>
                <dl>
            </section>
            <h3>License</h3>
            <section>
                <dl>
                    <dt>Software License:</dt>
                    <dd>VAPOR client and server are distributed under the GPL-3.0 open source license, with the exception of any components that may be bound by other licenses. You are free to download, modify, and redistribute this software with the stipulation that modified clients may not be able to connect to the home instance of VAPOR&apos;s server. Any derived works must also inherit the licenses associated with them, as per GPL-3.0.</dd>
                    <dt>VAPOR Server EULA:</dt>
                    <dd>Members of the TI-84+ CE calculator hobbyist community (or general userbase) are welcome to use the VAPOR network for its herein-indicated purposes for as long as the VAPOR network is active, with the following stipulations:<br />
                    <ul>
                        <li>Any bugs or exploits that adversely affect the Server&apos;s functionality in any way should be reported as soon as possible to the Server administrator using the Contact information provided below.</li>
                        <li>Developers hosting services or software on the VAPOR network must ensure that they have the right to distribute the software they are posting. This means either it is software of their own making, or that it is derived from open source or public domain works, or under explicit licensing agreement.</li>
                        <li>Any hosted software derived from open source or public domain sources that you distribute on our network is distributed under the terms of the source license, regardless of any licenses you may elsewhere stipulate. This applies only to libraries and borrowed code, not to portions that are not derived.</li>
                    </ul>
                    </dd>
                </dl>
            </section>
            <h3>Contact</h3>
            <section>
                <p>I may be reached for questions, comments, concerns, complaints or anything else at the following locations:</p>
                Email: &#097;&#099;&#097;&#103;&#108;&#105;&#097;&#110;&#111;&#057;&#055;&#064;&#103;&#109;&#097;&#105;&#108;&#046;&#099;&#111;&#109;<br />
                Discord: &#097;&#099;&#097;&#103;&#108;&#105;&#097;&#110;&#111;#&#051;&#054;&#056;&#053;
                <br /><br />
            </section>
            <p class="p-footer">* I will update the content of this User Guide dynamically to detail more features as they become available. I encourage users of VAPOR to consult this document whenever there is an update to make sure nothing critical has changed. Where applicable, sections that are no longer applicable will still show up in the documentation with a strikethrough, followed by a note indicating what version the feature was removed or changed in.</p>
        </div>
    </body>
</html>
