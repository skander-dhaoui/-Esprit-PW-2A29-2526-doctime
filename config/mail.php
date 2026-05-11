<?php
// config/mail.php — lit les variables MAIL_* du fichier .env (via config/env.php)

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailConfig
{
    /** @return array{host: string, port: int, user: string, pass: string, from: string, fromName: string, secure: string} */
    private static function smtpSettings(): array
    {
        $host = trim((string) (getenv('MAIL_HOST') ?: ''));
        $user = trim((string) (getenv('MAIL_USERNAME') ?: ''));
        $pass = trim((string) (getenv('MAIL_PASSWORD') ?: ''));
        $from = trim((string) (getenv('MAIL_FROM') ?: ''));
        if ($from === '') {
            $from = $user;
        }

        $port = (int) (getenv('MAIL_PORT') ?: 587);
        if ($port <= 0) {
            $port = 587;
        }

        $secure = strtolower(trim((string) (getenv('MAIL_ENCRYPTION') ?: 'tls')));
        if ($secure !== 'ssl' && $secure !== 'tls') {
            $secure = 'tls';
        }

        if ($host === '') {
            $host = 'smtp.gmail.com';
        }

        return [
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'pass'     => $pass,
            'from'     => $from,
            'fromName' => trim((string) (getenv('MAIL_FROM_NAME') ?: 'Valorys')),
            'secure'   => $secure,
        ];
    }

    public static function isConfigured(): bool
    {
        $c = self::smtpSettings();

        return $c['user'] !== '' && $c['pass'] !== '' && $c['from'] !== '';
    }

    /**
     * @throws Exception
     */
    public static function send(string $to, string $toName, string $subject, string $body, string $altBody = ''): bool
    {
        $cfg = self::smtpSettings();

        if ($cfg['user'] === '' || $cfg['pass'] === '' || $cfg['from'] === '') {
            error_log('MailConfig: MAIL_USERNAME / MAIL_PASSWORD / MAIL_FROM manquants dans .env — e-mail non envoyé.');
            if (class_exists('AppLogger', false)) {
                AppLogger::log('mail', 'send_skipped_missing_env', []);
            }
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['user'];
            $mail->Password   = $cfg['pass'];
            $mail->SMTPSecure = $cfg['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $cfg['port'];

            $mail->setFrom($cfg['from'], $cfg['fromName']);
            $mail->addAddress($to, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('MailConfig envoi échoué: ' . $mail->ErrorInfo . ' | ' . $e->getMessage());
            if (class_exists('AppLogger', false)) {
                AppLogger::log('mail', 'smtp_send_failed', ['error' => $mail->ErrorInfo]);
            }
            return false;
        }
    }
}
