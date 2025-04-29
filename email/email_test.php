<?php
$to = 'stingastlouis@gmail.com';
$subject = 'Test Email from cPanel';
$message = 'Hello, this is a test email.';
$headers = 'From: inkovscl@inkosi.africa' . "\r\n" .
           'Reply-To: inkovscl@inkosi.africa' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully.";
} else {
    echo "Failed to send email.";
}


?>