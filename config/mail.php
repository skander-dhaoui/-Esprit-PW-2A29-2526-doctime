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
    private static $smtpUser = 'ihebbouzgarrou21@gmail.com';
    private static $smtpPass = 'Ihebbouzgarrou69';
    private static $smtpSecure = 'tls';
    private static $fromEmail = 'ihebbouzgarrou21@gmail.com';
    private static $fromName = 'DocTime';
    
    public static function send($to, $toName, $subject, $body, $altBody = ''): bool {
        $mail = new PHPMailer(true);
        
        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Désactiver en production
            $mail->isSMTP();
            $mail->Host       = self::$smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$smtpUser;
            $mail->Password   = self::$smtpPass;
            $mail->SMTPSecure = self::$smtpSecure;
            $mail->Port       = self::$smtpPort;
            
            $mail->setFrom(self::$fromEmail, self::$fromName);
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
?>// update
