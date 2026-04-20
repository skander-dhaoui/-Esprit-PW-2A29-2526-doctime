<?php
// Diagnostiquer les problèmes des articles
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Diagnostic des Articles</h2>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier la structure de la table articles
    echo "=== STRUCTURE TABLE ARTICLES ===\n";
    $stmt = $db->query("DESCRIBE articles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . "\n";
    }
    
    echo "\n=== ARTICLES AVEC DÉTAILS ===\n";
    $stmt = $db->query("
        SELECT a.*, u.nom, u.prenom
        FROM articles a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.id DESC
        LIMIT 5
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($articles as $article) {
        echo "\n--- Article ID: " . $article['id'] . " ---\n";
        echo "Titre: " . htmlspecialchars($article['titre'] ?? 'N/A') . "\n";
        echo "Contenu length: " . strlen($article['contenu'] ?? '') . "\n";
        echo "Status: [" . ($article['statut'] ?? 'NULL') . "]\n";
        echo "User ID: " . $article['user_id'] . "\n";
        echo "User Name: " . ($article['prenom'] . ' ' . $article['nom']) . "\n";
        echo "Date: " . $article['created_at'] . "\n";
        echo "Vues: " . $article['views'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

echo "</pre>\n";
?>
