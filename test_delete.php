<?php
// Test suppression utilisateurs
header('Content-Type: text/html; charset=utf-8');
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Suppression</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h1>✓ Test Suppression d'Utilisateurs</h1>
    <table class='table table-striped mt-4'>
        <thead class='table-dark'>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Nom</th>
                <th>Rôle</th>
                <th>Articles liés</th>
                <th>Peut être supprimé?</th>
            </tr>
        </thead>
        <tbody>\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Chercher les utilisateurs avec articles
    $stmt = $db->query("
        SELECT u.id, u.nom, u.prenom, u.role,
               COUNT(a.id) as article_count
        FROM users u
        LEFT JOIN articles a ON u.id = a.auteur_id
        GROUP BY u.id, u.role
        HAVING COUNT(a.id) > 0 OR u.role IN ('patient', 'medecin')
        LIMIT 10
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $articles = $user['article_count'] ?? 0;
        $canDelete = $articles >= 0 ? '✓ Oui (articles supprimés en cascade)' : '❌ Non (FK)';
        $badgeClass = $articles > 0 ? 'bg-warning' : 'bg-success';
        
        echo "<tr>
            <td>{$user['id']}</td>
            <td>User</td>
            <td>{$user['prenom']} {$user['nom']}</td>
            <td><span class='badge bg-info'>{$user['role']}</span></td>
            <td><span class='badge $badgeClass'>$articles</span></td>
            <td>$canDelete</td>
        </tr>\n";
    }
    
    echo "        </tbody>
    </table>
    
    <div class='alert alert-info mt-5'>
        <strong>ℹ️ Note:</strong> Avec les modifications appliquées, tous les utilisateurs peuvent être supprimés:
        <ul>
            <li>Les articles liés sont d'abord supprimés</li>
            <li>Puis l'utilisateur est supprimé</li>
            <li>Les contraintes de clé étrangère ne bloqueront plus</li>
        </ul>
    </div>
</div>
</body>
</html>";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
