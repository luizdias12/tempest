<?php

namespace App\Service;

use App\Core\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class MailService
{
    private static function env(string $key, string $default = ''): string
    {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }

    public static function enviar(string $para, string $assunto, string $corpoHtml): bool
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = self::env('MAIL_HOST');
            $mail->SMTPAuth = self::env('MAIL_AUTH', 'true') === 'true';
            $mail->Username = self::env('MAIL_USERNAME');
            $mail->Password = self::env('MAIL_PASSWORD');
            $mail->Port = (int) self::env('MAIL_PORT', '587');
            $mail->CharSet = 'UTF-8';

            $encryption = strtolower(self::env('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(self::env('MAIL_FROM', $mail->Username), self::env('MAIL_FROM_NAME', 'Helpdesk'));
            $mail->addAddress($para);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $corpoHtml;

            return $mail->send();
        } catch (Throwable $e) {
            Logger::exception($e, ['para' => $para, 'assunto' => $assunto]);
            return false;
        }
    }
}
