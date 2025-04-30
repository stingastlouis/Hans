<?php
$to = 'stingastlouis@gmail.com';
$subject = 'Test Email from cPanel';


include 'email_template.php';

$messageBody = "
    <h2>Hi Sarah,</h2>
    <p>Thank you for purchasing from GlowLights. Your order is being processed and will be delivered within 3-5 days.</p>
    <p>Here's a summary of your order:</p>
    <ul>
        <li>1x Elegant LED Lamp – Rs 1,199</li>
        <li>2x Smart RGB Bulb – Rs 1,798</li>
    </ul>
    <p>Total: <strong>Rs 2,997</strong></p>
    <p>We'll send another email once your items are shipped.</p>
    <p style='margin-top: 20px;'>Stay bright ✨, <br> The GlowLights Team</p>
";

$finalEmail = renderEmailTemplate($messageBody);

$headers  = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: Light Store <no-reply@inkovscl@inkosi.africa>" . "\r\n";

if (mail($to, $subject, $finalEmail, $headers)) {
    echo "Email sent!";
} else {
    echo "Failed to send.";
}
?>
