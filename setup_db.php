<?php
echo "<h1>Configuration de la base de données DOCTIME</h1>";
echo "<pre>";

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    echo "🔄 Connexion à MySQL...\n";
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie\n\n";

    // Créer la base
    echo "🔄 Suppression de l'ancienne base si elle existe...\n";
    $pdo->exec("DROP DATABASE IF EXISTS doctime_db");
    echo "🔄 Création de la nouvelle base...\n";
    $pdo->exec("CREATE DATABASE doctime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données 'doctime_db' créée\n\n";

    $pdo->exec("USE doctime_db");

    // Lire et exécuter le fichier SQL
    echo "🔄 Lecture du fichier database.sql...\n";
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Fichier database.sql introuvable: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    echo "🔄 Exécution du schéma de base...\n";
    $pdo->exec($sql);
    echo "✅ Tables et données de base créées\n\n";

    // Appliquer les migrations SQL présentes dans le dossier migrations
    echo "🔄 Recherche des migrations...\n";
    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    sort($migrationFiles, SORT_NATURAL);

    echo "Migrations trouvées: " . count($migrationFiles) . "\n";

    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        echo "🔄 Application de $migrationName...\n";

        $migrationSql = file_get_contents($migrationFile);
        if (trim($migrationSql) !== '') {
            $pdo->exec($migrationSql);
            echo "✅ Migration $migrationName appliquée\n";
        } else {
            echo "⚠️  Migration $migrationName vide, ignorée\n";
        }
    }

    echo "\n✅ INSTALLATION TERMINÉE AVEC SUCCÈS!\n\n";

    // Vérification finale
    echo "🔄 Vérification des données...\n";
    $stmt = $pdo->query('SELECT COUNT(*) FROM events');
    $events = $stmt->fetchColumn();
    echo "Événements créés: $events\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM sponsors');
    $sponsors = $stmt->fetchColumn();
    echo "Sponsors créés: $sponsors\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"');
    $admins = $stmt->fetchColumn();
    echo "Administrateurs créés: $admins\n";

    echo "</pre>";
    echo "<hr>";
    echo "<h3>🎉 Prêt à utiliser!</h3>";
    echo "<p><strong>Compte admin:</strong> admin@doctime.com / admin123</p>";
    echo "<p><a href='index.php?page=login' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Aller à la connexion</a></p>";
    echo "<p><a href='check_db.php'>Vérifier la base de données</a></p>";

} catch(PDOException $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>❌ ERREUR PDO</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<h3>Dépannage:</h3>";
    echo "<ul>";
    echo "<li>Vérifiez que XAMPP est démarré (Apache + MySQL)</li>";
    echo "<li>Vérifiez que le port MySQL (3306) n'est pas utilisé par une autre application</li>";
    echo "<li>Essayez de redémarrer XAMPP</li>";
    echo "</ul>";
} catch(Exception $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>❌ ERREUR GÉNÉRALE</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<h3>Dépannage:</h3>";
    echo "<ul>";
    echo "<li>Vérifiez les permissions des fichiers</li>";
    echo "<li>Vérifiez que les fichiers database.sql et migrations/*.sql existent</li>";
    echo "</ul>";
}
?>