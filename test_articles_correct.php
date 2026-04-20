<?php
// Requête correcte
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance()->getConnection();
    
    // Bonne requête avec les vrais noms de colonnes
    $stmt = $db->query("
        SELECT a.id, a.titre, a.contenu, a.status, a.vues, a.created_at, 
               a.auteur_id, u.nom, u.prenom
        FROM articles a
        LEFT JOIN users u ON a.auteur_id = u.id
        ORDER BY a.created_at DESC
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Articles trouvés: " . count($articles) . "\n\n";
    
    foreach ($articles as $article) {
        echo "ID: " . $article['id'] . "\n";
        echo "Titre: " . htmlspecialchars($article['titre']) . "\n";
        echo "Auteur: " . $article['prenom'] . ' ' . $article['nom'] . "\n";
        echo "Status: [" . $article['status'] . "]\n";
        echo "Vues: " . $article['vues'] . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
