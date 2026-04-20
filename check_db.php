<?php
echo "<h1>Diagnostic de la base de données</h1>";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si la base existe
    $stmt = $pdo->query('SHOW DATABASES LIKE "doctime_db"');
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p style='color: green;'>✅ Base de données doctime_db existe</p>";

        $pdo->exec('USE doctime_db');

        // Vérifier les tables
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables trouvées: " . count($tables) . "</p>";

        // Vérifier les événements
        $stmt = $pdo->query('SELECT COUNT(*) FROM events');
        $events = $stmt->fetchColumn();
        echo "<p>Événements: <strong>$events</strong></p>";

        // Vérifier les sponsors
        $stmt = $pdo->query('SELECT COUNT(*) FROM sponsors');
        $sponsors = $stmt->fetchColumn();
        echo "<p>Sponsors: <strong>$sponsors</strong></p>";

        // Lister les événements
        if ($events > 0) {
            echo "<h3>Événements dans la base:</h3><ul>";
            $stmt = $pdo->query('SELECT id, titre, status FROM events');
            while ($row = $stmt->fetch()) {
                echo "<li>{$row['titre']} (Status: {$row['status']})</li>";
            }
            echo "</ul>";
        }

        // Lister les sponsors
        if ($sponsors > 0) {
            echo "<h3>Sponsors dans la base:</h3><ul>";
            $stmt = $pdo->query('SELECT id, nom, niveau FROM sponsors');
            while ($row = $stmt->fetch()) {
                echo "<li>{$row['nom']} (Niveau: {$row['niveau']})</li>";
            }
            echo "</ul>";
        }

    } else {
        echo "<p style='color: red;'>❌ Base de données doctime_db n'existe pas</p>";
        echo "<p><a href='setup_db.php'>Cliquez ici pour créer la base de données</a></p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur de connexion: " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez que MySQL est démarré dans XAMPP.</p>";
}
?>