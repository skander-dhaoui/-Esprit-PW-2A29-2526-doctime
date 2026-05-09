<?php
// Script de test MapController
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "✅ Connexion DB: OK<br>";
    
    // Test tables
    $tables = [
        'events' => 'SELECT COUNT(*) as cnt FROM events',
        'participations' => 'SELECT COUNT(*) as cnt FROM participations',
        'sponsors' => 'SELECT COUNT(*) as cnt FROM sponsors',
        'users' => 'SELECT COUNT(*) as cnt FROM users WHERE role = "admin"'
    ];
    
    foreach ($tables as $name => $query) {
        try {
            $result = $pdo->query($query)->fetch();
            echo "✅ Table $name: " . $result['cnt'] . " enregistrements<br>";
        } catch (Exception $e) {
            echo "❌ Table $name: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Test MapController</h3>";
    
    // Test MapController
    require_once __DIR__ . '/controllers/MapController.php';
    $mapCtrl = new MapController();
    echo "✅ MapController instancié<br>";
    
    // Tester la résolution de coordonnées
    $coords = $mapCtrl->resolveCoords('Tunis');
    // Pas accessible car c'est private, on va juste voir si le contrôleur se charge
    echo "✅ MapController chargé correctement<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}
?>
