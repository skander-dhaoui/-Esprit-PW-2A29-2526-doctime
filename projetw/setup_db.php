<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base
    $pdo->exec("DROP DATABASE IF EXISTS doctime_db");
    $pdo->exec("CREATE DATABASE doctime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données créée<br>";
    
    $pdo->exec("USE doctime_db");
    
    // Lire et exécuter le fichier SQL
    $sql = file_get_contents(__DIR__ . '/database.sql');
    $pdo->exec($sql);
    echo "✅ Tables créées avec succès<br>";
    
    echo "<hr>";
    echo "<h3>Installation terminée !</h3>";
    echo "<p>Compte admin: <strong>admin@doctime.com</strong> / <strong>admin123</strong></p>";
    echo "<a href='index.php?page=login'>Aller à la connexion</a>";
    
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>