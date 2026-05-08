<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getInstance()->getConnection();

echo "<h3>Colonnes users:</h3>";
$cols = $pdo->query("DESCRIBE users")->fetchAll();
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th></tr>";
foreach ($cols as $col) {
    echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td></tr>";
}
echo "</table>";
?>
