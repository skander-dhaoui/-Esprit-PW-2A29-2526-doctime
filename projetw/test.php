<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
// Forcer au moins 3 événements à avoir un prix > 0
$db->execute("UPDATE events SET prix = 150 WHERE id IN (SELECT id FROM (SELECT id FROM events LIMIT 3) as t)");
// Forcer tous les statuts à 'confirmé'
$db->execute("UPDATE participations SET statut = 'confirmé'");
echo "Mise à jour effectuée.";
