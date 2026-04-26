<?php
/**
 * Script de diagnostic pour l'authentification admin
 * Pour exécuter: http://localhost/valorys_Copie/debug_admin.php
 */

// Configuration
$host = 'localhost';
$dbname = 'doctime_db';
$user = 'root';
$pass = '';

echo '<h2>Diagnostic - Vérification du compte Admin</h2>';
echo '<style>body { font-family: Arial; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f4f4f4; padding: 10px; }</style>';

try {
    // Connexion à la BD
    echo '<p class="info">1️⃣ Connexion à la base de données...</p>';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '<p class="success">✓ Connecté avec succès</p>';

    // Vérifier si l'admin existe
    echo '<p class="info">2️⃣ Recherche du compte admin@doctime.com...</p>';
    $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role, statut FROM users WHERE email = ?");
    $stmt->execute(['admin@doctime.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo '<p class="error">❌ Le compte admin n\'existe PAS dans la base de données!</p>';
        echo '<p class="info">Création du compte admin...</p>';
        
        // Créer l'admin avec le mot de passe hashé
        $hashedPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role, statut) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'System',
            'Admin',
            'admin@doctime.com',
            $hashedPassword,
            'admin',
            'actif'
        ]);
        echo '<p class="success">✓ Compte admin créé avec succès!</p>';
        
        // Récupérer le nouvel admin
        $stmt = $pdo->prepare("SELECT id, nom, prenom, email, role, statut FROM users WHERE email = ?");
        $stmt->execute(['admin@doctime.com']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo '<p class="success">✓ Compte admin trouvé!</p>';
    }

    // Afficher les détails
    echo '<p class="info">3️⃣ Détails du compte admin:</p>';
    echo '<pre>';
    echo "ID: " . $admin['id'] . "\n";
    echo "Nom: " . $admin['nom'] . "\n";
    echo "Prénom: " . $admin['prenom'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Rôle: " . $admin['role'] . "\n";
    echo "Statut: " . $admin['statut'] . "\n";
    echo '</pre>';

    // Vérifier le mot de passe
    echo '<p class="info">4️⃣ Vérification du mot de passe (admin123)...</p>';
    $testPassword = 'admin123';
    
    // Récupérer le hash du mot de passe
    $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->execute(['admin@doctime.com']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $passwordHash = $result['password'];
    
    echo '<pre>';
    echo "Hash en BD: " . $passwordHash . "\n";
    echo '</pre>';
    
    if (password_verify($testPassword, $passwordHash)) {
        echo '<p class="success">✓ Le mot de passe "admin123" est correct!</p>';
    } else {
        echo '<p class="error">❌ Le mot de passe "admin123" est INCORRECT!</p>';
        echo '<p class="info">Le hash du mot de passe semble corrompu. Réinitialisation...</p>';
        
        // Recréer le hash
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$newHash, 'admin@doctime.com']);
        echo '<p class="success">✓ Mot de passe réinitialisé! Utilisez "admin123"</p>';
    }

    // Instructions
    echo '<hr>';
    echo '<h3>✅ Vous pouvez maintenant vous connecter avec:</h3>';
    echo '<pre>';
    echo "Email: admin@doctime.com\n";
    echo "Mot de passe: admin123\n";
    echo '</pre>';
    echo '<p>Allez à: <a href="index.php?page=login">Page de connexion</a></p>';
    echo '<p class="info"><strong>Important:</strong> Vous recevrez un code 2FA par email (vérifiez votre boîte mail et dossier spam)</p>';

} catch (PDOException $e) {
    echo '<p class="error">❌ Erreur BD: ' . htmlspecialchars($e->getMessage()) . '</p>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<hr>';
echo '<p><a href="debug_admin.php">Rafraîchir</a> | <a href="index.php">Retour à l\'accueil</a></p>';
?>
