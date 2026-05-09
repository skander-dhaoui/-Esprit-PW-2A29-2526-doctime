<?php
// Script pour créer/obtenir un compte admin
require_once __DIR__ . '/config/database.php';

$pdo = Database::getInstance()->getConnection();

// Vérifier s'il existe déjà un admin
$admin = $pdo->query("SELECT id, email, prenom, nom, role FROM users WHERE role = 'admin' LIMIT 1")->fetch();

if ($admin) {
    echo "<h3>Admin existant trouvé:</h3>";
    echo "Email: " . htmlspecialchars($admin['email']) . "<br>";
    echo "Nom: " . htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']) . "<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Rôle: " . $admin['role'] . "<br>";
} else {
    echo "Aucun admin trouvé.";
}

// Afficher les 5 premiers événements
echo "<hr>";
echo "<h3>Événements dans la base de données:</h3>";
$events = $pdo->query("SELECT id, titre, lieu, statut, date_debut FROM events LIMIT 10")->fetchAll();
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Titre</th><th>Lieu</th><th>Statut</th><th>Date</th></tr>";
foreach ($events as $event) {
    echo "<tr>";
    echo "<td>" . $event['id'] . "</td>";
    echo "<td>" . htmlspecialchars($event['titre']) . "</td>";
    echo "<td>" . htmlspecialchars($event['lieu']) . "</td>";
    echo "<td>" . $event['statut'] . "</td>";
    echo "<td>" . $event['date_debut'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Statistiques
echo "<hr>";
echo "<h3>Statistiques:</h3>";
$stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT e.id) as total_events,
        COUNT(DISTINCT p.id) as total_participants,
        COUNT(DISTINCT s.id) as total_sponsors
    FROM events e
    LEFT JOIN participations p ON p.event_id = e.id
    LEFT JOIN sponsors s ON s.id = e.sponsor_id
")->fetch();
echo "Total événements: " . $stats['total_events'] . "<br>";
echo "Total participants: " . $stats['total_participants'] . "<br>";
echo "Total sponsors: " . $stats['total_sponsors'] . "<br>";
?>
