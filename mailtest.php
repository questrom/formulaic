<?php

require('vendor/autoload.php');
require('parts.php');

use Nette\Mail\Message;

$mail = new Message();
$mail->setFrom('Form Builder <perljason@gmail.com>')
    ->addTo('perljason@gmail.com')
    ->addTo('jhansel@bu.edu')
    ->setSubject('Hello world!')
    ->setHTMLBody('<b>SampleHTML</b>');

use Nette\Mail\SmtpMailer;

$mailer = new SmtpMailer([
    'host' => 'smtp.gmail.com',
    'username' => 'perljason@gmail.com',
    'password' => file_get_contents('password.txt'),
    'secure' => 'ssl'
]);

$mailer->send($mail);
