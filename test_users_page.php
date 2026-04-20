<?php
// Test rapide pour vérifier la page users
header('Content-Type: text/plain; charset=utf-8');

// Récupérer la page users
$output = file_get_contents('http://localhost/valorys_Copie/index.php?page=users');

if ($output === false) {
    echo "Erreur: impossible de récupérer la page\n";
    exit(1);
}

// Vérifier les problèmes
$issues = [];

// Chercher du code PHP brut visible
if (preg_match('/}\\s*\\?>\\s*(?:private|public|function)/', $output)) {
    $issues[] = "Code de classe/fonction détecté après ?>  ";
}

// Chercher des balises PHP non fermées
if (preg_match('/<\\?php(?!.*\\?>)/s', $output)) {
    $issues[] = "Balise PHP ouvrante sans fermeture détectée";
}

// Chercher du code PHP visible comme texte
if (preg_match('/error_log|require_once|\\$this->|catch \\(Exception/m', $output) && 
    !preg_match('/<pre>|<code>|<script/', $output)) {
    $issues[] = "Code PHP détecté en tant que texte visible";
}

// Vérifier le contenu HTML basique
if (!preg_match('/<html|<!DOCTYPE/i', $output)) {
    $issues[] = "HTML structure manquante";
}

if (empty($issues)) {
    echo "✓ Page users: FONCTIONNELLE\n";
    echo "✓ Aucun code PHP brut détecté\n";
    echo "✓ HTML correctement structuré\n";
    echo "\nTaille de la page: " . strlen($output) . " bytes\n";
    exit(0);
} else {
    echo "⚠ PROBLÈMES DÉTECTÉS:\n";
    foreach ($issues as $issue) {
        echo "  - " . $issue . "\n";
    }
    exit(1);
}
?>
