<?php
// Script pour vérifier les colonnes
require_once __DIR__ . '/config/database.php';

$pdo = Database::getInstance()->getConnection();

echo "<h3>Colonnes de la table events:</h3>";
$columns = $pdo->query("DESCRIBE events")->fetchAll();
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . $col['Default'] . "</td>";
    echo "<td>" . $col['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Exemple d'événement:</h3>";
$event = $pdo->query("SELECT * FROM events LIMIT 1")->fetch();
if ($event) {
    echo "<pre>";
    print_r($event);
    echo "</pre>";
}
?>
