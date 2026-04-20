<?php
// Test la requête complète avec jointure
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

echo "<pre>\n";
try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1: Sans jointure
    echo "=== Sans jointure ===\n";
    $stmt = $db->query("SELECT id, titre, status FROM articles LIMIT 2");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OK: " . count($result) . " articles\n\n";
    
    // Test 2: Avec jointure
    echo "=== Avec jointure LEFT JOIN ===\n";
    $stmt = $db->query("
        SELECT a.id, a.titre, a.status, a.auteur_id, u.nom, u.prenom
        FROM articles a
        LEFT JOIN users u ON a.auteur_id = u.id
        LIMIT 2
    ");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OK: " . count($result) . " articles\n";
    foreach ($result as $row) {
        echo "  - " . $row['titre'] . " par " . ($row['prenom'] . ' ' . $row['nom']) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
