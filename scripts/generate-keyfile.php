<?php
    use FG\ASN1\Universal\Integer;
    use FG\ASN1\Universal\OctetString;
    use FG\ASN1\Universal\Sequence;
    require $_SERVER["DOCUMENT_ROOT"].'/vendor/autoload.php';

    use tivars\TIModel;
    use tivars\TIVarFile;
    use tivars\TIVarType;
    use tivars\TIVarTypes;
    require_once $_SERVER["DOCUMENT_ROOT"]."/ti_vars_lib/src/autoloader.php";

    if(isset($_POST["generate-calc-auth"])){
        $kf_errors = array();
        $privkey_file = $_SERVER["DOCUMENT_ROOT"]."/.secrets/privkey.pem";
        $privkey = openssl_get_privatekey(file_get_contents($privkey_file), $env["SSL_PASS"]);
        if($privkey){
            openssl_sign($_SESSION["id"].$_SESSION["api_secret"], $signature, $privkey, openssl_get_md_methods()[14]);
            $asn1_userid = new Integer($_SESSION["id"]);
            $asn1_usertoken = new OctetString(bin2hex($signature));
            $asn1_keydata = new Sequence($asn1_userid, $asn1_usertoken);
            $asn1_raw = $asn1_keydata->getBinary();
            $tfile = "/tmp/". uniqid(rand(), true);
            $binfile = $tfile.".bin";
            $appvfile = $tfile.".8xv";
            $fname = "TInyKF";
            $f = fopen($binfile, 'wb+');
            if($f){
                fwrite($f, "\x54\x49\x41\x55\x54\x48".$asn1_raw);
                fclose($f);
            } else {
                $kf_errors[] = "Binary IO error.";
                exit();
            }
            $output = shell_exec($_SERVER["DOCUMENT_ROOT"]."/convbin/bin/convbin -i $binfile -j bin -o $appvfile -k 8xv -n $fname 2>&1");
            error_log($output);
            if(file_exists($appvfile)){
                ob_clean();
                header_remove();
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$fname.'.8xv"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($appvfile));
                ob_clean();
                flush();
                echo file_get_contents($appvfile);
                unlink($binfile);
                unlink($appvfile);
                exit();
            } else {
                $kf_errors[] = "Error creating download.";
            }
        } else {
            $kf_errors[] = "OpenSSL: Error opening signing key.";
        }
    }
?>