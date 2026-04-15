<?php
echo "<h1>Test PHPMailer</h1>";

// Vérifier si le dossier PHPMailer existe
$phpmailer_path = __DIR__ . '/PHPMailer/src/PHPMailer.php';

if (!file_exists($phpmailer_path)) {
    echo "❌ PHPMailer non trouvé à : " . $phpmailer_path . "<br>";
    echo "Vérifiez que PHPMailer est installé dans : " . __DIR__ . "/PHPMailer/src/<br>";
    exit;
}

echo "✅ PHPMailer trouvé !<br>";

// Inclure les fichiers
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// Utiliser les classes (sans "use" dans ce contexte)
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$smtp = PHPMailer\PHPMailer\SMTP::class;

try {
    // Configuration du serveur
    $mail->SMTPDebug = 2;  // Niveau de debug (2 = afficher tout)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'afnengorai@gmail.com';
    $mail->Password   = 'amrgtpgoryfmvmai';  // Votre mot de passe d'application
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    
    // Expéditeur et destinataire
    $mail->setFrom('afnengorai@gmail.com', 'DocTime');
    $mail->addAddress('afnengorai@gmail.com', 'Afnen Gorai');
    
    // Contenu de l'email
    $mail->isHTML(true);
    $mail->Subject = 'Test DocTime';
    $mail->Body    = '<h1 style="color:#2A7FAA;">✅ Succès !</h1>
                      <p>Votre configuration email fonctionne parfaitement.</p>
                      <p>Vous pouvez maintenant envoyer des emails depuis DocTime.</p>
                      <hr>
                      <p><strong>Date :</strong> ' . date('d/m/Y H:i:s') . '</p>';
    $mail->AltBody = 'Test DocTime - Votre configuration email fonctionne.';
    
    $mail->send();
    echo "<br><span style='color:green;font-weight:bold;'>✅ Email envoyé avec succès !</span><br>";
    echo "Vérifiez votre boîte de réception : afnengorai@gmail.com";
    
} catch (Exception $e) {
    echo "<br><span style='color:red;font-weight:bold;'>❌ Erreur: </span><br>";
    echo $mail->ErrorInfo;
}
?>