<?php
echo "<h1>🔍 Diagnostic rapide - Sponsors</h1>";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si la base existe
    $stmt = $pdo->query('SHOW DATABASES LIKE "doctime_db"');
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>❌ Base de données 'doctime_db' n'existe pas</p>";
        echo "<p><a href='setup_db.php'>Créer la base de données</a></p>";
        exit;
    }

    $pdo->exec('USE doctime_db');

    // Vérifier la table sponsors
    $stmt = $pdo->query('SHOW TABLES LIKE "sponsors"');
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>❌ Table 'sponsors' n'existe pas</p>";
        exit;
    }

    // Vérifier la structure de la table sponsors
    echo "<h3>📋 Structure de la table sponsors</h3>";
    $stmt = $pdo->query('DESCRIBE sponsors');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";

    // Vérifier les valeurs de statut
    echo "<h3>🔍 Valeurs de statut dans les sponsors</h3>";
    $stmt = $pdo->query('SELECT DISTINCT statut FROM sponsors');
    $statuts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Statuts trouvés: " . implode(', ', $statuts) . "</p>";

    if ($total > 0) {
        echo "<h3>Liste des sponsors:</h3><ul>";
        $stmt = $pdo->query('SELECT id, nom, statut, actif FROM sponsors ORDER BY id');
        while ($row = $stmt->fetch()) {
            $status = $row['actif'] ? 'actif' : 'inactif';
            echo "<li>{$row['nom']} (ID: {$row['id']}, Statut: {$row['statut']}, Actif: $status)</li>";
        }
        echo "</ul>";
    }

    // Tester le modèle Sponsor
    echo "<h3>🧪 Test du modèle Sponsor</h3>";
    require_once 'models/Sponsor.php';
    $sponsorModel = new Sponsor();

    $sponsors = $sponsorModel->getAll(0, 100, 'all');
    echo "<p>Modèle getAll('all') retourne: <strong>" . count($sponsors) . "</strong> sponsors</p>";

    $sponsorsActifs = $sponsorModel->getAll(0, 100, 'actif');
    echo "<p>Modèle getAll('actif') retourne: <strong>" . count($sponsorsActifs) . "</strong> sponsors</p>";

    if (count($sponsors) > 0) {
        echo "<h4>Détails du premier sponsor:</h4><pre>";
        print_r($sponsors[0]);
        echo "</pre>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>