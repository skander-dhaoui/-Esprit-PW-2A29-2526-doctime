<?php
echo "<h1>Test Simple</h1>";

// Test 1: PHP fonctionne
echo "<p>✓ PHP fonctionne</p>";

// Test 2: Connexion base de données sans dépendances
try {
    $host = 'localhost';
    $dbname = 'doctime_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✓ Connexion PDO directe réussie</p>";
    
    // Test 3: Vérifier la table sponsors
    $stmt = $pdo->query("SELECT COUNT(*) FROM sponsors");
    $count = $stmt->fetchColumn();
    echo "<p>✓ Table sponsors existe: $count enregistrements</p>";
    
    // Test 4: Vérifier la colonne montant
    $stmt = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'montant'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✓ Colonne montant existe</p>";
        
        // Test 5: Vérifier les montants
        $stmt = $pdo->query("SELECT nom, montant FROM sponsors ORDER BY montant DESC LIMIT 5");
        $sponsors = $stmt->fetchAll();
        
        echo "<h3>Sponsors trouvés:</h3>";
        foreach ($sponsors as $sponsor) {
            echo "<p>" . htmlspecialchars($sponsor['nom']) . ": " . number_format($sponsor['montant'], 2, ',', ' ') . " TND</p>";
        }
    } else {
        echo "<p>✗ Colonne montant manquante</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='setup_sponsors_complete.php'>Configuration sponsors</a></p>";
echo "<p><a href='debug_dashboard.php'>Debug dashboard</a></p>";
echo "<p><a href='index.php?page=dashboard'>Dashboard original</a></p>";
?>
