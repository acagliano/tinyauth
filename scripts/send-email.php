<?php

require '../vendor/autoload.php';
$env = parse_ini_file('../.env');
$email = $argv[1];
$subject = $argv[2];
$htmlcontent = file_get_contents($argv[3]);

$sendgrid = new SendGrid($env['SENDGRID_API_KEY']);
$email_obj = new SendGrid\Mail\Mail();
$email_obj->setFrom("admin@cagstech.com");
$email_obj->setSubject($subject);
$email_obj->addTo($email);
$email_obj->addContent("text/html", $htmlcontent);
$sendgrid->send($email_obj);

?>