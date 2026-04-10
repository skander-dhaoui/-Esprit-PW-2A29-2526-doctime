<?php
// Database configuration
$host = 'localhost';
$dbname = 'doctime_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== All Users in Database ===" . PHP_EOL;
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, role FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        echo "ID: " . $u['id'] . " | Name: " . $u['prenom'] . " " . $u['nom'] . " | Email: " . $u['email'] . " | Role: " . $u['role'] . PHP_EOL;
    }
    
    echo "" . PHP_EOL;
    echo "Total users: " . count($users) . PHP_EOL;
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
