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
    private static $smtpPass = 'amrgtpgoryfmvmai';  // Mot de passe d'application Gmail
    private static $smtpSecure = 'tls';
    private static $fromEmail = 'afnengorai@gmail.com';
    private static $fromName = 'DocTime';
    
    /**
     * Envoyer un email
     */
    public static function send($to, $toName, $subject, $body, $altBody = ''): bool {
        $mail = new PHPMailer(true);
        
        try {
            if (!filter_var((string) $to, FILTER_VALIDATE_EMAIL)) {
                error_log("Email non envoye: destinataire invalide [" . (string) $to . "]");
                return false;
            }
            // Désactiver DEBUG pour éviter d'afficher avant les headers
            // Les erreurs sont loggées dans error_log
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            
            $mail->isSMTP();
            $mail->Host       = self::$smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$smtpUser;
            $mail->Password   = self::$smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::$smtpPort;
            $mail->Timeout    = 15;
            
            // Configuration pour Gmail
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = '8bit';
            
            // Options SSL (optionnel pour certains serveurs)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];
            
            $mail->setFrom(self::$fromEmail, self::$fromName);
            $mail->addReplyTo(self::$fromEmail, self::$fromName);
            $mail->addAddress($to, $toName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            
            // Log
            error_log("📧 Envoi d'email à: $to ($toName) - Sujet: $subject");
            
            $result = $mail->send();
            
            if ($result) {
                error_log("✅ Email envoyé avec succès à: $to");
            } else {
                error_log("❌ Échec envoi email à: $to - Erreur: " . $mail->ErrorInfo);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("❌ Exception email: " . $e->getMessage());
            error_log("   To: $to");
            error_log("   SMTP Host: " . self::$smtpHost . ":" . self::$smtpPort);
            error_log("   File: " . $e->getFile() . ":" . $e->getLine());
            return false;
        }
    }
    
    /**
     * Tester la connexion SMTP
     */
    public static function testConnection(): array {
        $mail = new PHPMailer(true);
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = self::$smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = self::$smtpUser;
            $mail->Password   = self::$smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = self::$smtpPort;
            
            // Tester la connexion
            if ($mail->smtpConnect()) {
                $result['success'] = true;
                $result['message'] = 'Connexion SMTP reussie';
                $result['details'][] = "Serveur: " . self::$smtpHost;
                $result['details'][] = "Port: " . self::$smtpPort;
                $result['details'][] = "Utilisateur: " . self::$smtpUser;
                $mail->smtpClose();
            } else {
                $result['message'] = 'Impossible de se connecter au serveur SMTP';
            }
        } catch (Exception $e) {
            $result['message'] = 'Erreur de connexion: ' . $e->getMessage();
            $result['details'][] = "Erreur SMTP: " . $mail->ErrorInfo;
        }
        
        return $result;
    }
}
