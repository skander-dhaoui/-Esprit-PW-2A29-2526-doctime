<?php
// Simuler un appel à listArticles()
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

ob_start();

try {
    require_once __DIR__ . '/config/database.php';
    
    // Récréer le flux de listArticles()
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT a.id, a.titre, a.contenu, a.status, a.vues, a.created_at, 
               a.auteur_id, u.nom, u.prenom
        FROM articles a
        LEFT JOIN users u ON a.auteur_id = u.id
        ORDER BY a.created_at DESC
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Définir les variables pour la vue
    $pageTitle = 'Gestion des Articles';
    $currentPage = 'articles_admin';
    $contentFile = __DIR__ . '/views/backoffice/articles_list_content.php';
    
    // Inclure la vue de contenu directement
    ob_end_clean();
    
    echo "<html><head><meta charset='UTF-8'><title>Test Articles</title></head><body>";
    echo "<h1>Test Affichage Articles</h1>";
    echo "<p>Articles trouvés: " . count($articles) . "</p>";
    
    if (count($articles) > 0) {
        echo "<ul>";
        foreach ($articles as $art) {
            echo "<li>" . htmlspecialchars($art['titre']) . " - " . $art['prenom'] . " " . $art['nom'] . "</li>";
        }
        echo "</ul>";
    }
    
    echo "</body></html>";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<pre>Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "</pre>";
}
?>
