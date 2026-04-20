<?php
// Test affichage articles
$html = file_get_contents('http://localhost/valorys_Copie/index.php?page=articles_admin');

// Vérifier si les articles s'affichent
echo "═══════════════════════════════════════\n";
echo "TEST AFFICHAGE ARTICLES\n";
echo "═══════════════════════════════════════\n\n";

if (preg_match('/Introduction aux Jointures SQL/i', $html)) {
    echo "✓ Article 9 titre visible\n";
} else {
    echo "❌ Article 9 titre non visible\n";
}

if (preg_match('/Cr.*er votre premi.*re jointure/u', $html)) {
    echo "✓ Article 10 titre encodé correctement (caractères accentués OK)\n";
} else {
    echo "⚠ Problème d'encodage possible\n";
}

if (preg_match('/System Admin|herguam|hedi/i', $html)) {
    echo "✓ Noms d'auteurs visibles\n";
} else {
    echo "❌ Noms d'auteurs non visibles\n";
}

if (preg_match('/<i class=\"fas fa-edit\"><\/i>/m', $html)) {
    echo "✓ Bouton Edit visible\n";
} else {
    echo "❌ Bouton Edit non visible\n";
}

if (preg_match('/<i class=\"fas fa-trash\"><\/i>/m', $html)) {
    echo "✓ Bouton Delete visible\n";
} else {
    echo "❌ Bouton Delete non visible\n";
}

if (preg_match('/bg-warning|bg-success/', $html)) {
    echo "✓ Badges de statut visibles\n";
} else {
    echo "❌ Badges de statut non visibles\n";
}

// Compter les articles affichés
preg_match_all('/<tr>/', $html, $matches);
$rowCount = count($matches[0]);
echo "\nNombre de lignes (articles + header): " . $rowCount . "\n";

echo "\n═══════════════════════════════════════\n";
?>
