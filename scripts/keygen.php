<?php
    $loader = require 'vendor/autoload.php';
    // load ASN.1 extensions for keyfile generation
    use FG\ASN1\Universal\Integer;
    use FG\ASN1\Universal\Boolean;
    use FG\ASN1\Universal\PrintableString;
    use FG\ASN1\Universal\OctetString;
    use FG\ASN1\Universal\Sequence;

    require_once "ti_vars_lib/src/autoloader.php";

    use tivars\TIModel;
    use tivars\TIVarFile;
    use tivars\TIVarType;
    use tivars\TIVarTypes;

    if(isset($_POST["kf_emit"])){
        $kf_errors = array();
        $privkey = openssl_get_privatekey(file_get_contents(".secrets/privkey.pem"), $env["SSL_PASS"]);
        if($privkey){
            $token = hash("sha512", $_SESSION["id"].$_SESSION["secret"], true);
            openssl_sign($token, $signature, $privkey, openssl_get_md_methods()[14]);
            $asn1_user = new Integer($_SESSION["id"]);
            $asn1_signature = new OctetString(bin2hex($signature));
            $asn1_credentials = new Sequence($asn1_user, $asn1_signature);
            $asn1_hash = new OctetString(bin2hex(hash('sha256', $asn1_user->getBinary().$asn1_signature->getBinary(), true)));
            $asn1_encrypted = new Boolean(false);
            $asn1_complete = new Sequence($asn1_encrypted, $asn1_credentials, $asn1_hash);
            if(isset($_POST["kf_passphrase"]) && $_POST["kf_passphrase"]!=""){
                $asn1_encrypted = new Boolean(true);
                $nonce = random_bytes(32);
                $secrets=hash_pbkdf2("sha256", $_POST["kf_passphrase"], $nonce, 100, 48, true);
                $aes_key = substr($secrets, 0, 32);
                $aes_iv = substr($secrets, -16, 16);
                $ciphertext = openssl_encrypt($asn1_credentials->getBinary(), "AES-256-GCM", $aes_key, $options = OPENSSL_RAW_DATA, $aes_iv, $tag);
                if($ciphertext !== false){
                    $asn1_nonce = new OctetString(bin2hex($nonce));
                    $asn1_credentials = new OctetString(bin2hex($ciphertext));
                    $asn1_tag = new OctetString(bin2hex($tag));
                    $asn1_complete = new Sequence($asn1_encrypted, $asn1_nonce, $asn1_credentials, $asn1_tag);
                } else {
                    $kf_errors[] = "OpenSSL: Encryption error.";
                    exit();
                }
            }
            $asn1_raw_data = $asn1_complete->getBinary();
            $fname = filter_input(INPUT_POST, 'kf_name', FILTER_SANITIZE_STRING);
            $tfile = "/tmp/". uniqid(rand(), true);
            $binfile = $tfile.".bin";
            $appvfile = $tfile.".8xv";
            $f = fopen($binfile, 'wb+');
            if($f){
                fwrite($f, "\x54\x49\x41\x55\x54\x48".$asn1_raw_data);
                fclose($f);
            } else {
                $kf_errors[] = "Binary IO error.";
                exit();
            }
            $output = shell_exec("convbin/bin/convbin -i $binfile -j bin -o $appvfile -k 8xv -n $fname 2>&1");
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