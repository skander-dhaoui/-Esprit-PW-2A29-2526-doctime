<?php
// Test simple pour déboguer listArticles
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AdminController.php';

echo "<pre>\n";
try {
    $admin = new AdminController();
    
    // Tester la requête directement
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT a.id, a.titre, a.status
        FROM articles a
        LIMIT 5
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Articles trouvés: " . count($articles) . "\n";
    foreach ($articles as $art) {
        echo "- ID: " . $art['id'] . ", Titre: " . htmlspecialchars($art['titre']) . ", Status: [" . $art['status'] . "]\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
echo "</pre>";
?>
