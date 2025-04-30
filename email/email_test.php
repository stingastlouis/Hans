<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require '../mailer/src/Exception.php';
require '../mailer/src/PHPMailer.php';
require '../mailer/src/SMTP.php';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();                                // Send using SMTP
    $mail->Host       = 'mail.inkosi.africa';       // Your mail server (check cPanel email section)
    $mail->SMTPAuth   = true;                        // Enable SMTP authentication
    $mail->Username   = '_mainaccount@inkosi.africa';   // SMTP username
    $mail->Password   = 'loveByG1$';      // SMTP password
    $mail->SMTPSecure = 'ssl';                      // Encryption ('tls' or 'ssl')
    $mail->Port       = 465;                         // Port for SSL (587 for TLS)

    // Recipients
    $mail->setFrom('_mainaccount@inkosi.africa', 'Inkosi App');
    $mail->addAddress('stingastlouis@gmail.com', 'Sting');     // Recipient

    // Content
    $mail->isHTML(true);                             // Set email format to HTML
    $mail->Subject = 'Test Email via SMTP key';
    $mail->Body    = 'This is a <b>test email</b> sent via SMTP from cPanel.';
    $mail->AltBody = 'This is a plain-text version of the email content';

    $mail->send();
    echo 'Email sent successfully';
} catch (Exception $e) {
    echo "Failed to send email. Error: {$mail->ErrorInfo}";
}
?>
