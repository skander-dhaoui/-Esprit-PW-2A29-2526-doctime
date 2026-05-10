<?php
// config/mail.php

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailConfig {
    
    private static $smtpHost = 'smtp.gmail.com';
    private static $smtpPort = 587;
    private static $smtpUser = 'afnengorai@gmail.com';
    private static $smtpPass = 'amrgtpgoryfmvmai';
    private static $smtpSecure = 'tls';
    private static $fromEmail = 'afnengorai@gmail.com';
    private static $fromName = 'DocTime';

    private static function env(string $key, string $default): string {
        $value = getenv($key);
        return $value !== false && $value !== '' ? $value : $default;
    }
    
    public static function send($to, $toName, $subject, $body, $altBody = ''): bool {
        $mail = new PHPMailer(true);

        try {
            if (!filter_var((string) $to, FILTER_VALIDATE_EMAIL)) {
                error_log('Email non envoye: destinataire invalide [' . (string) $to . ']');
                return false;
            }

            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = self::env('MAIL_HOST', self::$smtpHost);
            $mail->SMTPAuth   = true;
            $mail->Username   = self::env('MAIL_USERNAME', self::$smtpUser);
            $mail->Password   = self::env('MAIL_PASSWORD', self::$smtpPass);
            $enc              = self::env('MAIL_ENCRYPTION', self::$smtpSecure);
            $mail->SMTPSecure = ($enc === 'tls' || $enc === 'ssl')
                ? (($enc === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS)
                : $enc;
            $mail->Port       = (int) self::env('MAIL_PORT', (string) self::$smtpPort);
            $mail->Timeout    = 15;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = '8bit';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $fromAddr = self::env('MAIL_FROM_ADDRESS', self::$fromEmail);
            $fromName = self::env('MAIL_FROM_NAME', self::$fromName);
            $mail->setFrom($fromAddr, $fromName);
            $mail->addReplyTo($fromAddr, $fromName);
            $mail->addAddress($to, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($body);

            error_log("Envoi d'email à: $to ($toName) - Sujet: $subject");
            $result = $mail->send();
            if ($result) {
                error_log("Email envoye avec succes à: $to");
            } else {
                error_log("Echec envoi email à: $to - " . $mail->ErrorInfo);
            }

            return $result;
        } catch (Exception $e) {
            error_log('Exception email: ' . $e->getMessage() . ' | ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Tester la connexion SMTP (diagnostic back-office / dev).
     *
     * @return array{success:bool, message:string, details:array<int,string>}
     */
    public static function testConnection(): array {
        $mail = new PHPMailer(true);
        $result = [
            'success' => false,
            'message' => '',
            'details' => [],
        ];

        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = self::env('MAIL_HOST', self::$smtpHost);
            $mail->SMTPAuth   = true;
            $mail->Username   = self::env('MAIL_USERNAME', self::$smtpUser);
            $mail->Password   = self::env('MAIL_PASSWORD', self::$smtpPass);
            $enc              = self::env('MAIL_ENCRYPTION', self::$smtpSecure);
            $mail->SMTPSecure = ($enc === 'tls' || $enc === 'ssl')
                ? (($enc === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS)
                : $enc;
            $mail->Port       = (int) self::env('MAIL_PORT', (string) self::$smtpPort);

            if ($mail->smtpConnect()) {
                $result['success'] = true;
                $result['message'] = 'Connexion SMTP reussie';
                $result['details'][] = 'Serveur: ' . self::env('MAIL_HOST', self::$smtpHost);
                $result['details'][] = 'Port: ' . self::env('MAIL_PORT', (string) self::$smtpPort);
                $mail->smtpClose();
            } else {
                $result['message'] = 'Impossible de se connecter au serveur SMTP';
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur de connexion: ' . $e->getMessage();
            if (!empty($mail->ErrorInfo)) {
                $result['details'][] = $mail->ErrorInfo;
            }
        }

        return $result;
    }
}
?>
