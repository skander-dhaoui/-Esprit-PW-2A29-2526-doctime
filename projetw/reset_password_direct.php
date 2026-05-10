<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Diagnostic complet: Mot de passe oublié</h2>\n\n";

$db = Database::getInstance()->getConnection();

// Récupérer l'utilisateur afnengorai@gmail.com
$stmt = $db->prepare("
    SELECT id, email, nom, prenom, reset_token, reset_expires,
           CASE WHEN reset_token IS NOT NULL THEN 'OUI' ELSE 'NON' END as a_un_token,
           CASE WHEN reset_expires > NOW() THEN 'VALIDE' ELSE 'EXPIRÉ' END as token_status
    FROM users 
    WHERE email = :email
");
$stmt->execute([':email' => 'afnengorai@gmail.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ Utilisateur afnengorai@gmail.com non trouvé en base de données\n";
    exit;
}

echo "<h3>Informations utilisateur:</h3>\n";
echo "<pre style='background:#f5f5f5;padding:10px;border-radius:5px;'>\n";
echo "Email: " . htmlspecialchars($user['email']) . "\n";
echo "Nom: " . htmlspecialchars($user['nom'] . ' ' . $user['prenom']) . "\n";
echo "ID: " . $user['id'] . "\n";
echo "\nToken de réinitialisation:\n";
echo "  - Existe: " . $user['a_un_token'] . "\n";
echo "  - Status: " . $user['token_status'] . "\n";

if ($user['reset_token']) {
    echo "  - Token: " . $user['reset_token'] . "\n";
    echo "  - Expires: " . $user['reset_expires'] . "\n";
    
    // Calculer le temps restant
    $expiresTime = strtotime($user['reset_expires']);
    $nowTime = time();
    $remaining = $expiresTime - $nowTime;
    
    if ($remaining > 0) {
        $hours = floor($remaining / 3600);
        $minutes = floor(($remaining % 3600) / 60);
        echo "  - Temps restant: " . $hours . "h " . $minutes . "m\n";
        
        // Générer le lien de réinitialisation
        $resetLink = 'http://localhost/valorys_Copie/index.php?page=reset_password&token=' . $user['reset_token'];
        echo "\n✅ Le token est valide!\n";
        echo "Lien de réinitialisation:\n";
        echo "<a href='" . htmlspecialchars($resetLink) . "'>" . htmlspecialchars($resetLink) . "</a>\n";
    } else {
        echo "  ⚠️ Le token a expiré\n";
    }
} else {
    echo "  ❌ Aucun token trouvé (le formulaire n'a pas été soumis)\n";
}

echo "</pre>\n";

// Vérifier si l'email aurait dû être envoyé
echo "\n<h3>Prochaines étapes:</h3>\n";
if ($user['a_un_token'] === 'OUI' && $user['token_status'] === 'VALIDE') {
    echo "✅ Le token est valide. Vous pouvez cliquer sur le lien ci-dessus pour réinitialiser votre mot de passe.\n\n";
    
    // Afficher le formulaire de réinitialisation directement
    echo "<h3>Ou entrez votre nouveau mot de passe ici:</h3>\n";
    echo "<form method='POST' action='index.php?page=reset_password' style='background:#f5f5f5;padding:20px;border-radius:5px;max-width:400px;'>\n";
    echo "  <input type='hidden' name='token' value='" . htmlspecialchars($user['reset_token']) . "'>\n";
    echo "  <div style='margin-bottom:15px;'>\n";
    echo "    <label style='display:block;margin-bottom:5px;'>Nouveau mot de passe:</label>\n";
    echo "    <input type='password' name='password' placeholder='Minimum 6 caractères' required style='width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;'>\n";
    echo "  </div>\n";
    echo "  <div style='margin-bottom:15px;'>\n";
    echo "    <label style='display:block;margin-bottom:5px;'>Confirmer mot de passe:</label>\n";
    echo "    <input type='password' name='password_confirmation' placeholder='Confirmez votre mot de passe' required style='width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;'>\n";
    echo "  </div>\n";
    echo "  <button type='submit' style='background:#4CAF50;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;width:100%;'>Réinitialiser mon mot de passe</button>\n";
    echo "</form>\n";
} else if ($user['a_un_token'] === 'OUI' && $user['token_status'] === 'EXPIRÉ') {
    echo "⚠️ Le token a expiré. Vous devez refaire une demande de réinitialisation.\n";
    echo "<a href='index.php?page=forgot_password' style='display:inline-block;background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;margin-top:10px;'>Nouveau lien de réinitialisation</a>\n";
} else {
    echo "❌ Aucun token trouvé. Le formulaire 'mot de passe oublié' n'a probablement pas été soumis.\n";
    echo "<a href='index.php?page=forgot_password' style='display:inline-block;background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;margin-top:10px;'>Allez au formulaire de réinitialisation</a>\n";
}
?>
