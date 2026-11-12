<?php
class MailDeliveryException extends RuntimeException {}

final class Mailer {
    public static function sendVerification(string $recipient, string $code): void {
        $config = require __DIR__ . '/../config/mail.php';
        $sender = trim($config['username']);
        $password = preg_replace('/\s+/', '', $config['app_password']);
        if (!filter_var($sender, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $sender) || strlen($password) !== 16) {
            throw new MailDeliveryException('Email delivery is not configured. Please contact the administrator.');
        }
        if (!extension_loaded('curl') || !in_array('smtp', curl_version()['protocols'], true)) {
            throw new MailDeliveryException('Email delivery is unavailable. Please contact the administrator.');
        }
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $recipient) || !preg_match('/^\d{6}$/D', $code)) {
            throw new MailDeliveryException('Unable to send a verification code to this email address.');
        }
        $body = "Your DCSS Meeting Scheduler verification code is:\r\n\r\n{$code}\r\n\r\n"
            . "This code expires in 10 minutes. Only the most recent code will work.\r\n"
            . "If you did not create an account, you can ignore this email.\r\n";
        $message = 'Date: ' . date(DATE_RFC2822) . "\r\n"
            . "From: DCSS Meeting Scheduler <{$sender}>\r\nTo: <{$recipient}>\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($sender, '@'), 1) . ">\r\n"
            . "Subject: Verify your DCSS email address\r\nMIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($body);
        $stream = fopen('php://temp', 'w+');
        $curl = curl_init();
        try {
            fwrite($stream, $message);
            rewind($stream);
            curl_setopt_array($curl, [
                CURLOPT_URL => 'smtp://smtp.gmail.com:587',
                CURLOPT_USE_SSL => CURLUSESSL_ALL,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERNAME => $sender,
                CURLOPT_PASSWORD => $password,
                CURLOPT_LOGIN_OPTIONS => 'AUTH=LOGIN',
                CURLOPT_MAIL_FROM => '<' . $sender . '>',
                CURLOPT_MAIL_RCPT => ['<' . $recipient . '>'],
                CURLOPT_UPLOAD => true,
                CURLOPT_INFILE => $stream,
                CURLOPT_INFILESIZE => strlen($message),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 25,
            ]);
            if (curl_exec($curl) === false) {
                // Log the transport error number, never credentials, codes or SMTP transcripts.
                error_log('Gmail SMTP delivery failed; curl error ' . curl_errno($curl));
                throw new MailDeliveryException('We could not send your verification email. Please try resending the code shortly. If this continues, contact the administrator.');
            }
        } finally {
            curl_close($curl);
            fclose($stream);
        }
    }
}
