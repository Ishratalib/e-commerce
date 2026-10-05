<?php

require_once '../core/Mailer.php';

$to = 'ishuni098@gmail.com';

$mailer = new Mailer();

$subject = 'NovaWear Email Test';

$body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Test</title>
</head>
<body>

    <h2>Email Test Successful</h2>

    <p>This is a test email from your e-commerce website.</p>

    <p>If you received this email, SMTP and PHPMailer are working correctly.</p>

    <p>Regards,<br>
    NovaWear</p>

</body>
</html>
';

if ($mailer->send($to, $subject, $body)) {

    echo '<h2 style="color: green;">Email sent successfully!</h2>';

} else {

    echo '<h2 style="color: red;">Email failed to send.</h2>';

}