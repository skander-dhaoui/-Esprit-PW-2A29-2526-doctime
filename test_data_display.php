<?php
// Test détaillé pour vérifier les données affichées
header('Content-Type: text/plain; charset=utf-8');

echo "═══════════════════════════════════════\n";
echo "TEST DÉTAILLÉ - AFFICHAGE DES DONNÉES\n";
echo "═══════════════════════════════════════\n\n";

// Test 1: Vérifier que les médecins s'affichent
echo "TEST 1: Page médecins\n";
$html = file_get_contents('http://localhost/valorys_Copie/index.php?page=medecins');
if (preg_match('/afnen|gorai|herguam|hedi/i', $html)) {
    echo "✓ Les noms des médecins sont visibles\n";
} else {
    echo "❌ Les noms des médecins ne sont pas visibles\n";
}

if (preg_match('/af@gmail\.com|admin@mediconnect\.tn/i', $html)) {
    echo "✓ Les emails des médecins sont visibles\n";
} else {
    echo "❌ Les emails des médecins ne sont pas visibles\n";
}

if (preg_match('/Aucun médecin trouvé/i', $html)) {
    echo "❌ Message 'Aucun médecin trouvé' détecté (les données ne s'affichent pas)\n";
} else {
    echo "✓ Message 'Aucun médecin trouvé' absent\n";
}

echo "\nTEST 2: Page articles\n";
$html = file_get_contents('http://localhost/valorys_Copie/index.php?page=articles_admin');
if (preg_match('/Aucun article trouvé/i', $html)) {
    echo "ℹ Message 'Aucun article trouvé' (normal si pas d'articles)\n";
} else {
    echo "✓ Articles détectés\n";
}

echo "\nTEST 3: Page utilisateurs\n";
$html = file_get_contents('http://localhost/valorys_Copie/index.php?page=users');
if (preg_match('/Aucun utilisateur trouvé/i', $html)) {
    echo "ℹ Message 'Aucun utilisateur trouvé'\n";
} else {
    echo "✓ Utilisateurs détectés\n";
}

echo "\nTEST 4: Page patients\n";
$html = file_get_contents('http://localhost/valorys_Copie/index.php?page=patients');
if (preg_match('/Aucun patient trouvé/i', $html)) {
    echo "ℹ Message 'Aucun patient trouvé' (normal si pas de patients)\n";
} else {
    echo "✓ Patients détectés\n";
}

echo "\n═══════════════════════════════════════\n";
?>
