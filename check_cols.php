<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getInstance()->getConnection();

echo "<h3>Colonnes participations:</h3>";
$cols = $pdo->query("DESCRIBE participations")->fetchAll();
foreach ($cols as $col) {
    echo $col['Field'] . "<br>";
}

echo "<hr>";
echo "<h3>Colonnes sponsors:</h3>";
$cols = $pdo->query("DESCRIBE sponsors")->fetchAll();
foreach ($cols as $col) {
    echo $col['Field'] . "<br>";
}

echo "<hr>";
echo "<h3>Exemple participation:</h3>";
$part = $pdo->query("SELECT * FROM participations LIMIT 1")->fetch();
if ($part) {
    echo "<pre>";
    print_r($part);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>Exemple sponsor:</h3>";
$spon = $pdo->query("SELECT * FROM sponsors LIMIT 1")->fetch();
if ($spon) {
    echo "<pre>";
    print_r($spon);
    echo "</pre>";
}
?>
