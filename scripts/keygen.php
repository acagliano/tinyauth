<?php
    $loader = require 'vendor/autoload.php';
    // load ASN.1 extensions for keyfile generation
    use FG\ASN1\Universal\Integer;
    use FG\ASN1\Universal\Boolean;
    use FG\ASN1\Universal\PrintableString;
    use FG\ASN1\Universal\OctetString;
    use FG\ASN1\Universal\ObjectIdentifier;
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
            $key_identifier = "TInyAuthKF";
            $asn1_user = new PrintableString($_SESSION["user"]);
            $asn1_signature = new OctetString(bin2hex($signature));
            $asn1_credentials = new Sequence($asn1_user, $asn1_signature);
            $asn1_encrypted = new Boolean(false);
            $asn1_complete = new Sequence($asn1_identifier, $asn1_encrypted, $asn1_credentials);
            if(isset($_POST["kf_passphrase"])){
                $asn1_encrypted = new Boolean(true);
                $nonce = random_bytes(32);
                $secrets=hash_pbkdf2("sha256", $_POST["kf_passphrase"], $nonce, 1000, 48, true);
                $aes_key = substr($secrets, 0, 32);
                $aes_iv = substr($secrets, -16, 16);
                $ciphertext = openssl_encrypt($asn1_credentials->getBinary(), "AES-256-GCM", $aes_key, $options = OPENSSL_RAW_DATA, $aes_iv, $tag);
                if($ciphertext !== false){
                    $asn1_nonce = new OctetString(bin2hex($nonce));
                    $asn1_credentials = new OctetString(bin2hex($ciphertext));
                    $asn1_complete = new Sequence($asn1_identifier, $asn1_encrypted, $asn1_nonce, $asn1_credentials);
                } else {
                    $kf_errors[] = "OpenSSL: Encryption error.";
                }
            }
            $asn1_raw_data = $key_identifier.$asn1_complete->getBinary();
            $fname = filter_input(INPUT_POST, 'kf_name', FILTER_SANITIZE_STRING);
            mkdir("tmp");
            $tfile = "/tmp/". uniqid(rand(), true);
            $ti_file_handle = TIVarFile::createNew(TIVarType::createFromName('AppVar'), $fname, TIModel::createFromName('83PCE'));
            $ti_var_data = unpack('C*',$asn1_raw_data);
            $ti_file_handle->setContentFromData($ti_var_data);
            $ti_file_handle->saveVarToFile("/tmp", basename($tfile));
            if(file_exists($tfile.".8xv")){
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.$fname.'.8xv"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($tfile.".8xv"));
                ob_clean();
                flush();
                echo file_get_contents($tfile.".8xv");
                exit;
                unlink($tfile.".8xv");
            } else {
                $kf_errors[] = "Error creating download.";
            }
        } else {
            $kf_errors[] = "OpenSSL: Error opening signing key.";
        }
    }
?>