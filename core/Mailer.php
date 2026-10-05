<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer
{
    private PHPMailer $mail;

    public function __construct()
    {
        $smtp = require __DIR__ . '/../config/smtp.php';

        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();

        $this->mail->Host = $smtp['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $smtp['username'];
        $this->mail->Password = $smtp['password'];

        $this->mail->SMTPSecure = $smtp['encryption'];
        $this->mail->Port = $smtp['port'];

        $this->mail->setFrom(
            $smtp['from_email'],
            $smtp['from_name']
        );

        $this->mail->isHTML(true);

        $this->mail->CharSet = 'UTF-8';
    }

    public function send(
        string $to,
        string $subject,
        string $body
    ): bool {

        try {

            $this->mail->clearAddresses();

            $this->mail->addAddress($to);

            $this->mail->Subject = $subject;

            $this->mail->Body = $body;

            $this->mail->AltBody =
                strip_tags($body);

            return $this->mail->send();

        } catch (Exception $e) {

            return false;
        }
    }
}