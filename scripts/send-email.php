<?php
$env = parse_ini_file($_SERVER["TINYAUTH_ROOT"].".env");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require $_SERVER["TINYAUTH_ROOT"]."vendor/autoload.php";

function send_email($to, $subject, $body, $isHTML=true){

    $mail = new PHPMailer(true);
    $mail->isSMTP();

    // SMTP connection settings
    $mail->Host = 'smtp.mail.me.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'acagliano91@icloud.com';
    $mail->Password = $env["SMTP_PASSWD"];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email headers config
    $mail->setFrom("noreply@cagstech.com");

    // Recipient(s)
    if(is_array($to)){
        foreach($to as $recip){
            $mail->addAddress($recip);
        }
    }
    else { $mail->addAddress($to); }

    // Message content
    $mail->isHTML($isHTML);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $gpg = gnupg_init();
    

    // send mail, log any errors
    if (!$mail->send()) {error_log($mail->ErrorInfo);}
}

?>