<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// Créer un utilisateur test s'il n'existe pas
$testEmail = 'test@test.fr';
$testPassword = 'Test1234';

// Vérifier si l'utilisateur existe
$checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$checkStmt->execute([':email' => $testEmail]);
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo "<h2>✓ Utilisateur test existe déjà</h2>";
    echo "Email: " . $testEmail . "<br>";
    echo "Mot de passe: " . $testPassword . "<br>";
} else {
    echo "<h2>Création d'un utilisateur test</h2>";
    
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    
    $insertStmt = $db->prepare("
        INSERT INTO users (nom, prenom, email, telephone, password, role, statut, created_at)
        VALUES (:nom, :prenom, :email, :telephone, :password, :role, :statut, NOW())
    ");
    
    $insertStmt->execute([
        ':nom' => 'Test',
        ':prenom' => 'User',
        ':email' => $testEmail,
        ':telephone' => '0600000000',
        ':password' => $hashedPassword,
        ':role' => 'patient',
        ':statut' => 'actif'
    ]);
    
    echo "✓ Utilisateur créé avec succès!<br>";
    echo "Email: " . $testEmail . "<br>";
    echo "Mot de passe: " . $testPassword . "<br>";
}

// Vérifier la création
$verifyStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email");
$verifyStmt->execute([':email' => $testEmail]);
$result = $verifyStmt->fetch(PDO::FETCH_ASSOC);

echo "<br><h3>Vérification: " . ($result['count'] > 0 ? "✓ OK" : "✗ FAILED") . "</h3>";
echo "Vous pouvez maintenant vous connecter avec:<br>";
echo "Email: <strong>" . $testEmail . "</strong><br>";
echo "Mot de passe: <strong>" . $testPassword . "</strong><br>";
?>
