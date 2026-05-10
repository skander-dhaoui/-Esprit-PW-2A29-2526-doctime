<?php
echo "<h1>Recherche du fichier Article.php</h1>";

// Chemins possibles
$paths = [
    __DIR__ . '/models/Article.php',
    __DIR__ . '/../models/Article.php',
    __DIR__ . '/controllers/models/Article.php',
    __DIR__ . '/../controllers/models/Article.php',
];

foreach ($paths as $path) {
    $realPath = realpath($path);
    if ($realPath && file_exists($realPath)) {
        echo "<p>✅ Fichier trouvé: " . $realPath . "</p>";
        
        // Lire le contenu pour vérifier la méthode getAll
        $content = file_get_contents($realPath);
        if (strpos($content, 'function getAll') !== false) {
            echo "<p style='color:green'>   ✅ La méthode getAll() existe dans ce fichier</p>";
        } else {
            echo "<p style='color:red'>   ❌ La méthode getAll() n'existe PAS dans ce fichier</p>";
            echo "<pre>" . htmlspecialchars(substr($content, 0, 300)) . "</pre>";
        }
    } else {
        echo "<p>❌ Fichier non trouvé: " . $path . "</p>";
    }
}

// Vérifier aussi la classe chargée
echo "<h2>Vérification de la classe chargée</h2>";

// Inclure le modèle normalement
require_once __DIR__ . '/models/Article.php';

if (class_exists('Article')) {
    echo "<p>✅ Classe Article existe</p>";
    
    $reflection = new ReflectionClass('Article');
    echo "<p>Fichier de la classe: " . $reflection->getFileName() . "</p>";
    
    $methods = get_class_methods('Article');
    echo "<p>Méthodes disponibles: " . implode(', ', $methods) . "</p>";
    
    if (in_array('getAll', $methods)) {
        echo "<p style='color:green'>✅ getAll() existe bien</p>";
    } else {
        echo "<p style='color:red'>❌ getAll() n'existe PAS</p>";
    }
} else {
    echo "<p style='color:red'>❌ Classe Article n'existe pas</p>";
}
?>