<?php


$to = 'perljason@gmail.com';
$subject = 'HTML email test';

$headers = 'From: smgforms@bu.edu\r\n';
$headers .= 'MIME-Version: 1.0\r\n';
$headers .= 'Content-Type: text/html; charset=ISO-8859-1\r\n';
$message = '<html><body><h1>Test</h1></body></html>';

mail($to, $subject, $message, $headers);