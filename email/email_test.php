<?php
var_dump(function_exists('mail'));
$to = 'stingastlouis@gmail.com';
$subject = 'Test Email from cPanel';
$message = 'Hello, this is a test email.';
$headers = 'From: inkovscl@inkosi.africa';


if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully.";
} else {
    echo "Failed to send email.";
}


?>