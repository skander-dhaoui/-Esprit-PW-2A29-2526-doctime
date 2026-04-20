<?php
// Test toutes les pages critiques
header('Content-Type: text/plain; charset=utf-8');

$pages = [
    'users' => 'http://localhost/valorys_Copie/index.php?page=users',
    'articles_admin' => 'http://localhost/valorys_Copie/index.php?page=articles_admin',
    'patients' => 'http://localhost/valorys_Copie/index.php?page=patients',
    'medecins' => 'http://localhost/valorys_Copie/index.php?page=medecins',
];

echo "═══════════════════════════════════════\n";
echo "TEST DES PAGES BACKOFFICE\n";
echo "═══════════════════════════════════════\n\n";

$results = [];
foreach ($pages as $name => $url) {
    $output = @file_get_contents($url);
    
    if ($output === false) {
        $results[$name] = ['status' => '❌ ERREUR', 'message' => 'Impossible de récupérer la page'];
        continue;
    }
    
    $issues = [];
    
    // Chercher du code PHP brut
    if (preg_match('/}\\s*\\?>|<\\?php.*?(?=<|\$|^)/s', $output)) {
        $issues[] = "Code PHP brut possible";
    }
    
    // Chercher le HTML basique
    if (preg_match('/<html|<!DOCTYPE/i', $output)) {
        $status = empty($issues) ? '✓ OK' : '⚠ AVERTISSEMENT';
        $message = empty($issues) ? 'Page rendue correctement' : implode(', ', $issues);
    } else {
        $status = '❌ ERREUR';
        $message = 'HTML structure manquante';
    }
    
    $results[$name] = [
        'status' => $status,
        'message' => $message,
        'size' => strlen($output) . ' bytes'
    ];
}

// Afficher les résultats
foreach ($results as $page => $result) {
    echo str_pad($page, 20) . " | " . str_pad($result['status'], 15) . " | " . $result['message'] . " | " . $result['size'] . "\n";
}

echo "\n═══════════════════════════════════════\n";
$allOk = array_filter($results, fn($r) => strpos($r['status'], '❌') === false) === count($results);
echo $allOk ? "✓ TOUTES LES PAGES SONT OK\n" : "⚠ CERTAINES PAGES ONT DES PROBLÈMES\n";
echo "═══════════════════════════════════════\n";
?>
