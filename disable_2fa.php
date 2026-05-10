<?php
require_once __DIR__ . '/config/database.php';

$pdo = Database::getInstance()->getConnection();

// Désactiver la 2FA pour l'admin
$result = $pdo->prepare("UPDATE users SET deux_factor_enabled = 0 WHERE role = 'admin' LIMIT 1")->execute();
echo "2FA désactivée pour admin: " . ($result ? "OK" : "ERREUR") . "<br>";

// Vérifier
$admin = $pdo->query("SELECT id, email, deux_factor_enabled FROM users WHERE role = 'admin' LIMIT 1")->fetch();
echo "Statut 2FA: " . ($admin['deux_factor_enabled'] ? "Activée" : "Désactivée") . "<br>";
?>
