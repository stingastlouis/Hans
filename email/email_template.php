<?php
function renderEmailTemplate($messageBody) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>GlowLights Email</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .header { background: #ffe135; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 26px; color: #222; }
            .content { padding: 20px; color: #333; font-size: 15px; line-height: 1.6; }
            .footer { background: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>GlowLights ✨</h1>
                <p style="margin: 0;">Brighten Your World</p>
            </div>
            <div class="content">
                ' . $messageBody . '
            </div>
            <div class="footer">
                &copy; ' . date("Y") . ' GlowLights. All rights reserved.<br>
                123 Bright Avenue, LightCity, Mauritius
            </div>
        </div>
    </body>
    </html>';
}
?>
