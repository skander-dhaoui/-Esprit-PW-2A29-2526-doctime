<?php
// Test complet - Vérifier tous les boutons
header('Content-Type: text/html; charset=utf-8');
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Boutons</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h1>✓ Test des Boutons d'Action</h1>
    <table class='table table-striped mt-4'>
        <thead class='table-dark'>
            <tr>
                <th>Section</th>
                <th>Bouton</th>
                <th>URL attendue</th>
                <th>État</th>
            </tr>
        </thead>
        <tbody>\n";

// Test des URLs
$tests = [
    // USERS
    ['Users', 'Ajouter', 'index.php?page=users&action=create', 'GET'],
    ['Users', 'Éditer ID 1', 'index.php?page=users&action=edit&id=1', 'GET'],
    ['Users', 'Supprimer ID 1', 'index.php?page=users&action=delete&id=1', 'GET'],
    
    // PATIENTS
    ['Patients', 'Ajouter', 'index.php?page=patients&action=add', 'GET'],
    ['Patients', 'Éditer ID 1', 'index.php?page=patients&action=edit&id=1', 'GET'],
    ['Patients', 'Supprimer ID 1', 'index.php?page=patients&action=delete&id=1', 'GET'],
    
    // MEDECINS
    ['Médecins', 'Ajouter', 'index.php?page=medecins_admin&action=add', 'GET'],
    ['Médecins', 'Éditer ID 3', 'index.php?page=medecins_admin&action=edit&id=3', 'GET'],
    ['Médecins', 'Supprimer ID 3', 'index.php?page=medecins_admin&action=delete&id=3', 'GET'],
];

foreach ($tests as $test) {
    $section = $test[0];
    $button = $test[1];
    $url = $test[2];
    
    // Vérifier si la page existe
    $response = @file_get_contents('http://localhost' . (strpos($url, 'http') === 0 ? '' : '/valorys_Copie/') . $url);
    $status = $response !== false ? '✓ OK' : '❌ Erreur';
    $badgeClass = $response !== false ? 'bg-success' : 'bg-danger';
    
    echo "<tr>
        <td>$section</td>
        <td>$button</td>
        <td><small>$url</small></td>
        <td><span class='badge $badgeClass'>$status</span></td>
    </tr>\n";
}

echo "        </tbody>
    </table>
    
    <hr>
    <h3 class='mt-5'>Vérification des vues des listes</h3>
    <table class='table table-striped'>
        <thead class='table-dark'>
            <tr>
                <th>Page</th>
                <th>Bouton Ajouter visible?</th>
                <th>Boutons Edit/Delete visibles?</th>
            </tr>
        </thead>
        <tbody>\n";

$pages = [
    'users' => 'http://localhost/valorys_Copie/index.php?page=users',
    'patients' => 'http://localhost/valorys_Copie/index.php?page=patients',
    'medecins' => 'http://localhost/valorys_Copie/index.php?page=medecins_admin',
];

foreach ($pages as $name => $url) {
    $html = @file_get_contents($url);
    
    $hasAdd = preg_match('/action=(create|add)/', $html) ? '✓' : '❌';
    $hasActions = preg_match('/fa-edit|fa-trash|fa-edit|action=(edit|delete)/', $html) ? '✓' : '❌';
    
    echo "<tr>
        <td><strong>$name</strong></td>
        <td>$hasAdd</td>
        <td>$hasActions</td>
    </tr>\n";
}

echo "        </tbody>
    </table>
</div>
</body>
</html>";
?>
