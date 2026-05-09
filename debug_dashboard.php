<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<h1>Debug Dashboard</h1>";

try {
    $db = Database::getInstance()->getConnection();
    echo "<p>✓ Connexion à la base de données réussie</p>";
    
    // Vérifier les tables
    $tables = ['sponsors', 'events', 'participations'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "<p>Table '$table': " . ($exists ? "✓ Existe" : "✗ Manquante") . "</p>";
    }
    
    // Vérifier la colonne montant
    if (in_array('sponsors', $tables)) {
        $stmt = $db->query("SHOW COLUMNS FROM sponsors LIKE 'montant'");
        $hasMontant = $stmt->rowCount() > 0;
        echo "<p>Colonne 'montant' dans sponsors: " . ($hasMontant ? "✓ Existe" : "✗ Manquante") . "</p>";
        
        if ($hasMontant) {
            $totalSponsors = $db->query("SELECT COUNT(*) FROM sponsors")->fetchColumn();
            $totalMontant = $db->query("SELECT COALESCE(SUM(montant), 0) FROM sponsors")->fetchColumn();
            echo "<p><strong>Total sponsors:</strong> $totalSponsors</p>";
            echo "<p><strong>Montant total:</strong> $totalMontant TND</p>";
            
            // Afficher les sponsors
            $sponsors = $db->query("SELECT nom, montant FROM sponsors ORDER BY montant DESC")->fetchAll();
            echo "<h3>Liste des sponsors:</h3>";
            echo "<ul>";
            foreach ($sponsors as $sponsor) {
                echo "<li>" . htmlspecialchars($sponsor['nom']) . ": " . number_format($sponsor['montant'], 2, ',', ' ') . " TND</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<p><a href='setup_sponsors_complete.php'>Configurer les sponsors</a></p>";
echo "<p><a href='index.php?page=dashboard'>Retour au dashboard</a></p>";
?>
