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
            $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Désactiver en production
            $mail->isSMTP();
            $mail->Host       = self::env('MAIL_HOST', self::$smtpHost);
            $mail->SMTPAuth   = true;
            $mail->Username   = self::env('MAIL_USERNAME', self::$smtpUser);
            $mail->Password   = self::env('MAIL_PASSWORD', self::$smtpPass);
            $mail->SMTPSecure = self::env('MAIL_ENCRYPTION', self::$smtpSecure);
            $mail->Port       = (int)self::env('MAIL_PORT', (string)self::$smtpPort);
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom(self::env('MAIL_FROM_ADDRESS', self::$fromEmail), self::env('MAIL_FROM_NAME', self::$fromName));
            $mail->addAddress($to, $toName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur d'envoi email: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>
